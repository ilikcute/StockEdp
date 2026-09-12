<template>
  <div
    v-if="canViewReplenishment"
    class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs transition-shadow hover:shadow-md"
  >
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0 shadow-2xs">
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
            />
          </svg>
        </div>

        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-gray-900">
              Action Center: Rekomendasi Replenishment & Reorder
            </h3>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800">
              Live Engine
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-0.5">
            Deteksi defisit stok otomatis, alokasi surplus antar gudang, dan persiapan transfer satu klik.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <router-link
          :to="{
            path: '/inventory/replenishment',
            query: locationId ? { location_id: locationId } : {}
          }"
          class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-500 shadow-xs transition-colors min-h-[40px] focus:outline-none focus:ring-2 focus:ring-indigo-600 cursor-pointer"
        >
          <span>Buka Action Center</span>
          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M14 5l7 7m0 0l-7 7m7-7H3"
            />
          </svg>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@features/auth/stores/use_auth_store.js';

defineProps({
  locationId: {
    type: [Number, String],
    default: null,
  },
});

const authStore = useAuthStore();
const canViewReplenishment = computed(() => authStore.hasPermission('replenishment.view'));
</script>
