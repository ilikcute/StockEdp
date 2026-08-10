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
        <!-- Panduan & Download Template -->
        <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-4 space-y-3">
          <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
              <h4 class="text-xs font-semibold text-blue-900 uppercase tracking-wider">
                Petunjuk Pengisian File CSV:
              </h4>
              <ul class="text-xs text-blue-800 list-disc list-inside space-y-0.5">
                <li>Gunakan template resmi berformat CSV UTF-8.</li>
                <li>Jangan mengubah atau menghapus nama kolom header.</li>
                <li>Maksimum 5.000 baris data per proses import.</li>
                <li><strong>CREATE ONLY</strong>: Data yang sudah ada di database atau duplikat di file dianggap error.</li>
                <li><strong>All-or-Nothing</strong>: Seluruh baris harus valid agar data dapat diimport.</li>
              </ul>
            </div>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-white hover:bg-blue-50 border border-blue-200 rounded-lg transition-colors shadow-xs shrink-0 cursor-pointer"
              :disabled="isDownloadingTemplate"
              @click="downloadTemplate"
            >
              <span v-if="isDownloadingTemplate">Mengunduh...</span>
              <span v-else>⬇ Unduh Template CSV</span>
            </button>
          </div>
        </div>

        <!-- File Upload Section -->
        <div class="space-y-2">
          <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
            Pilih File CSV:
          </label>
          <div class="flex items-center gap-3">
            <input
              ref="fileInputRef"
              type="file"
              accept=".csv,text/csv"
              class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 file:cursor-pointer border border-gray-200 rounded-xl p-1 bg-white cursor-pointer"
              @change="onFileSelected"
            >
            <button
              type="button"
              class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed shrink-0 cursor-pointer"
              :disabled="!selectedFile || isValidating || isCommitting"
              @click="validateFile"
            >
              <span v-if="isValidating">Memvalidasi...</span>
              <span v-else>🔍 Validasi File</span>
            </button>
          </div>
          <p
            v-if="selectedFile"
            class="text-xs text-gray-500"
          >
            File dipilih: <strong>{{ selectedFile.name }}</strong> ({{ (selectedFile.size / 1024).toFixed(1) }} KB)
          </p>
        </div>

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
          <!-- Summary Cards -->
          <div class="grid grid-cols-3 gap-3">
            <div class="bg-gray-50 border border-gray-100 p-3 rounded-xl">
              <span class="text-xs text-gray-500 block">Total Baris</span>
              <span class="text-lg font-bold text-gray-900">{{ validationResult.total_rows }}</span>
            </div>
            <div class="bg-green-50 border border-green-100 p-3 rounded-xl">
              <span class="text-xs text-green-700 block">Baris Valid</span>
              <span class="text-lg font-bold text-green-700">{{ validationResult.valid_rows }}</span>
            </div>
            <div
              class="p-3 rounded-xl border"
              :class="validationResult.invalid_rows > 0 ? 'bg-red-50 border-red-100 text-red-700' : 'bg-gray-50 border-gray-100 text-gray-500'"
            >
              <span class="text-xs block">Baris Error</span>
              <span
                class="text-lg font-bold"
                :class="validationResult.invalid_rows > 0 ? 'text-red-700' : 'text-gray-900'"
              >
                {{ validationResult.invalid_rows }}
              </span>
            </div>
          </div>

          <!-- Status Banner -->
          <div
            v-if="validationResult.invalid_rows === 0 && validationResult.total_rows > 0"
            class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-center gap-2"
          >
            <span>✓</span>
            <span class="font-medium">Seluruh {{ validationResult.total_rows }} baris data valid dan siap diimport ke database.</span>
          </div>

          <div
            v-else-if="validationResult.invalid_rows > 0"
            class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 space-y-1"
          >
            <div class="flex items-center gap-2 font-semibold">
              <span>⚠</span>
              <span>Ditemukan {{ validationResult.invalid_rows }} baris data bermasalah.</span>
            </div>
            <p class="text-[11px] text-red-700">
              Sesuai aturan <em>All-or-Nothing</em>, proses import tidak dapat dilanjutkan sebelum file CSV diperbaiki.
            </p>
          </div>

          <!-- Error List Table -->
          <div
            v-if="validationResult.errors && validationResult.errors.length > 0"
            class="space-y-2"
          >
            <h5 class="text-xs font-bold text-red-900 uppercase tracking-wider">
              Daftar Error ({{ validationResult.errors.length }} Masalah Ditemukan):
            </h5>
            <div class="max-h-60 overflow-y-auto border border-red-200 rounded-xl">
              <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-red-50 text-red-900 font-semibold sticky top-0 border-b border-red-200">
                  <tr>
                    <th class="p-2.5 w-16 text-center">
                      Baris
                    </th>
                    <th class="p-2.5 w-28">
                      Kolom
                    </th>
                    <th class="p-2.5 w-36">
                      Kode Error
                    </th>
                    <th class="p-2.5">
                      Keterangan
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-red-100 bg-white">
                  <tr
                    v-for="(err, idx) in validationResult.errors"
                    :key="idx"
                    class="hover:bg-red-50/40"
                  >
                    <td class="p-2 text-center font-semibold text-red-700">
                      {{ err.row }}
                    </td>
                    <td class="p-2 font-mono text-gray-700">
                      {{ err.field }}
                    </td>
                    <td class="p-2 font-mono text-[11px] text-red-600">
                      {{ err.code }}
                    </td>
                    <td class="p-2 text-gray-800">
                      {{ err.message }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Preview Table (Up to 20 rows) -->
          <div
            v-if="validationResult.preview && validationResult.preview.length > 0"
            class="space-y-2"
          >
            <div class="flex items-center justify-between">
              <h5 class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                Preview Data ({{ validationResult.preview.length }} Baris Pertama):
              </h5>
              <span class="text-[11px] text-gray-400">Menampilkan maksimal 20 baris</span>
            </div>
            <div class="max-h-60 overflow-x-auto overflow-y-auto border border-gray-200 rounded-xl">
              <table class="w-full text-left text-xs border-collapse min-w-[500px]">
                <thead class="bg-gray-50 text-gray-700 font-semibold sticky top-0 border-b border-gray-200">
                  <tr>
                    <th class="p-2.5 w-14 text-center">
                      #
                    </th>
                    <th class="p-2.5 w-16 text-center">
                      Status
                    </th>
                    <th
                      v-for="col in previewColumns"
                      :key="col"
                      class="p-2.5 capitalize"
                    >
                      {{ formatColumnName(col) }}
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                  <tr
                    v-for="row in validationResult.preview"
                    :key="row.row_number"
                    :class="row.is_valid ? 'hover:bg-gray-50' : 'bg-red-50/30 hover:bg-red-50/60'"
                  >
                    <td class="p-2 text-center text-gray-500 font-mono">
                      {{ row.row_number }}
                    </td>
                    <td class="p-2 text-center">
                      <span
                        class="px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                        :class="row.is_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                      >
                        {{ row.is_valid ? 'VALID' : 'ERROR' }}
                      </span>
                    </td>
                    <td
                      v-for="col in previewColumns"
                      :key="col"
                      class="p-2 text-gray-800 truncate max-w-xs font-mono text-[11px]"
                    >
                      {{ row[col] ?? '-' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
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
import { ref, computed, watch } from 'vue';
import { masterDataImportApi } from '../api/master_data_import_api';

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

const fileInputRef = ref(null);
const selectedFile = ref(null);
const isValidating = ref(false);
const isCommitting = ref(false);
const isDownloadingTemplate = ref(false);
const generalError = ref('');
const validationResult = ref(null);
const importSuccessResult = ref(null);

const typeLabel = computed(() => {
  const map = {
    products: 'Produk',
    categories: 'Kategori',
    units: 'Satuan',
    locations: 'Lokasi',
  };
  return map[props.type] || 'Data';
});

const displayTitle = computed(() => {
  return props.title || `Import ${typeLabel.value} Masal`;
});

const previewColumns = computed(() => {
  const map = {
    categories: ['code', 'name', 'description'],
    units: ['code', 'name', 'symbol', 'description'],
    locations: ['code', 'name', 'description', 'address', 'phone'],
    products: ['sku', 'barcode', 'name', 'category_code', 'unit_code', 'minimum_stock'],
  };
  return map[props.type] || [];
});

const canCommit = computed(() => {
  return (
    validationResult.value &&
    validationResult.value.total_rows > 0 &&
    validationResult.value.invalid_rows === 0 &&
    !generalError.value &&
    !isCommitting.value
  );
});

function formatColumnName(col) {
  return col.replace('_', ' ');
}

function resetState() {
  selectedFile.value = null;
  isValidating.value = false;
  isCommitting.value = false;
  isDownloadingTemplate.value = false;
  generalError.value = '';
  validationResult.value = null;
  importSuccessResult.value = null;
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
}

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      resetState();
    }
  }
);

