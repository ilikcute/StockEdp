<template>
  <div
    v-if="log"
    class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
    @click.self="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150"
      role="dialog"
      aria-modal="true"
    >
      <!-- Header -->
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/70">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
            #{{ log.id }}
          </div>
          <div>
            <h3 class="text-sm font-bold text-gray-900 leading-tight">
              Rincian Log Aktivitas
            </h3>
            <p class="text-[11px] text-gray-500">
              Audit Trail Record &bull; {{ formatDate(log.created_at) }}
            </p>
          </div>
        </div>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
          @click="$emit('close')"
        >
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
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-5 space-y-4 overflow-y-auto text-xs">
        <!-- Main Info Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-gray-50 p-3.5 rounded-xl border border-gray-100">
          <div>
            <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-400 block mb-0.5">Aktor / Pengguna</span>
            <div class="font-semibold text-gray-900 text-xs">
              {{ log.user ? log.user.name : (log.user_id ? `User ID #${log.user_id}` : 'Sistem / Tamu') }}
            </div>
            <div
              v-if="log.user?.username"
              class="text-[11px] text-gray-500 font-mono"
            >
              @{{ log.user.username }}
            </div>
          </div>

          <div>
            <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-400 block mb-0.5">Modul & Aksi</span>
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="px-2 py-0.5 rounded-md font-semibold text-[11px] bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                {{ log.module }}
              </span>
              <span class="px-2 py-0.5 rounded-md font-semibold text-[11px] bg-slate-100 text-slate-800 border border-slate-200">
                {{ log.action }}
              </span>
            </div>
          </div>

          <div>
            <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-400 block mb-0.5">Alamat IP</span>
            <div class="font-mono text-[11px] text-gray-800">
              {{ log.ip_address || '-' }}
            </div>
          </div>

          <div>
            <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-400 block mb-0.5">Entitas Subjek</span>
            <div
              class="text-[11px] text-gray-700 truncate"
              :title="log.subject_type"
            >
              {{ formatSubject(log.subject_type, log.subject_id) }}
            </div>
          </div>
        </div>

        <!-- Deskripsi -->
        <div>
          <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-400 block mb-1">Deskripsi</span>
          <div class="p-3 bg-white rounded-lg border border-gray-200 text-gray-800 leading-relaxed font-normal">
            {{ log.description || 'Tidak ada deskripsi.' }}
          </div>
        </div>

        <!-- User Agent -->
        <div v-if="log.user_agent">
          <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-400 block mb-1">User Agent (Browser / Client)</span>
          <div class="p-2 bg-gray-50 rounded-lg border border-gray-100 text-[10px] font-mono text-gray-600 break-all">
            {{ log.user_agent }}
          </div>
        </div>

        <!-- Properties JSON -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-400">Data Tambahan (Properties / Payload)</span>
            <button
              v-if="log.properties && Object.keys(log.properties).length > 0"
              type="button"
              class="text-[10px] text-indigo-600 hover:text-indigo-800 font-semibold cursor-pointer"
              @click="copyJson"
            >
              {{ copied ? 'Disalin!' : 'Salin JSON' }}
            </button>
          </div>
          <div
            v-if="log.properties && Object.keys(log.properties).length > 0"
            class="bg-slate-900 text-slate-100 p-3 rounded-xl font-mono text-[11px] overflow-x-auto max-h-60"
          >
            <pre>{{ formattedJson }}</pre>
          </div>
          <div
            v-else
            class="p-3 bg-gray-50 rounded-lg border border-dashed border-gray-200 text-gray-400 text-center text-[11px]"
          >
            Tidak ada data properti tambahan.
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/70 flex justify-end">
        <button
          type="button"
          class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 transition-colors cursor-pointer"
          @click="$emit('close')"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    log: {
        type: Object,
        default: null,
    },
});

defineEmits(['close']);

const copied = ref(false);

const formattedJson = computed(() => {
    if (!props.log?.properties) return '';
    try {
        return JSON.stringify(props.log.properties, null, 2);
    } catch {
        return String(props.log.properties);
    }
});

const copyJson = () => {
    if (!formattedJson.value) return;
    navigator.clipboard.writeText(formattedJson.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const formatDate = (val) => {
    if (!val) return '-';
    try {
        const d = new Date(val);
        return d.toLocaleString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch {
        return val;
    }
};

const formatSubject = (type, id) => {
    if (!type) return '-';
    const shortName = type.split('\\').pop();
    return id ? `${shortName} #${id}` : shortName;
};
</script>
