<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <svg
            class="w-5 h-5 text-amber-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
            />
          </svg>
          Computed Operational Alert Center
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          Peringatan otomatis yang dihitung secara real-time berdasarkan kondisi persediaan saat ini.
        </p>
      </div>
      <span
        v-if="alerts.length > 0"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300"
      >
        {{ alerts.length }} Peringatan
      </span>
    </div>

    <!-- Empty State -->
    <div
      v-if="!alerts || alerts.length === 0"
      class="flex items-center gap-3 p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-lg text-emerald-800 dark:text-emerald-300"
    >
      <svg
        class="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
      <div class="text-xs">
        <span class="font-bold">Semua Operasi Normal.</span> Tidak ada kondisi kritis atau peringatan stok yang membutuhkan tindakan segera.
      </div>
    </div>

    <!-- Alert List -->
    <div
      v-else
      class="space-y-3"
    >
      <div
        v-for="(alert, idx) in alerts"
        :key="idx"
        :class="[
          'p-3.5 rounded-lg border flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-colors',
          severityClasses(alert.severity)
        ]"
      >
        <div class="flex items-start gap-3">
          <div :class="['w-2 h-2 rounded-full mt-1.5 flex-shrink-0', severityDot(alert.severity)]" />
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-gray-900 dark:text-white">{{ alert.title }}</span>
              <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider', severityBadge(alert.severity)]">
                {{ alert.severity }} ({{ alert.count }})
              </span>
            </div>
            <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
              {{ alert.message }}
            </p>
          </div>
        </div>

        <button
          v-if="alert.route_name"
          type="button"
          class="self-end sm:self-center px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-xs font-medium rounded-md shadow-sm transition-colors flex-shrink-0"
          @click="navigateToRoute(alert.route_name)"
        >
          Tindak Lanjuti &rarr;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';

defineProps({
  alerts: {
    type: Array,
    default: () => [],
  },
});

const router = useRouter();

const navigateToRoute = (routeName) => {
  if (router && routeName) {
    // Map backend route_name hint to Vue router path if needed
    const routeMap = {
      'inventory-balances.index': '/inventory-balances',
      'reports.low-stock': '/reports/low-stock',
      'stock-transfers.index': '/stock-transfers',
      'stock-adjustments.index': '/stock-adjustments',
      'stock-opnames.index': '/stock-opnames',
      'locations.index': '/locations',
    };
    const targetPath = routeMap[routeName] || `/${routeName.replace('.index', '')}`;
    router.push(targetPath);
  }
};

const severityClasses = (severity) => {
  switch (severity) {
    case 'CRITICAL':
      return 'bg-rose-50/70 dark:bg-rose-950/30 border-rose-200 dark:border-rose-900/60';
    case 'WARNING':
      return 'bg-amber-50/70 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900/60';
    default:
      return 'bg-blue-50/70 dark:bg-blue-950/30 border-blue-200 dark:border-blue-900/60';
  }
};

const severityDot = (severity) => {
  switch (severity) {
    case 'CRITICAL':
      return 'bg-rose-500 animate-pulse';
    case 'WARNING':
      return 'bg-amber-500';
    default:
      return 'bg-blue-500';
  }
};

const severityBadge = (severity) => {
  switch (severity) {
    case 'CRITICAL':
      return 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300';
    case 'WARNING':
      return 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300';
    default:
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300';
  }
};
</script>