function onFileSelected(event) {
  const file = event.target.files?.[0];
  if (file) {
    selectedFile.value = file;
    validationResult.value = null;
    generalError.value = '';
    importSuccessResult.value = null;
  }
}

async function downloadTemplate() {
  try {
    isDownloadingTemplate.value = true;
    generalError.value = '';
    const response = await masterDataImportApi.downloadTemplate(props.type);

    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `template_${props.type}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  } catch (err) {
    generalError.value = err.response?.data?.message || 'Gagal mengunduh template CSV.';
  } finally {
    isDownloadingTemplate.value = false;
  }
}

async function validateFile() {
  if (!selectedFile.value) return;

  try {
    isValidating.value = true;
    generalError.value = '';
    validationResult.value = null;
    importSuccessResult.value = null;

    const res = await masterDataImportApi.validateImport(props.type, selectedFile.value);
    validationResult.value = res.data;
  } catch (err) {
    generalError.value = err.response?.data?.message || 'Gagal memvalidasi file CSV.';
  } finally {
    isValidating.value = false;
  }
}

async function commitImport() {
  if (!canCommit.value || !selectedFile.value || !validationResult.value) return;

  try {
    isCommitting.value = true;
    generalError.value = '';

    const sha256 = validationResult.value.sha256;
    const res = await masterDataImportApi.commitImport(props.type, selectedFile.value, sha256);
    importSuccessResult.value = res.data;
    emit('imported', res.data);
  } catch (err) {
    generalError.value = err.response?.data?.message || 'Terjadi kesalahan saat memproses import.';
  } finally {
    isCommitting.value = false;
  }
}

function handleClose() {
  if (isCommitting.value || isValidating.value) return;
  emit('close');
}
</script>
