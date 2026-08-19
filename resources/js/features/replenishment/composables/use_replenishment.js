import { reactive, ref } from 'vue';
import { replenishmentApi } from '../api/replenishment_api.js';

export function useReplenishment() {
  const loading = ref(false);
  const filterOptionsLoading = ref(false);
  const error = ref('');
  const recommendations = ref([]);
  const generatedAt = ref('');

  // Action Center & Review Modal State
  const isReviewModalOpen = ref(false);
  const reviewItems = ref([]);
  const validatingAction = ref(false);
  const conflictError = ref(null);
  const generalError = ref(null);

  const summary = reactive({
    low_stock_product_count: 0,
    inbound_covered_count: 0,
    internal_transfer_count: 0,
    mixed_count: 0,
    external_reorder_count: 0,
    critical_product_count: 0,
  });

  const meta = reactive({
    current_page: 1,
    from: null,
    last_page: 1,
    per_page: 15,
    to: null,
    total: 0,
  });

  const filterOptions = reactive({
    locations: [],
    categories: [],
    units: [],
    recommendation_types: [],
    priorities: [],
  });

  const filters = reactive({
    location_id: '',
    search: '',
    category_id: '',
    unit_id: '',
    recommendation_type: '',
    priority: '',
    sort_by: 'gross_shortage_quantity',
    sort_order: 'desc',
    per_page: 15,
    page: 1,
  });

  const fetchFilterOptions = async () => {
    filterOptionsLoading.value = true;
    error.value = '';
    try {
      const res = await replenishmentApi.getFilterOptions();
      const payload = res.data?.data || res.data || {};
      filterOptions.locations = payload.locations || [];
      filterOptions.categories = payload.categories || [];
      filterOptions.units = payload.units || [];
      filterOptions.recommendation_types = payload.recommendation_types || [];
      filterOptions.priorities = payload.priorities || [];

      // Auto-select first location if not set
      if (!filters.location_id && filterOptions.locations.length > 0) {
        filters.location_id = filterOptions.locations[0].id;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat opsi filter.';
    } finally {
      filterOptionsLoading.value = false;
    }
  };

  const fetchRecommendations = async () => {
    if (!filters.location_id) {
      recommendations.value = [];
      return;
    }

    loading.value = true;
    error.value = '';
    try {
      const cleanParams = {
        location_id: filters.location_id,
        page: filters.page,
        per_page: filters.per_page,
        sort_by: filters.sort_by,
        sort_order: filters.sort_order,
      };

      if (filters.search) cleanParams.search = filters.search;
      if (filters.category_id) cleanParams.category_id = filters.category_id;
      if (filters.unit_id) cleanParams.unit_id = filters.unit_id;
      if (filters.recommendation_type) cleanParams.recommendation_type = filters.recommendation_type;
      if (filters.priority) cleanParams.priority = filters.priority;

      const res = await replenishmentApi.getRecommendations(cleanParams);
      const root = res.data?.data || res.data || {};

      recommendations.value = root.data || [];
      generatedAt.value = root.generated_at || '';

      if (root.summary) {
        summary.low_stock_product_count = root.summary.low_stock_product_count ?? 0;
        summary.inbound_covered_count = root.summary.inbound_covered_count ?? 0;
        summary.internal_transfer_count = root.summary.internal_transfer_count ?? 0;
        summary.mixed_count = root.summary.mixed_count ?? 0;
        summary.external_reorder_count = root.summary.external_reorder_count ?? 0;
        summary.critical_product_count = root.summary.critical_product_count ?? 0;
      }

      if (root.meta) {
        meta.current_page = root.meta.current_page ?? 1;
        meta.from = root.meta.from ?? null;
        meta.last_page = root.meta.last_page ?? 1;
        meta.per_page = root.meta.per_page ?? 15;
        meta.to = root.meta.to ?? null;
        meta.total = root.meta.total ?? 0;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data rekomendasi reorder.';
      recommendations.value = [];
    } finally {
      loading.value = false;
    }
  };

  const openReviewModal = (items) => {
    reviewItems.value = Array.isArray(items) ? items : [items];
    conflictError.value = null;
    generalError.value = null;
    isReviewModalOpen.value = true;
  };

  const closeReviewModal = () => {
    isReviewModalOpen.value = false;
    reviewItems.value = [];
    conflictError.value = null;
    generalError.value = null;
  };

  const validateAction = async (payload) => {
    validatingAction.value = true;
    conflictError.value = null;
    generalError.value = null;

    try {
      const res = await replenishmentApi.validateAction(payload);
      return {
        success: true,
        data: res.data?.data || res.data,
      };
    } catch (err) {
      const status = err.response?.status;
      const msg = err.response?.data?.message || 'Gagal memvalidasi aksi transfer persediaan.';

      if (status === 409) {
        conflictError.value = msg;
      } else {
        generalError.value = msg;
      }

      return {
        success: false,
        status,
        message: msg,
      };
    } finally {
      validatingAction.value = false;
    }
  };

  const changePage = (newPage) => {
    filters.page = newPage;
    fetchRecommendations();
  };

  const resetFilters = () => {
    filters.search = '';
    filters.category_id = '';
    filters.unit_id = '';
    filters.recommendation_type = '';
    filters.priority = '';
    filters.sort_by = 'gross_shortage_quantity';
    filters.sort_order = 'desc';
    filters.page = 1;
    fetchRecommendations();
  };

  return {
    loading,
    filterOptionsLoading,
    error,
    recommendations,
    generatedAt,
    summary,
    meta,
    filterOptions,
    filters,
    isReviewModalOpen,
    reviewItems,
    validatingAction,
    conflictError,
    generalError,
    fetchFilterOptions,
    fetchRecommendations,
    openReviewModal,
    closeReviewModal,
    validateAction,
    changePage,
    resetFilters,
  };
}
