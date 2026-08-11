<template>
  <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
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
        <p class="text-xs text-gray-500 mt-0.5">
          Peringatan otomatis yang dihitung secara real-time berdasarkan kondisi persediaan saat ini.
        </p>
      </div>
      <span
        v-if="alerts.length > 0"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800"
      >
        {{ alerts.length }} Peringatan
      </span>
    </div>

    <!-- Empty State -->
    <div
      v-if="!alerts || alerts.length === 0"
      class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800"
    >
      <svg
        class="w-5 h-5 flex-shrink-0 text-emerald-600"
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
              <span class="text-xs font-bold text-gray-900">{{ alert.title }}</span>
              <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider', severityBadge(alert.severity)]">
                {{ alert.severity }} ({{ alert.count }})
              </span>
            </div>
            <p class="text-xs text-gray-600 mt-0.5">
              {{ alert.message }}
            </p>
          </div>
        </div>

        <button
          v-if="alert.route_name && canNavigate(alert.permission)"
          type="button"
          class="self-end sm:self-center px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-xs font-medium rounded-md shadow-xs transition-colors flex-shrink-0 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
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
import { useAuthStore } from '../../auth/stores/use_auth_store';

defineProps({
  alerts: {
    type: Array,
    default: () => [],
  },
});

const router = useRouter();
const authStore = useAuthStore();

const canNavigate = (permission) => {
  if (!permission) return true;
  return authStore && authStore.hasPermission ? authStore.hasPermission(permission) : false;
};

const navigateToRoute = (routeName) => {
  if (router && routeName) {
    router.push({ name: routeName });
  }
};

const severityClasses = (severity) => {
  switch (severity) {
    case 'CRITICAL':
      return 'bg-rose-50/70 border-rose-200';
    case 'WARNING':
      return 'bg-amber-50/70 border-amber-200';
    default:
      return 'bg-blue-50/70 border-blue-200';
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
      return 'bg-rose-100 text-rose-800';
    case 'WARNING':
      return 'bg-amber-100 text-amber-800';
    default:
      return 'bg-blue-100 text-blue-800';
  }
};
</script>
