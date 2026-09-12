<template>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
    <!-- 1. Total Low Stock -->
    <div class="p-4 rounded-xl bg-white border border-gray-200 shadow-xs">
      <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">
        Total Stok Kurang
      </div>
      <div class="text-2xl font-bold text-gray-900 mt-1">
        {{ summary.low_stock_product_count || 0 }}
      </div>
      <div class="text-[11px] text-gray-400 mt-0.5">
        Produk di bawah min
      </div>
    </div>

    <!-- 2. Covered Inbound -->
    <div class="p-4 rounded-xl bg-white border border-blue-200 shadow-xs">
      <div class="text-xs font-medium text-blue-600 uppercase tracking-wider">
        Ditutup Inbound
      </div>
      <div class="text-2xl font-bold text-gray-900 mt-1">
        {{ summary.inbound_covered_count || 0 }}
      </div>
      <div class="text-[11px] text-blue-600 font-medium mt-0.5">
        Transfer SENT aktif
      </div>
    </div>

    <!-- 3. Internal Transfer -->
    <div class="p-4 rounded-xl bg-white border border-emerald-200 shadow-xs">
      <div class="text-xs font-medium text-emerald-600 uppercase tracking-wider">
        Transfer Internal
      </div>
      <div class="text-2xl font-bold text-gray-900 mt-1">
        {{ summary.internal_transfer_count || 0 }}
      </div>
      <div class="text-[11px] text-emerald-600 font-medium mt-0.5">
        Penuh surplus internal
      </div>
    </div>

    <!-- 4. Mixed -->
    <div class="p-4 rounded-xl bg-white border border-amber-200 shadow-xs">
      <div class="text-xs font-medium text-amber-600 uppercase tracking-wider">
        Sebagian Transfer
      </div>
      <div class="text-2xl font-bold text-gray-900 mt-1">
        {{ summary.mixed_count || 0 }}
      </div>
      <div class="text-[11px] text-amber-600 font-medium mt-0.5">
        Transfer + Reorder
      </div>
    </div>

    <!-- 5. External Reorder -->
    <div class="p-4 rounded-xl bg-white border border-rose-200 shadow-xs">
      <div class="text-xs font-medium text-rose-600 uppercase tracking-wider">
        Reorder Eksternal
      </div>
      <div class="text-2xl font-bold text-gray-900 mt-1">
        {{ summary.external_reorder_count || 0 }}
      </div>
      <div class="text-[11px] text-rose-600 font-medium mt-0.5">
        Tanpa surplus gudang
      </div>
    </div>

    <!-- 6. Critical (0 / Minus) -->
    <div class="p-4 rounded-xl bg-white border border-purple-200 shadow-xs">
      <div class="text-xs font-medium text-purple-600 uppercase tracking-wider">
        Stok Kritis
      </div>
      <div class="text-2xl font-bold text-gray-900 mt-1">
        {{ summary.critical_product_count || 0 }}
      </div>
      <div class="text-[11px] text-purple-600 font-medium mt-0.5">
        Saldo 0 atau minus
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  summary: {
    type: Object,
    required: true,
  },
});
</script>
