<template>
  <div
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="reopen-dialog-title"
  >
    <div
      class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
      aria-hidden="true"
      @click="$emit('cancel')"
    />
    <div class="flex min-h-screen items-center justify-center px-4 py-8">
      <div class="relative w-full max-w-md rounded-lg bg-white shadow-xl">
        <div class="px-6 pt-6 pb-4">
          <h3
            id="reopen-dialog-title"
            class="text-base font-semibold leading-6 text-gray-900"
          >
            Buka Kembali Sesi Opname
          </h3>
          <div class="mt-3 space-y-3 text-sm text-gray-600">
            <p>
              Sesi opname akan dikembalikan ke status <strong>Sedang Dihitung</strong>.
              Lokasi persediaan tetap dibekukan selama proses berlangsung.
            </p>
            <p>
              Hasil hitung sebelumnya tetap tersimpan dalam riwayat audit.
              Selisih (variance) akan dihitung ulang setelah Complete berikutnya.
            </p>
          </div>

          <div class="mt-4">
            <label
              for="reopen-reason"
              class="block text-sm font-medium text-gray-700"
            >
              Alasan Pembukaan Kembali
              <span
                class="text-red-500"
                aria-hidden="true"
              >*</span>
            </label>
            <textarea
              id="reopen-reason"
              v-model="reason"
              rows="3"
              placeholder="Jelaskan alasan mengapa opname dibuka kembali..."
              class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              :class="{ 'border-red-500': error }"
              aria-describedby="reopen-reason-error"
              @input="error = ''"
            />
            <p
              v-if="error"
              id="reopen-reason-error"
              class="mt-1 text-sm text-red-600"
            >
              {{ error }}
            </p>
          </div>
        </div>

        <div class="flex flex-row-reverse gap-2 rounded-b-lg bg-gray-50 px-6 py-4">
          <button
            type="button"
            :disabled="loading"
            class="inline-flex justify-center rounded-md bg-yellow-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-600"
            @click="handleConfirm"
          >
            {{ loading ? 'Memproses...' : 'Buka Kembali' }}
          </button>
          <button
            type="button"
            :disabled="loading"
            class="inline-flex justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50"
            @click="$emit('cancel')"
          >
            Batal
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);

const reason = ref('');
const error = ref('');

function handleConfirm() {
    const trimmed = reason.value.trim();
    if (!trimmed) {
        error.value = 'Alasan pembukaan kembali wajib diisi.';
        return;
    }
    emit('confirm', { reason: trimmed });
}
</script>
