import { ref, watch, onMounted } from 'vue';
import { useAuthStore } from '@features/auth/stores/use_auth_store';

export function useDocumentList({ store, fetch, collection, extraFilters = ref({}) }) {
    const authStore = useAuthStore();

    const searchQuery = ref('');
    const statusFilter = ref('');

    watch([statusFilter, extraFilters], () => fetchData(1), { deep: true });

    const fetchData = (page = 1) => {
        const extra = typeof extraFilters.value === 'object' ? extraFilters.value : {};
        fetch({
            page,
            search: searchQuery.value,
            status: statusFilter.value,
            ...extra,
        });
    };

    const onSearch = () => {
        fetchData(1);
    };

    const changePage = (page) => {
        const lastPage = store[collection]?.meta?.last_page || 1;
        if (page >= 1 && page <= lastPage) {
            fetchData(page);
        }
    };

    const hasPermission = (permission) => {
        return authStore.hasPermission(permission);
    };

    onMounted(() => {
        fetchData();
    });

    return { store, searchQuery, statusFilter, fetchData, onSearch, changePage, hasPermission };
}