<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="onClose"
    >
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-100 flex-shrink-0">
          <h2 class="text-lg font-semibold text-gray-900">
            {{ isEditing ? 'Edit Supplier' : 'Tambah Supplier' }}
          </h2>
        </div>

        <form
          class="p-6 space-y-4 overflow-y-auto"
          @submit.prevent="handleSubmit"
        >
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label
                for="sup-code"
                class="block text-sm font-medium text-gray-700 mb-1"
              >Kode <span class="text-red-500">*</span></label>
              <input
                id="sup-code"
                v-model="form.code"
                type="text"
                class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 uppercase"
                :class="store.validationErrors?.code ? 'border-red-500' : 'border-gray-300'"
                placeholder="Contoh: SUP-001"
                required
              >
              <p
                v-if="store.validationErrors?.code"
                class="mt-1 text-xs text-red-600"
              >
                {{ store.validationErrors.code[0] }}
              </p>
            </div>

            <div>
              <label
                for="sup-name"
                class="block text-sm font-medium text-gray-700 mb-1"
              >Nama Supplier <span class="text-red-500">*</span></label>
              <input
                id="sup-name"
                v-model="form.name"
                type="text"
                class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                :class="store.validationErrors?.name ? 'border-red-500' : 'border-gray-300'"
                placeholder="Contoh: PT Maju Jaya"
                required
              >
              <p
                v-if="store.validationErrors?.name"
                class="mt-1 text-xs text-red-600"
              >
                {{ store.validationErrors.name[0] }}
              </p>
            </div>
          </div>

          <div>
            <label
              for="sup-contact"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Nama Kontak / PIC</label>
            <input
              id="sup-contact"
              v-model="form.contact_person"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              placeholder="Nama penanggung jawab"
            >
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label
                for="sup-phone"
                class="block text-sm font-medium text-gray-700 mb-1"
              >Telepon</label>
              <input
                id="sup-phone"
                v-model="form.phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                placeholder="08xx-xxxx-xxxx"
              >
            </div>

            <div>
              <label
                for="sup-email"
                class="block text-sm font-medium text-gray-700 mb-1"
              >Email</label>
              <input
                id="sup-email"
                v-model="form.email"
                type="email"
                class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                :class="store.validationErrors?.email ? 'border-red-500' : 'border-gray-300'"
                placeholder="email@supplier.com"
              >
              <p
                v-if="store.validationErrors?.email"
                class="mt-1 text-xs text-red-600"
              >
                {{ store.validationErrors.email[0] }}
              </p>
            </div>
          </div>

          <div>
            <label
              for="sup-tax"
              class="block text-sm font-medium text-gray-700 mb-1"
            >NPWP / Nomor Pajak</label>
            <input
              id="sup-tax"
              v-model="form.tax_number"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              placeholder="XX.XXX.XXX.X-XXX.XXX"
            >
          </div>

          <div>
            <label
              for="sup-address"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Alamat</label>
            <textarea
              id="sup-address"
              v-model="form.address"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              rows="3"
              placeholder="Alamat lengkap supplier"
            />
          </div>

          <div class="flex justify-end gap-3 pt-2 border-t border-gray-100 flex-shrink-0">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
              :disabled="store.isLoading"
              @click="onClose"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors shadow-sm"
              :disabled="store.isLoading"
            >
              {{ store.isLoading ? 'Menyimpan…' : (isEditing ? 'Perbarui' : 'Simpan') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { useSupplierStore } from '../stores/use_supplier_store';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    supplier: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);
const store = useSupplierStore();

const isEditing = computed(() => !!props.supplier);

const form = reactive({
    code: '',
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    tax_number: '',
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        store.clearMessages();
        if (props.supplier) {
            form.code = props.supplier.code ?? '';
            form.name = props.supplier.name ?? '';
            form.contact_person = props.supplier.contact_person ?? '';
            form.phone = props.supplier.phone ?? '';
            form.email = props.supplier.email ?? '';
            form.address = props.supplier.address ?? '';
            form.tax_number = props.supplier.tax_number ?? '';
        } else {
            Object.assign(form, { code: '', name: '', contact_person: '', phone: '', email: '', address: '', tax_number: '' });
        }
    }
});

const handleSubmit = async () => {
    const success = isEditing.value
        ? await store.updateSupplier(props.supplier.id, form)
        : await store.createSupplier(form);

    if (success) {
        emit('saved');
        onClose();
    }
};

const onClose = () => {
    if (!store.isLoading) emit('close');
};
</script>
