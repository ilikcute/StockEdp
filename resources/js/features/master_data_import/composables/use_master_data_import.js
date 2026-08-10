import { ref, computed } from 'vue';
import { masterDataImportApi } from '../api/master_data_import_api';

export function useMasterDataImport(props, emit) {
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

  return {
    fileInputRef,
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
  };
}
