<template>
  <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-xs flex flex-col justify-between h-full">
    <div>
      <!-- Header with Period Selector -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 mb-2.5 border-b border-gray-100">
        <div>
          <h2 class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-indigo-600" />
            Intelijen Pergerakan Persediaan
          </h2>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Analisis perputaran dan dormansi stok berdasarkan transaksi.
          </p>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto">
          <label
            for="movement-period-select"
            class="text-[11px] text-gray-500 font-medium whitespace-nowrap"
          >
            Periode:
          </label>
          <select
            id="movement-period-select"
            v-model="selectedPeriod"
            class="text-xs font-semibold text-gray-700 bg-gray-50 border border-gray-300 rounded-lg px-2 py-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer min-h-[30px]"
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
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
        <!-- Card 1: Slow Moving -->
        <component
          :is="canNavigate ? 'button' : 'div'"
          type="button"
          class="relative bg-slate-50/70 hover:bg-slate-50 rounded-lg border border-slate-200 p-2.5 text-left transition-all group flex flex-col justify-between min-h-[95px] focus:outline-none focus:ring-2 focus:ring-slate-400"
          :class="{ 'cursor-pointer hover:shadow-md hover:border-slate-300': canNavigate }"
          @click="navigateToMovement('slow-moving')"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-500" />
                Slow Moving
              </span>
              <span class="text-[10px] font-medium text-slate-500 bg-white px-1.5 py-0.2 rounded border border-slate-200">
                {{ selectedPeriod }} Hari
              </span>
            </div>

            <div class="mt-1.5 flex items-baseline gap-1.5">
              <span
                id="stat-slow-moving-count"
                class="text-xl font-extrabold text-slate-900"
              >
                {{ localLoading ? '...' : (counts.slow_moving_count ?? 0) }}
              </span>
              <span class="text-[11px] font-semibold text-slate-600">Produk</span>
            </div>

            <p class="text-[10px] text-slate-500 mt-0.5 truncate">
              Tidak ada pergerakan stok.
            </p>
          </div>

          <div
            v-if="canNavigate"
            class="mt-2 pt-1 border-t border-slate-200/60 flex items-center justify-between text-[11px] font-semibold text-indigo-600 group-hover:text-indigo-800"
          >
            <span>Lihat Detail</span>
            <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
          </div>
        </component>

        <!-- Card 2: Fast Moving -->
        <component
          :is="canNavigate ? 'button' : 'div'"
          type="button"
          class="relative bg-emerald-50/50 hover:bg-emerald-50 rounded-lg border border-emerald-200 p-2.5 text-left transition-all group flex flex-col justify-between min-h-[95px] focus:outline-none focus:ring-2 focus:ring-emerald-500"
          :class="{ 'cursor-pointer hover:shadow-md hover:border-emerald-300': canNavigate }"
          @click="navigateToMovement('fast-moving')"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                Fast Moving
              </span>
              <span class="text-[10px] font-medium text-emerald-700 bg-white px-1.5 py-0.2 rounded border border-emerald-200">
                {{ selectedPeriod }} Hari
              </span>
            </div>

            <div class="mt-1.5 flex items-baseline gap-1.5">
              <span
                id="stat-fast-moving-count"
                class="text-xl font-extrabold text-emerald-950"
              >
                {{ localLoading ? '...' : (counts.fast_moving_count ?? 0) }}
              </span>
              <span class="text-[11px] font-semibold text-emerald-700">Produk</span>
            </div>

            <p class="text-[10px] text-emerald-600/90 mt-0.5 truncate">
              Perputaran barang tinggi.
            </p>
          </div>

          <div
            v-if="canNavigate"
            class="mt-2 pt-1 border-t border-emerald-200/60 flex items-center justify-between text-[11px] font-semibold text-emerald-700 group-hover:text-emerald-900"
          >
            <span>Lihat Detail</span>
            <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
          </div>
        </component>
      </div>
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
