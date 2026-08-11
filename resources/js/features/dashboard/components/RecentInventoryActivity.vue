<template>
  <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
          <svg
            class="w-5 h-5 text-indigo-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          Aktivitas Persediaan Terkini (Maks. 10 Total)
        </h3>
        <p class="text-xs text-gray-500 mt-0.5">
          Pergerakan stok fisik terbaru yang telah tercatat dalam sistem.
        </p>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="!activities || activities.length === 0"
      class="text-center py-8 text-gray-400 text-xs"
    >
      Belum ada aktivitas pergerakan stok pada lokasi terjangkau.
    </div>

    <!-- Table with No. Sequence Column -->
    <div
      v-else
      class="overflow-x-auto"
    >
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-200">
            <th class="py-2.5 px-3 w-12 text-center">
              No.
            </th>
            <th class="py-2.5 px-3">
              Waktu
            </th>
            <th class="py-2.5 px-3">
              Tipe Movement
            </th>
            <th class="py-2.5 px-3">
              No. Dokumen
            </th>
            <th class="py-2.5 px-3">
              SKU & Produk
            </th>
            <th class="py-2.5 px-3">
              Lokasi
            </th>
            <th class="py-2.5 px-3 text-right">
              Jumlah
            </th>
            <th class="py-2.5 px-3">
              Petugas
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="(item, index) in activities"
            :key="item.id || index"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-2.5 px-3 text-center text-gray-400 font-mono">
              {{ index + 1 }}
            </td>
            <td class="py-2.5 px-3 text-gray-600 whitespace-nowrap">
              {{ formatDate(item.occurred_at) }}
            </td>
            <td class="py-2.5 px-3 whitespace-nowrap">
              <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase', typeBadgeClass(item.type)]">
                {{ formatTypeLabel(item.type) }}
              </span>
            </td>
            <td class="py-2.5 px-3 font-mono text-gray-800 whitespace-nowrap">
              {{ item.reference_number || '-' }}
            </td>
            <td class="py-2.5 px-3">
              <div class="font-medium text-gray-900">
                {{ item.product_name }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                {{ item.product_sku }}
              </div>
            </td>
            <td class="py-2.5 px-3 text-gray-600 whitespace-nowrap">
              <span class="font-semibold">{{ item.location_code }}</span> — {{ item.location_name }}
            </td>
            <td class="py-2.5 px-3 text-right font-mono font-bold text-gray-900 whitespace-nowrap">
              {{ item.quantity ?? '0.0000' }} {{ item.unit_symbol }}
            </td>
            <td class="py-2.5 px-3 text-gray-500 whitespace-nowrap">
              {{ item.performed_by || 'System' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  activities: {
    type: Array,
    default: () => [],
  },
});

const formatDate = (isoString) => {
  if (!isoString) return '-';
  try {
    const d = new Date(isoString);
    return d.toLocaleString('id-ID', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return isoString;
  }
};

const formatTypeLabel = (type) => {
  const map = {
    RECEIPT: 'Penerimaan',
    ISSUE: 'Pengeluaran',
    TRANSFER_IN: 'Transfer Masuk',
    TRANSFER_OUT: 'Transfer Keluar',
    ADJUSTMENT_IN: 'Penyesuaian (+)',
    ADJUSTMENT_OUT: 'Penyesuaian (-)',
    OPNAME_IN: 'Opname (+)',
    OPNAME_OUT: 'Opname (-)',
    REVERSAL: 'Pembatalan',
  };
  return map[type] || type;
};

const typeBadgeClass = (type) => {
  if (['RECEIPT', 'TRANSFER_IN', 'ADJUSTMENT_IN', 'OPNAME_IN'].includes(type)) {
    return 'bg-emerald-100 text-emerald-800';
  }
  if (['ISSUE', 'TRANSFER_OUT', 'ADJUSTMENT_OUT', 'OPNAME_OUT'].includes(type)) {
    return 'bg-amber-100 text-amber-800';
  }
  return 'bg-gray-100 text-gray-800';
};
</script>
