<template>
  <div class="rounded-lg bg-white p-4 shadow mb-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
      <div>
        <label class="block text-xs font-medium text-gray-700">Arah Selisih</label>
        <select
          :value="filters.variance_direction"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="emit('update:filter', 'variance_direction', $event.target.value)"
        >
          <option value="">
            Semua Arah Selisih
          </option>
          <option value="POSITIVE">
            POSITIVE (Selisih Positif)
          </option>
          <option value="NEGATIVE">
            NEGATIVE (Selisih Negatif)
          </option>
          <option value="ZERO">
            ZERO (Nol / Sesuai)
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Filter Product Unexpected</label>
        <select
          :value="filters.is_unexpected"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="emit('update:filter', 'is_unexpected', $event.target.value)"
        >
          <option value="">
            Semua Produk
          </option>
          <option value="1">
            Hanya Produk Tak Terduga (Unexpected = 1)
          </option>
          <option value="0">
            Hanya Produk Terdaftar (Unexpected = 0)
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Pencarian Teks</label>
        <input
          :value="filters.search"
          type="text"
          placeholder="Cari nomor opname..."
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @input="emit('update:filter', 'search', $event.target.value)"
        >
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Lokasi</label>
        <select
          :value="filters.location_id"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="emit('update:filter', 'location_id', $event.target.value)"
        >
          <option value="">
            Semua Lokasi
          </option>
          <option
            v-for="loc in masterStore.locations"
            :key="loc.id"
            :value="loc.id"
          >
            {{ loc.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Kategori</label>
        <select
          :value="filters.category_id"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="emit('update:filter', 'category_id', $event.target.value)"
        >
          <option value="">
            Semua Kategori
          </option>
          <option
            v-for="cat in masterStore.categories"
            :key="cat.id"
            :value="cat.id"
          >
            {{ cat.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Satuan</label>
        <select
          :value="filters.unit_id"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="emit('update:filter', 'unit_id', $event.target.value)"
        >
          <option value="">
            Semua Satuan
          </option>
          <option
            v-for="u in masterStore.units"
            :key="u.id"
            :value="u.id"
          >
            {{ u.name }}
          </option>
        </select>
      </div>

      <div class="relative">
        <label class="block text-xs font-medium text-gray-700">Produk</label>
        <input
          v-model="productSearch"
          type="text"
          placeholder="Cari produk (min 2 karakter)..."
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @input="$emit('product-search', productSearch)"
          @focus="showProductDropdown = true"
        >
        <div
          v-if="showProductDropdown && masterStore.products.length > 0"
          class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
        >
          <div
            v-for="prod in masterStore.products"
            :key="prod.id"
            class="cursor-pointer px-3 py-1 text-xs hover:bg-indigo-50"
            @click="onSelectProduct(prod)"
          >
            {{ prod.name }} ({{ prod.sku }})
          </div>
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Tanggal Mulai</label>
        <input
          :value="filters.start_date"
          type="date"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @input="emit('update:filter', 'start_date', $event.target.value)"
        >
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Tanggal Akhir</label>
        <input
          :value="filters.end_date"
          type="date"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @input="emit('update:filter', 'end_date', $event.target.value)"
        >
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Urutkan Berdasarkan</label>
        <select
          :value="filters.sort_by"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="emit('update:filter', 'sort_by', $event.target.value)"
        >
          <option value="posted_at">
            Waktu Posting
          </option>
          <option value="opname_date">
            Tanggal Dokumen
          </option>
          <option value="opname_number">
            Nomor Opname
          </option>
          <option value="id">
            ID Item
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Arah Urutan</label>
        <select
          :value="filters.sort_order"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="emit('update:filter', 'sort_order', $event.target.value)"
        >
          <option value="desc">
            Terbaru / Descending
          </option>
          <option value="asc">
            Terlama / Ascending
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Per Halaman</label>
        <select
          :value="filters.per_page"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="emit('update:filter', 'per_page', $event.target.value)"
        >
          <option value="15">
            15 Baris
          </option>
          <option value="25">
            25 Baris
          </option>
          <option value="50">
            50 Baris
          </option>
        </select>
      </div>

      <div class="flex items-end">
        <button
          type="button"
          class="w-full rounded-md bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-200"
          @click="$emit('reset')"
        >
          Reset Filter
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    filters: { type: Object, required: true },
    masterStore: { type: Object, required: true },
});

const emit = defineEmits(['update:filter', 'product-search', 'select-product', 'reset']);

const productSearch = ref('');
const showProductDropdown = ref(false);

const onSelectProduct = (prod) => {
    productSearch.value = prod.name;
    showProductDropdown.value = false;
    emit('select-product', prod);
};
</script>
