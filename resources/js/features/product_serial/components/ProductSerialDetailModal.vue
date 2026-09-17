<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
    @click.self="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150"
    >
      <!-- Modal Header -->
      <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-base font-bold tracking-tight text-white font-mono">
                {{ serial?.serial_number || 'Detail Serial Number' }}
              </h2>
              <button
                type="button"
                title="Salin Serial Number"
                class="text-slate-400 hover:text-white transition-colors p-1 rounded"
                @click="copyToClipboard(serial?.serial_number)"
              >
                <svg v-if="copied" class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </button>
            </div>
            <p class="text-xs text-slate-300">
              {{ serial?.product?.sku }} &bull; {{ serial?.product?.name }}
            </p>
          </div>
        </div>

        <button
          type="button"
          class="text-slate-400 hover:text-white hover:bg-white/10 p-1.5 rounded-lg transition-colors"
          @click="$emit('close')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-5 overflow-y-auto space-y-5 custom-scrollbar flex-1">
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Status Card -->
          <div class="rounded-xl p-3.5 border border-slate-200 bg-slate-50/50">
            <p class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Status Siklus Hidup</p>
            <div class="mt-1 flex items-center gap-2">
              <span
                class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold"
                :class="statusBadgeClass(serial?.status)"
              >
                {{ serial?.status_label || serial?.status }}
              </span>
              <span
                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium"
                :class="serial?.current_condition === 'GOOD' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
              >
                {{ serial?.current_condition === 'GOOD' ? 'Bagus (GOOD)' : 'Rusak (DEFECTIVE)' }}
              </span>
            </div>
          </div>

          <!-- Current Location / Store Card -->
          <div class="rounded-xl p-3.5 border border-slate-200 bg-slate-50/50">
            <p class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Posisi Saat Ini</p>
            <p class="mt-1 text-xs font-semibold text-slate-900">
              <template v-if="serial?.current_store">
                <span class="text-blue-700">Toko: {{ serial.current_store.name }}</span>
                <span class="block text-[10px] text-slate-500 font-normal truncate">{{ serial.current_store.address }}</span>
              </template>
              <template v-else-if="serial?.current_location">
                <span class="text-slate-800">Gudang/Pos: {{ serial.current_location.name }}</span>
                <span class="block text-[10px] text-slate-500 font-normal">Kode: {{ serial.current_location.code }}</span>
              </template>
              <template v-else>
                <span class="text-slate-400 italic">Tidak terikat lokasi internal</span>
              </template>
            </p>
          </div>

          <!-- Unit Info Card -->
          <div class="rounded-xl p-3.5 border border-slate-200 bg-slate-50/50">
            <p class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Terakhir Diperbarui</p>
            <p class="mt-1 text-xs font-medium text-slate-900">
              {{ formatDateTime(serial?.updated_at) }}
            </p>
            <p class="text-[10px] text-slate-500">
              Terdaftar sejak: {{ formatDateTime(serial?.created_at) }}
            </p>
          </div>
        </div>

        <!-- Notes if present -->
        <div v-if="serial?.notes" class="rounded-xl p-3 bg-amber-50 border border-amber-200 text-xs text-amber-900">
          <span class="font-semibold">Catatan Kondisi Terakhir:</span> {{ serial.notes }}
        </div>

        <!-- Vertical Timeline Section -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Riwayat Perjalanan Unit (Lifecycle Movements)
            </h3>
            <span class="text-[11px] text-slate-500 font-medium">
              Total {{ serial?.movements?.length || 0 }} mutasi tercatat
            </span>
          </div>

          <!-- Timeline Container -->
          <div v-if="serial?.movements && serial.movements.length > 0" class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
            <div
              v-for="(m, idx) in serial.movements"
              :key="m.id || idx"
              class="relative group"
            >
              <!-- Timeline Dot -->
              <div
                class="absolute -left-6 top-1 w-5 h-5 rounded-full border-2 border-white flex items-center justify-center shadow-xs text-white text-[9px] font-bold"
                :class="movementDotClass(m.movement_type)"
              >
                {{ serial.movements.length - idx }}
              </div>

              <!-- Content Box -->
              <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-2xs hover:border-indigo-300 transition-colors">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1.5">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold"
                      :class="movementBadgeClass(m.movement_type)"
                    >
                      {{ m.movement_type_label || m.movement_type }}
                    </span>
                    <span v-if="m.reference_number" class="text-[11px] font-mono font-medium text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200/60">
                      {{ m.reference_number }}
                    </span>
                  </div>
                  <span class="text-[11px] text-slate-400 font-medium whitespace-nowrap">
                    {{ formatDateTime(m.created_at) }}
                  </span>
                </div>

                <!-- Source to Destination Transition -->
                <div class="text-xs text-slate-700 mt-2 space-y-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-slate-500">Asal:</span>
                    <span class="font-medium text-slate-900">
                      {{ formatNode(m.source_store, m.source_location, '—') }}
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                    <span class="text-slate-500">Tujuan:</span>
                    <span class="font-medium text-slate-900">
                      {{ formatNode(m.destination_store, m.destination_location, '—') }}
                    </span>
                  </div>

                  <!-- Status & Condition Changes -->
                  <div class="flex items-center gap-3 text-[11px] text-slate-600 pt-1 flex-wrap">
                    <span v-if="m.to_status">
                      Status Menjadi:
                      <strong class="text-slate-800">{{ m.to_status_label || m.to_status }}</strong>
                    </span>
                    <span v-if="m.to_condition">
                      Kondisi:
                      <strong :class="m.to_condition === 'GOOD' ? 'text-emerald-700' : 'text-rose-700'">
                        {{ m.to_condition }}
                      </strong>
                    </span>
                    <span v-if="m.user" class="text-slate-500">
                      Oleh: <strong class="text-slate-700">{{ m.user.name }}</strong>
                    </span>
                  </div>
                </div>

                <!-- Notes -->
                <div v-if="m.notes" class="mt-2 text-[11px] text-slate-600 bg-slate-50 rounded-lg p-2 border border-slate-150">
                  <span class="font-semibold text-slate-700">Keterangan:</span> {{ m.notes }}
                </div>
              </div>
            </div>
          </div>

          <!-- Empty movements state -->
          <div v-else class="text-center py-8 border border-dashed border-slate-200 rounded-xl bg-slate-50/50">
            <p class="text-xs text-slate-500">Belum ada riwayat pergerakan yang tercatat untuk serial number ini.</p>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
        <button
          type="button"
          class="rounded-lg border border-slate-300 bg-white px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs transition-colors"
          @click="$emit('close')"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    serial: {
        type: Object,
        default: null,
    },
});

