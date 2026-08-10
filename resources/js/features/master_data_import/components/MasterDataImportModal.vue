<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
    @click.self="handleClose"
  >
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
        <div>
          <h3 class="text-lg font-bold text-gray-900">
            {{ displayTitle }}
          </h3>
          <p class="text-xs text-gray-500 mt-0.5">
            Unggah file CSV untuk mengimpor data secara masal (CREATE ONLY)
          </p>
        </div>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 rounded-lg p-1.5 transition-colors cursor-pointer"
          :disabled="isCommitting || isValidating"
          @click="handleClose"
        >
          <span class="sr-only">Tutup</span>
          ✕
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto space-y-6 flex-1 text-sm">
        <!-- Instructions & Template Download -->
        <MasterDataImportInstructions
          :is-downloading="isDownloadingTemplate"
          @download="downloadTemplate"
        />

        <!-- File Upload Section -->
        <MasterDataImportUploader
          ref="uploaderRef"
          :selected-file="selectedFile"
          :is-validating="isValidating"
          :is-committing="isCommitting"
          @file-selected="onFileSelected"
          @validate="validateFile"
        />

        <!-- Global Error Notification -->
        <div
          v-if="generalError"
          class="p-4 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 flex items-start gap-2"
        >
          <span class="font-bold">Error:</span>
          <span>{{ generalError }}</span>
        </div>

        <!-- Validation Results Section -->
        <div
          v-if="validationResult"
          class="space-y-4 pt-2 border-t border-gray-100"
        >
          <MasterDataImportSummary :summary="validationResult" />

          <!-- Error List Table -->
          <MasterDataImportErrorTable
            v-if="validationResult.errors && validationResult.errors.length > 0"
            :errors="validationResult.errors"
          />

          <!-- Preview Table (Up to 20 rows) -->
          <MasterDataImportPreviewTable
            v-if="validationResult.preview && validationResult.preview.length > 0"
            :preview-rows="validationResult.preview"
            :columns="previewColumns"
          />
        </div>

        <!-- Success Result -->
        <div
          v-if="importSuccessResult"
          class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-900 space-y-2 text-center py-6"
        >
          <div class="text-3xl">
            🎉
          </div>
          <h4 class="font-bold text-base">
            Import Berhasil!
          </h4>
          <p class="text-xs text-green-800">
            Sebanyak <strong>{{ importSuccessResult.imported_rows }}</strong> data {{ typeLabel }} berhasil disimpan ke database.
          </p>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-3">
        <button
          type="button"
          class="px-4 py-2 text-xs font-semibold text-gray-700 hover:text-gray-900 bg-white hover:bg-gray-100 border border-gray-200 rounded-xl transition-colors cursor-pointer"
          :disabled="isCommitting || isValidating"
          @click="handleClose"
        >
          {{ importSuccessResult ? 'Tutup' : 'Batal' }}
        </button>

        <button
          v-if="!importSuccessResult"
          type="button"
          class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-colors shadow-sm disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2 cursor-pointer"
          :disabled="!canCommit || isCommitting || isValidating"
          @click="commitImport"
        >
          <span
            v-if="isCommitting"
            class="animate-spin"
          >⏳</span>
          <span>{{ isCommitting ? 'Mengimpor Data...' : 'Konfirmasi Import Data' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useMasterDataImport } from '../composables/use_master_data_import';
import MasterDataImportInstructions from './MasterDataImportInstructions.vue';
import MasterDataImportUploader from './MasterDataImportUploader.vue';
import MasterDataImportSummary from './MasterDataImportSummary.vue';
import MasterDataImportErrorTable from './MasterDataImportErrorTable.vue';
import MasterDataImportPreviewTable from './MasterDataImportPreviewTable.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    required: true,
    validator: (val) => ['products', 'categories', 'units', 'locations'].includes(val),
  },
  title: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close', 'imported']);

const uploaderRef = ref(null);

const {
  selectedFile,
  isValidating,
  isCommitting,
  isDownloadingTemplate,
  generalError,
  validationResult,
  importSuccessResult,
  typeLabel,
  displayTitle,
  previewColumns,
  canCommit,
  resetState,
  onFileSelected,
  downloadTemplate,
  validateFile,
  commitImport,
} = useMasterDataImport(props, emit);

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      resetState();
      if (uploaderRef.value?.inputEl) {
        uploaderRef.value.inputEl.value = '';
      }
    }
  }
);

function handleClose() {
  if (isCommitting.value || isValidating.value) return;
  emit('close');
}
</script>
