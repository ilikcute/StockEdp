<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs">
    <!-- Header with Period Selector -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-4 border-b border-gray-100">
      <div>
        <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-600" />
          Intelijen Pergerakan Persediaan (Movement Intelligence)
        </h2>
        <p class="text-xs text-gray-500 mt-0.5">
          Analisis perputaran dan dormansi stok berdasarkan transaksi aktual.
        </p>
      </div>

      <div class="flex items-center gap-2 self-start sm:self-auto">
        <label
          for="movement-period-select"
          class="text-xs text-gray-500 font-medium whitespace-nowrap"
        >
          Periode Analisis:
        </label>
        <select
          id="movement-period-select"
          v-model="selectedPeriod"
          class="text-xs font-semibold text-gray-700 bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer min-h-[36px]"
          :disabled="localLoading"
          @change="onPeriodChange"
        >
          <option
            v-for="opt in periodOptions"
            :key="opt.value"
            :value="opt.value"
          >
            {{ opt.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Card 1: Slow Moving -->
      <component
        :is="canNavigate ? 'button' : 'div'"
        type="button"
        class="relative bg-slate-50/70 hover:bg-slate-50 rounded-xl border border-slate-200 p-4 text-left transition-all group flex flex-col justify-between min-h-[130px] focus:outline-none focus:ring-2 focus:ring-slate-400"
        :class="{ 'cursor-pointer hover:shadow-md hover:border-slate-300': canNavigate }"
        @click="navigateToMovement('slow-moving')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-slate-500" />
              Slow Moving
            </span>
            <span class="text-[11px] font-medium text-slate-500 bg-white px-2 py-0.5 rounded-full border border-slate-200">
              {{ selectedPeriod }} Hari
            </span>
          </div>

          <div class="mt-3 flex items-baseline gap-2">
            <span
              id="stat-slow-moving-count"
              class="text-3xl font-extrabold text-slate-900"
            >
              {{ localLoading ? '...' : (counts.slow_moving_count ?? 0) }}
            </span>
            <span class="text-xs font-semibold text-slate-600">Produk</span>
          </div>

          <p class="text-xs text-slate-500 mt-1">
            Tidak ada pergerakan selama {{ selectedPeriod }} hari terakhir.
          </p>
        </div>

        <div
          v-if="canNavigate"
          class="mt-4 pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs font-semibold text-indigo-600 group-hover:text-indigo-800"
        >
          <span>Lihat Detail Slow Moving</span>
          <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
        </div>
      </component>

      <!-- Card 2: Fast Moving -->
      <component
        :is="canNavigate ? 'button' : 'div'"
        type="button"
        class="relative bg-emerald-50/50 hover:bg-emerald-50 rounded-xl border border-emerald-200 p-4 text-left transition-all group flex flex-col justify-between min-h-[130px] focus:outline-none focus:ring-2 focus:ring-emerald-500"
        :class="{ 'cursor-pointer hover:shadow-md hover:border-emerald-300': canNavigate }"
        @click="navigateToMovement('fast-moving')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500" />
              Fast Moving
            </span>
            <span class="text-[11px] font-medium text-emerald-700 bg-white px-2 py-0.5 rounded-full border border-emerald-200">
              {{ selectedPeriod }} Hari
            </span>
          </div>

          <div class="mt-3 flex items-baseline gap-2">
            <span
              id="stat-fast-moving-count"
              class="text-3xl font-extrabold text-emerald-950"
            >
              {{ localLoading ? '...' : (counts.fast_moving_count ?? 0) }}
            </span>
            <span class="text-xs font-semibold text-emerald-700">Produk</span>
          </div>

          <p class="text-xs text-emerald-600/90 mt-1">
            Perputaran sangat tinggi · {{ selectedPeriod }} hari terakhir.
          </p>
        </div>

        <div
          v-if="canNavigate"
          class="mt-4 pt-2 border-t border-emerald-200/60 flex items-center justify-between text-xs font-semibold text-emerald-700 group-hover:text-emerald-900"
        >
          <span>Lihat Detail Fast Moving</span>
          <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
        </div>
      </component>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import dashboardApi from '../api/dashboard_api';

const props = defineProps({
    movementData: {
        type: Object,
        default: () => ({}),
    },
    locationId: {
        type: [String, Number],
        default: '',
    },
});

const router = useRouter();
const authStore = useAuthStore();

const canNavigate = computed(() => {
    return authStore.hasPermission('reports.inventory_movement.view')
        || authStore.hasPermission('reports.view')
        || authStore.hasPermission('dashboard.view');
});

const periodOptions = [
    { value: 30, label: '30 Hari' },
    { value: 60, label: '60 Hari' },
    { value: 90, label: '90 Hari' },
    { value: 120, label: '120 Hari' },
    { value: 180, label: '180 Hari' },
    { value: 365, label: '365 Hari' },
];

const selectedPeriod = ref(90);
const localLoading = ref(false);
const counts = reactive({
    slow_moving_count: 0,
    fast_moving_count: 0,
});

watch(
    () => props.movementData,
    (newData) => {
        if (newData) {
            counts.slow_moving_count = newData.slow_moving_count ?? 0;
            counts.fast_moving_count = newData.fast_moving_count ?? 0;
            if (newData.period_days) {
                selectedPeriod.value = Number(newData.period_days);
            }
        }
    },
    { immediate: true }
);

watch(
    () => props.locationId,
    () => {
        fetchSummary();
    }
);

async function onPeriodChange() {
    await fetchSummary();
}

async function fetchSummary() {
    localLoading.value = true;
    try {
        const params = {
            period: selectedPeriod.value,
        };
        if (props.locationId) {
            params.location_id = props.locationId;
        }

        const response = await dashboardApi.getMovementSummary(params);
        if (response?.data?.data) {
            counts.slow_moving_count = response.data.data.slow_moving_count ?? 0;
            counts.fast_moving_count = response.data.data.fast_moving_count ?? 0;
        }
    } catch (err) {
        console.error('Failed to load inventory movement summary:', err);
    } finally {
        localLoading.value = false;
    }
}

function navigateToMovement(type) {
    if (!canNavigate.value) {
        return;
    }

    const query = {
        type,
        period: selectedPeriod.value,
    };

    if (props.locationId) {
        query.location_id = props.locationId;
    }

    router.push({
        path: '/reports/inventory-movement',
        query,
    });
}
</script>
