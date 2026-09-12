<template>
  <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-3.5 flex flex-col justify-between h-full">
    <div>
      <div class="flex items-center justify-between mb-2.5">
        <div>
          <h3 class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-amber-500"
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
            Operational Alert Center
          </h3>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Peringatan otomatis berdasarkan kondisi real-time persediaan.
          </p>
        </div>
        <span
          v-if="alerts.length > 0"
          class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800"
        >
          {{ alerts.length }} Peringatan
        </span>
      </div>

      <!-- Empty State -->
      <div
        v-if="!alerts || alerts.length === 0"
        class="flex items-center gap-2.5 p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800"
      >
        <svg
          class="w-4 h-4 flex-shrink-0 text-emerald-600"
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
        <div class="text-[11px]">
          <span class="font-bold">Semua Operasi Normal.</span> Tidak ada kondisi kritis yang membutuhkan tindakan segera.
        </div>
      </div>

      <!-- Alert List (Scrollable if many) -->
      <div
        v-else
        class="space-y-2 max-h-[170px] overflow-y-auto pr-1 custom-scrollbar"
      >
        <div
          v-for="(alert, idx) in alerts"
          :key="idx"
          :class="[
            'p-2 rounded-lg border flex flex-col sm:flex-row sm:items-center justify-between gap-2 transition-colors',
            severityClasses(alert.severity)
          ]"
        >
          <div class="flex items-start gap-2 min-w-0">
            <div :class="['w-1.5 h-1.5 rounded-full mt-1.5 flex-shrink-0', severityDot(alert.severity)]" />
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="text-xs font-bold text-gray-900 truncate">{{ alert.title }}</span>
                <span :class="['px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider shrink-0', severityBadge(alert.severity)]">
                  {{ alert.count }}
                </span>
              </div>
              <p class="text-[11px] text-gray-600 mt-0.5 line-clamp-1">
                {{ alert.message }}
              </p>
            </div>
          </div>

          <button
            v-if="alert.route_name && canNavigate(alert.permission)"
            type="button"
            class="self-end sm:self-center px-2 py-1 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-[11px] font-semibold rounded shadow-xs transition-colors flex-shrink-0 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
            @click="navigateToRoute(alert.route_name)"
          >
            Tindak Lanjuti &rarr;
          </button>
        </div>
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
      return 'bg-rose-50/70 border-rose-200 text-rose-900';
    case 'WARNING':
      return 'bg-amber-50/70 border-amber-200 text-amber-900';
    case 'INFO':
    default:
      return 'bg-blue-50/70 border-blue-200 text-blue-900';
  }
};

const severityBadge = (severity) => {
  switch (severity) {
    case 'CRITICAL':
      return 'bg-rose-100 text-rose-800';
    case 'WARNING':
      return 'bg-amber-100 text-amber-800';
    case 'INFO':
    default:
      return 'bg-blue-100 text-blue-800';
  }
};

const severityDot = (severity) => {
  switch (severity) {
    case 'CRITICAL':
      return 'bg-rose-500';
    case 'WARNING':
      return 'bg-amber-500';
    case 'INFO':
    default:
      return 'bg-blue-500';
  }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 4px;
}
</style>
