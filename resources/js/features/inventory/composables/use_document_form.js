import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { supplierApi } from '@/features/supplier/api/supplier_api.js';
import { productApi } from '@/features/product/api/product_api.js';
import { locationApi } from '@/features/location/api/location_api.js';
import { inventoryApi } from '../api/inventoryApi.js';
import { showToast } from '@/shared/utils/use_toast.js';
import {
    addDecimal4Strings,
    tryNormalizeDecimal4String,
    isValidDecimal4String,
    compareDecimal4Strings,
    normalizeDecimal4String,
} from '../scanner/utils/decimal_string.js';

const cleanQuantity = (qStr) => {
    const cleanQty = qStr.endsWith('.0000') ? qStr.slice(0, -5) : qStr.replace(/0+$/, '').replace(/\.$/, '');
    return cleanQty || '1';
};

export function useDocumentForm(config) {
    const { store, isEdit, basePath, headerKey, locationNoun, hasStockColumn } = config;

    const route = useRoute();
    const router = useRouter();

    const isSubmitting = ref(false);
    const errorMsg = ref('');
    const scanLocationId = ref('');
    const scannerPanelRef = ref(null);

    const form = ref({
        [headerKey]: '',
        date: new Date().toISOString().slice(0, 10),
        notes: '',
        ...(config.extraFields || {}),
        items: [],
    });

    const suppliers = ref([]);
    const products = ref([]);
    const locations = ref([]);

    const fetchDependencies = async () => {
        try {
            const tasks = [
                productApi.getAll({ is_active: 1, per_page: 1000 }),
                locationApi.getAll({ is_active: 1, assigned_only: 1, per_page: 1000 }),
            ];
            if (headerKey === 'supplier_id') {
                tasks.unshift(supplierApi.getAll({ is_active: 1, per_page: 500 }));
            }
            const [supRes, prodRes, locRes] = await Promise.all(tasks);

            if (headerKey === 'supplier_id') {
                suppliers.value = supRes.data?.data?.data || supRes.data?.data || [];
            }
            products.value = prodRes.data?.data?.data || prodRes.data?.data || [];
            locations.value = locRes.data?.data?.data || locRes.data?.data || [];

            if (locations.value.length > 0) {
                scanLocationId.value = locations.value[0].id;
            }
        } catch {
            errorMsg.value = headerKey === 'supplier_id'
                ? 'Gagal memuat master data produk, lokasi, atau supplier.'
                : 'Gagal memuat master data produk atau lokasi.';
        }
    };

    const loadDocument = async () => {
        try {
            const data = await store.fetchById(route.params.id);
            if (data.status !== 'DRAFT') {
                showToast('Hanya DRAFT yang dapat diedit.', { type: 'error' });
                router.push(`${basePath}/${route.params.id}`);
                return;
            }
            const extraValues = {};
            if (config.extraFields) {
                for (const key of Object.keys(config.extraFields)) {
                    extraValues[key] = data[key] ?? config.extraFields[key];
                }
            }
            form.value = {
                [headerKey]: data[headerKey] ?? '',
                date: data.date,
                notes: data.notes || '',
                ...extraValues,
                items: data.items.map((i) => ({
                    product_id: i.product_id,
                    location_id: i.location_id,
                    quantity: cleanQuantity(String(i.quantity ?? '1')),
                    available_stock: '0.0000',
                })),
            };
            if (hasStockColumn) {
                form.value.items.forEach((_, idx) => fetchStock(idx));
            }
        } catch {
            errorMsg.value = store.error || 'Gagal memuat data dokumen.';
        }
    };

    const fetchStock = async (index) => {
        if (!hasStockColumn) return;
        const item = form.value.items[index];
        if (item && item.product_id && item.location_id) {
            try {
                const res = await inventoryApi.getBalances({
                    product_id: item.product_id,
                    location_id: item.location_id,
                });
                const data = res.data?.data?.data || res.data?.data || [];
                item.available_stock = data.length > 0 ? normalizeDecimal4String(data[0].quantity) : '0.0000';
            } catch {
                item.available_stock = '0.0000';
            }
        }
    };

    const isQuantityExceeding = (item) => {
        if (!item.available_stock || !item.quantity) return false;
        if (!isValidDecimal4String(item.quantity) || !isValidDecimal4String(item.available_stock)) return false;
        try {
            return compareDecimal4Strings(item.quantity, item.available_stock) > 0;
        } catch {
            return false;
        }
    };

    const getProductPrice = (productId) => {
        if (!productId) return 0;
        const prod = products.value.find((p) => p.id === productId);
        return prod?.unit_price ? Number(prod.unit_price) : 0;
    };

    const getItemSubtotal = (item) => {
        const price = getProductPrice(item.product_id);
        const qty = parseFloat(item.quantity) || 0;
        return price * qty;
    };

    const totalItemsQty = computed(() => {
        return form.value.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
    });

    const grandTotalAmount = computed(() => {
        return form.value.items.reduce((sum, item) => sum + getItemSubtotal(item), 0);
    });

    const handleQtyBlur = (item) => {
        const norm = tryNormalizeDecimal4String(item.quantity);
        if (norm !== null) {
            item.quantity = norm.endsWith('.0000') ? norm.slice(0, -5) : norm.replace(/0+$/, '').replace(/\.$/, '');
        }
    };

    const handleProductScanned = async (scannedProduct) => {
        if (!scanLocationId.value) {
            errorMsg.value = 'Pilih lokasi scan terlebih dahulu.';
            return;
        }

        const existsInProducts = products.value.some((p) => p.id === scannedProduct.id);
        if (!existsInProducts) {
            products.value.push(scannedProduct);
        }

        if (form.value.items.length === 1 && !form.value.items[0].product_id) {
            const newItem = {
                product_id: scannedProduct.id,
                location_id: scanLocationId.value,
                quantity: '1',
                available_stock: '0.0000',
            };
            form.value.items[0] = newItem;
            await fetchStock(0);
            errorMsg.value = '';
            return;
        }

        const existingItemIndex = form.value.items.findIndex(
            (item) => item.product_id === scannedProduct.id && String(item.location_id) === String(scanLocationId.value)
        );

        if (existingItemIndex !== -1) {
            const currentQty = form.value.items[existingItemIndex].quantity;
            if (!isValidDecimal4String(currentQty)) {
                errorMsg.value = `Kuantitas saat ini pada baris produk "${scannedProduct.name}" (${currentQty}) tidak valid. Harap perbaiki kuantitas sebelum melakukan scan ulang.`;
                return;
            }
            const added = addDecimal4Strings(currentQty, '1.0000');
            form.value.items[existingItemIndex].quantity = added.endsWith('.0000') ? added.slice(0, -5) : added;
            await fetchStock(existingItemIndex);
        } else {
            form.value.items.push({
                product_id: scannedProduct.id,
                location_id: scanLocationId.value,
                quantity: '1',
                available_stock: '0.0000',
            });
            await fetchStock(form.value.items.length - 1);
        }

        errorMsg.value = '';
    };

    const getProductSku = (productId) => {
        if (!productId) return '';
        const prod = products.value.find((p) => p.id === productId);
        return prod ? prod.sku : '';
    };

    const onSkuEntered = async (typedSku, index) => {
        const code = (typedSku || '').trim().toLowerCase();
        if (!code) {
            form.value.items[index].product_id = '';
            return;
        }

        const matched = products.value.find(
            (p) =>
                (p.sku && p.sku.toLowerCase() === code) ||
                (p.barcode && p.barcode.toLowerCase() === code)
        );

        if (matched) {
            form.value.items[index].product_id = matched.id;
            errorMsg.value = '';
            await fetchStock(index);
        } else {
            errorMsg.value = `Produk dengan SKU / Barcode "${typedSku}" tidak ditemukan.`;
        }
    };

    const addItem = () => {
        const newIdx = form.value.items.length;
        form.value.items.push({
            product_id: '',
            location_id: scanLocationId.value || (locations.value[0]?.id || ''),
            quantity: '1',
            available_stock: '0.0000',
        });
        if (form.value.items[newIdx].product_id && form.value.items[newIdx].location_id) {
            fetchStock(newIdx);
        }
    };

    const removeItem = (index) => {
        form.value.items.splice(index, 1);
    };

    const submitForm = async () => {
        if (form.value.items.length === 0) {
            errorMsg.value = 'Minimal harus ada 1 baris item produk.';
            return;
        }

        const combos = new Set();
        for (const item of form.value.items) {
            if (!item.product_id || !item.location_id) {
                errorMsg.value = `Semua baris item harus memilih produk dan ${locationNoun}.`;
                return;
            }
            if (!isValidDecimal4String(item.quantity) || compareDecimal4Strings(item.quantity, '0.0000') <= 0) {
                errorMsg.value = 'Kuantitas item harus berupa angka positif valid dengan maksimal 4 desimal.';
                return;
            }
            const key = `${item.product_id}-${item.location_id}`;
            if (combos.has(key)) {
                errorMsg.value = `Tidak boleh ada produk dan ${locationNoun} yang sama dalam satu dokumen.`;
                return;
            }
            combos.add(key);
        }

        isSubmitting.value = true;
        errorMsg.value = '';

        try {
            const payload = { ...form.value };
            if (payload.supplier_id === '') {
                payload.supplier_id = null;
            }
            if (isEdit) {
                await store.update(route.params.id, payload);
                router.push(`${basePath}/${route.params.id}`);
            } else {
                const data = await store.create(payload);
                router.push(`${basePath}/${data.data.id}`);
            }
        } catch (e) {
            errorMsg.value = store.error || e.response?.data?.message || 'Gagal menyimpan dokumen.';
        } finally {
            isSubmitting.value = false;
        }
    };

    const handleGlobalKeydown = (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            scannerPanelRef.value?.focusInput();
        } else if (e.key === 'F9') {
            e.preventDefault();
            if (!isSubmitting.value) {
                submitForm();
            }
        }
    };

    onMounted(async () => {
        window.addEventListener('keydown', handleGlobalKeydown);
        await fetchDependencies();
        if (isEdit) {
            await loadDocument();
        } else if (form.value.items.length === 0) {
            addItem();
        }
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleGlobalKeydown);
    });

    return {
        route,
        router,
        isEdit,
        isSubmitting,
        errorMsg,
        scanLocationId,
        scannerPanelRef,
        form,
        suppliers,
        products,
        locations,
        getProductPrice,
        getItemSubtotal,
        totalItemsQty,
        grandTotalAmount,
        fetchStock,
        isQuantityExceeding,
        handleQtyBlur,
        handleProductScanned,
        getProductSku,
        onSkuEntered,
        addItem,
        removeItem,
        submitForm,
    };
}