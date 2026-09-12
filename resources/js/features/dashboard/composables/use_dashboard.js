import { ref, reactive } from 'vue';
import dashboardApi from '../api/dashboard_api';

export function useDashboard() {
    const loading = ref(false);
    const error = ref(null);
    const dashboardData = ref(null);

    const filters = reactive({
        location_id: '',
        period: '7d',
    });

    const fetchDashboard = async () => {
        loading.value = true;
        error.value = null;

        try {
            const params = {};
            if (filters.location_id) {
                params.location_id = filters.location_id;
            }
            if (filters.period) {
                params.period = filters.period;
            }

            const response = await dashboardApi.getDashboard(params);
            if (response?.data?.success) {
                dashboardData.value = response.data.data;
            } else {
                dashboardData.value = response?.data || null;
            }
        } catch (err) {
            console.error('Failed to load operational dashboard:', err);
            error.value = err.response?.data?.message || 'Gagal memuat data dashboard operasional.';
        } finally {
            loading.value = false;
        }
    };

    return {
        loading,
        error,
        dashboardData,
        filters,
        fetchDashboard,
    };
}
