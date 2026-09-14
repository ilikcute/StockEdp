<template>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
    <!-- 1. Total Low Stock -->
    <div class="px-3 py-2 rounded-xl bg-white border border-gray-200 shadow-2xs">
      <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">
        Total Stok Kurang
      </div>
      <div class="text-lg font-bold text-gray-900 mt-0.5 font-mono">
        {{ summary.low_stock_product_count || 0 }}
      </div>
      <div class="text-[10px] text-gray-400 mt-0.5 leading-none">
        Produk di bawah min
      </div>
    </div>

    <!-- 2. Covered Inbound -->
    <div class="px-3 py-2 rounded-xl bg-white border border-blue-200/80 shadow-2xs">
      <div class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">
        Ditutup Inbound
      </div>
      <div class="text-lg font-bold text-blue-900 mt-0.5 font-mono">
        {{ summary.inbound_covered_count || 0 }}
      </div>
      <div class="text-[10px] text-blue-600 font-medium mt-0.5 leading-none">
        Transfer in-transit
      </div>
    </div>

    <!-- 3. Internal Transfer -->
    <div class="px-3 py-2 rounded-xl bg-white border border-emerald-200/80 shadow-2xs">
      <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">
        Transfer Internal
      </div>
      <div class="text-lg font-bold text-emerald-900 mt-0.5 font-mono">
        {{ summary.internal_transfer_count || 0 }}
      </div>
      <div class="text-[10px] text-emerald-600 font-medium mt-0.5 leading-none">
        Surplus internal
      </div>
    </div>

    <!-- 4. Mixed -->
    <div class="px-3 py-2 rounded-xl bg-white border border-amber-200/80 shadow-2xs">
      <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">
        Sebagian Transfer
      </div>
      <div class="text-lg font-bold text-amber-900 mt-0.5 font-mono">
        {{ summary.mixed_count || 0 }}
      </div>
      <div class="text-[10px] text-amber-600 font-medium mt-0.5 leading-none">
        Transfer + Reorder
      </div>
    </div>

    <!-- 5. External Reorder -->
    <div class="px-3 py-2 rounded-xl bg-white border border-rose-200/80 shadow-2xs">
      <div class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">
        Reorder Eksternal
      </div>
      <div class="text-lg font-bold text-rose-900 mt-0.5 font-mono">
        {{ summary.external_reorder_count || 0 }}
      </div>
      <div class="text-[10px] text-rose-600 font-medium mt-0.5 leading-none">
        Tanpa surplus gudang
      </div>
    </div>

    <!-- 6. Critical (0 / Minus) -->
    <div class="px-3 py-2 rounded-xl bg-white border border-purple-200/80 shadow-2xs">
      <div class="text-[10px] font-bold text-purple-600 uppercase tracking-wider">
        Stok Kritis
      </div>
      <div class="text-lg font-bold text-purple-900 mt-0.5 font-mono">
        {{ summary.critical_product_count || 0 }}
      </div>
      <div class="text-[10px] text-purple-600 font-medium mt-0.5 leading-none">
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