defineEmits(['close']);

const copied = ref(false);

function copyToClipboard(text) {
    if (!text) return;
    navigator.clipboard.writeText(text);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

function formatDateTime(dateStr) {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
}

function formatNode(store, location, fallback = '—') {
    if (store && store.name) return `Toko ${store.name}`;
    if (location && location.name) return location.name;
    return fallback;
}

function statusBadgeClass(status) {
    switch (status) {
        case 'IN_STOCK':
            return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
        case 'INSTALLED':
            return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'DEFECTIVE':
            return 'bg-rose-100 text-rose-800 border border-rose-200';
        case 'RETURNED_TO_VENDOR':
            return 'bg-purple-100 text-purple-800 border border-purple-200';
        case 'DISPOSED':
            return 'bg-slate-200 text-slate-800 border border-slate-300';
        default:
            return 'bg-slate-100 text-slate-700';
    }
}

function movementBadgeClass(type) {
    switch (type) {
        case 'INITIAL_REGISTRATION':
            return 'bg-slate-100 text-slate-800 border border-slate-200';
        case 'STORE_ALLOCATION_INSTALL':
            return 'bg-blue-50 text-blue-700 border border-blue-200';
        case 'STORE_ALLOCATION_PULL':
            return 'bg-rose-50 text-rose-700 border border-rose-200';
        case 'TRANSFER':
            return 'bg-amber-50 text-amber-700 border border-amber-200';
        case 'RECEIPT':
            return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
        case 'RMA_DISPATCH':
        case 'RMA_RETURN':
            return 'bg-purple-50 text-purple-700 border border-purple-200';
        default:
            return 'bg-slate-100 text-slate-700';
    }
}

function movementDotClass(type) {
    switch (type) {
        case 'INITIAL_REGISTRATION':
            return 'bg-slate-500';
        case 'STORE_ALLOCATION_INSTALL':
            return 'bg-blue-600';
        case 'STORE_ALLOCATION_PULL':
            return 'bg-rose-600';
        case 'TRANSFER':
            return 'bg-amber-500';
        case 'RECEIPT':
            return 'bg-emerald-600';
        case 'RMA_DISPATCH':
        case 'RMA_RETURN':
            return 'bg-purple-600';
        default:
            return 'bg-slate-500';
    }
}
</script>
