<template>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
    <!-- 1. Total Low Stock -->
    <div class="p-4 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
      <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
        Total Stok Kurang
      </div>
      <div class="text-2xl font-bold text-slate-900 dark:text-slate-100 mt-1">
        {{ summary.low_stock_product_count }}
      </div>
      <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
        Produk di bawah min
      </div>
    </div>

    <!-- 2. Covered Inbound -->
    <div class="p-4 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 shadow-sm">
      <div class="text-xs font-medium text-blue-700 dark:text-blue-300">
        Ditutup Inbound
      </div>
      <div class="text-2xl font-bold text-blue-800 dark:text-blue-200 mt-1">
        {{ summary.inbound_covered_count }}
      </div>
      <div class="text-[11px] text-blue-600/70 dark:text-blue-400/70 mt-0.5">
        Transfer SENT aktif
      </div>
    </div>

    <!-- 3. Internal Transfer -->
    <div class="p-4 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 shadow-sm">
      <div class="text-xs font-medium text-emerald-700 dark:text-emerald-300">
        Transfer Internal
      </div>
      <div class="text-2xl font-bold text-emerald-800 dark:text-emerald-200 mt-1">
        {{ summary.internal_transfer_count }}
      </div>
      <div class="text-[11px] text-emerald-600/70 dark:text-emerald-400/70 mt-0.5">
        Penuh surplus internal
      </div>
    </div>

    <!-- 4. Mixed -->
    <div class="p-4 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 shadow-sm">
      <div class="text-xs font-medium text-amber-700 dark:text-amber-300">
        Sebagian Transfer
      </div>
      <div class="text-2xl font-bold text-amber-800 dark:text-amber-200 mt-1">
        {{ summary.mixed_count }}
      </div>
      <div class="text-[11px] text-amber-600/70 dark:text-amber-400/70 mt-0.5">
        Transfer + Reorder
      </div>
    </div>

    <!-- 5. External Reorder -->
    <div class="p-4 rounded-xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 shadow-sm">
      <div class="text-xs font-medium text-rose-700 dark:text-rose-300">
        Reorder Eksternal
      </div>
      <div class="text-2xl font-bold text-rose-800 dark:text-rose-200 mt-1">
        {{ summary.external_reorder_count }}
      </div>
      <div class="text-[11px] text-rose-600/70 dark:text-rose-400/70 mt-0.5">
        Tanpa surplus gudang
      </div>
    </div>

    <!-- 6. Critical (0 / Minus) -->
    <div class="p-4 rounded-xl bg-purple-50/60 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800 shadow-sm">
      <div class="text-xs font-medium text-purple-700 dark:text-purple-300">
        Stok Kritis
      </div>
      <div class="text-2xl font-bold text-purple-800 dark:text-purple-200 mt-1">
        {{ summary.critical_product_count }}
      </div>
      <div class="text-[11px] text-purple-600/70 dark:text-purple-400/70 mt-0.5">
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
