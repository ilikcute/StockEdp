import { ref, reactive } from 'vue';
import { userApi } from '../api/user_api.js';
import { normalizeApiError } from '@shared/api/api_client.js';

export function useUserManagement() {
    const users = ref([]);
    const meta = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0,
    });

    const filters = reactive({
        search: '',
        role_id: '',
        location_id: '',
        is_active: '',
        sort_by: 'created_at',
        sort_order: 'desc',
        page: 1,
    });

    const roles = ref([]);
    const locations = ref([]);
    const roleListWithPermissions = ref([]);
    const allPermissions = ref({});

    const loading = ref(false);
    const optionsLoading = ref(false);
    const rolesLoading = ref(false);
    const saving = ref(false);
    const error = ref(null);
    const formErrors = ref({});

    const isFormModalOpen = ref(false);
    const editingUser = ref(null);
    const activeTab = ref('users'); // 'users' | 'roles'

    const fetchUsers = async () => {
        loading.value = true;
        error.value = null;
        try {
            const params = {
                page: filters.page,
                search: filters.search || undefined,
                role_id: filters.role_id || undefined,
                location_id: filters.location_id || undefined,
                is_active: filters.is_active !== '' ? filters.is_active : undefined,
                sort_by: filters.sort_by || undefined,
                sort_order: filters.sort_order || undefined,
            };

            const response = await userApi.getUsers(params);
            users.value = response.data.data ?? [];
            meta.value = response.data.meta ?? meta.value;
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message;
        } finally {
            loading.value = false;
        }
    };

    const fetchFormOptions = async () => {
        optionsLoading.value = true;
        try {
            const response = await userApi.getFormOptions();
            roles.value = response.data.data?.roles ?? [];
            locations.value = response.data.data?.locations ?? [];
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message;
        } finally {
            optionsLoading.value = false;
        }
    };

    const fetchRolesAndPermissions = async () => {
        rolesLoading.value = true;
        try {
            const [rolesRes, permsRes] = await Promise.all([
                userApi.getRoles(),
                userApi.getPermissions(),
            ]);
            roleListWithPermissions.value = rolesRes.data.data ?? [];
            allPermissions.value = permsRes.data.data ?? {};
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message;
        } finally {
            rolesLoading.value = false;
        }
    };

    const openCreateModal = () => {
        editingUser.value = null;
        formErrors.value = {};
        isFormModalOpen.value = true;
    };

    const openEditModal = (user) => {
        editingUser.value = { ...user };
        formErrors.value = {};
        isFormModalOpen.value = true;
    };

    const closeFormModal = () => {
        isFormModalOpen.value = false;
        editingUser.value = null;
        formErrors.value = {};
    };

    const saveUser = async (formData) => {
        saving.value = true;
        formErrors.value = {};
        error.value = null;

        try {
            if (editingUser.value?.id) {
                await userApi.updateUser(editingUser.value.id, formData);
            } else {
                await userApi.createUser(formData);
            }
            closeFormModal();
            await fetchUsers();
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message;
            formErrors.value = normalized.errors ?? {};
            return false;
        } finally {
            saving.value = false;
        }
    };

    const toggleUserStatus = async (user) => {
        const newStatus = !user.is_active;
        const confirmMsg = newStatus
            ? `Aktifkan kembali akun pengguna "${user.name}"?`
            : `Nonaktifkan akun pengguna "${user.name}"? Pengguna tidak akan bisa login.`;

        if (!window.confirm(confirmMsg)) {
            return;
        }

        loading.value = true;
        error.value = null;

        try {
            await userApi.updateUserStatus(user.id, newStatus);
            await fetchUsers();
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message;
        } finally {
            loading.value = false;
        }
    };

    const changePage = (page) => {
        filters.page = page;
        fetchUsers();
    };

    const resetFilters = () => {
        filters.search = '';
        filters.role_id = '';
        filters.location_id = '';
        filters.is_active = '';
        filters.sort_by = 'created_at';
        filters.sort_order = 'desc';
        filters.page = 1;
        fetchUsers();
    };

    return {
        users,
        meta,
        filters,
        roles,
        locations,
        roleListWithPermissions,
        allPermissions,
        loading,
        optionsLoading,
        rolesLoading,
        saving,
        error,
        formErrors,
        isFormModalOpen,
        editingUser,
        activeTab,
        fetchUsers,
        fetchFormOptions,
        fetchRolesAndPermissions,
        openCreateModal,
        openEditModal,
        closeFormModal,
        saveUser,
        toggleUserStatus,
        changePage,
        resetFilters,
    };
}
