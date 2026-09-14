<template>
  <div class="space-y-3">
    <!-- TOP Header Card with Integrated Tabs and Filters -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs space-y-2.5">
      <!-- Primary Controls Row -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h1 class="text-base font-bold text-gray-900 tracking-tight flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-indigo-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
              />
            </svg>
            Pengelolaan Pengguna & Hak Akses
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Kelola akun pengguna, peran otorisasi (RBAC), dan penugasan akses lokasi gudang.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <BaseButton
            v-if="hasPermission('users.manage')"
            id="btn-create-user"
            size="sm"
            @click="openCreateModal"
          >
            + Tambah Pengguna
          </BaseButton>
        </div>
      </div>

      <!-- Navigation Tabs & Integrated Filters Row -->
      <div class="pt-2 border-t border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-2.5">
        <nav
          class="flex space-x-6"
          aria-label="Tabs"
        >
          <button
            type="button"
            :class="[
              activeTab === 'users'
                ? 'border-indigo-600 text-indigo-600 font-semibold'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
              'whitespace-nowrap border-b-2 pb-1.5 px-1 text-xs cursor-pointer transition-colors flex items-center gap-1.5'
            ]"
            @click="activeTab = 'users'"
          >
            <span>Daftar Pengguna</span>
            <span
              v-if="meta.total"
              class="rounded-full bg-gray-100 px-1.5 py-0.2 text-[10px] text-gray-600 font-mono"
            >
              {{ meta.total }}
            </span>
          </button>

          <button
            type="button"
            :class="[
              activeTab === 'roles'
                ? 'border-indigo-600 text-indigo-600 font-semibold'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
              'whitespace-nowrap border-b-2 pb-1.5 px-1 text-xs cursor-pointer transition-colors'
            ]"
            @click="onSelectRolesTab"
          >
            Peran & Hak Akses (Roles & Permissions)
          </button>
        </nav>

        <!-- Tab Users Filters -->
        <div
          v-if="activeTab === 'users'"
          class="flex items-center gap-2 flex-wrap sm:flex-nowrap"
        >
          <div class="w-full sm:w-48">
            <input
              id="user-search"
              v-model="filters.search"
              type="text"
              placeholder="Cari nama, username..."
              class="block w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
              @input="onSearchInput"
            >
          </div>

          <select
            id="user-role-filter"
            v-model="filters.role_id"
            class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
            @change="fetchUsers"
          >
            <option value="">
              Semua Peran
            </option>
            <option
              v-for="role in roles"
              :key="role.id"
              :value="role.id"
            >
              {{ role.name }}
            </option>
          </select>

          <select
            id="user-location-filter"
            v-model="filters.location_id"
            class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
            @change="fetchUsers"
          >
            <option value="">
              Semua Lokasi
            </option>
            <option
              v-for="loc in locations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ loc.code }} - {{ loc.name }}
            </option>
          </select>

          <select
            id="user-status-filter"
            v-model="filters.is_active"
            class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
            @change="fetchUsers"
          >
            <option value="">
              Semua Status
            </option>
            <option value="true">
              Aktif
            </option>
            <option value="false">
              Nonaktif
            </option>
          </select>

          <button
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-2xs hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors cursor-pointer"
            @click="resetFilters"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Error Alert -->
    <BaseAlert
      v-if="error"
      :message="error"
    />

    <!-- TAB 1: DAFTAR PENGGUNA -->
    <div v-if="activeTab === 'users'">
      <UserTable
        :users="users"
        :meta="meta"
        :loading="loading"
        @edit="openEditModal"
        @toggle-status="toggleUserStatus"
        @change-page="changePage"
      />
    </div>

    <!-- TAB 2: ROLES & PERMISSIONS -->
    <div v-else-if="activeTab === 'roles'">
      <RolePermissionMatrix
        :roles="roleListWithPermissions"
        :all-permissions="allPermissions"
        :loading="rolesLoading"
        @edit-role-permissions="openRolePermissionsModal"
      />
    </div>

    <!-- Create / Edit User Modal -->
    <UserFormModal
      :is-open="isFormModalOpen"
      :user="editingUser"
      :roles="roles"
      :locations="locations"
      :saving="saving"
      :error="error"
      :form-errors="formErrors"
      @close="closeFormModal"
      @save="saveUser"
    />

    <!-- Edit Role Permissions Modal -->
    <RolePermissionsModal
      :is-open="isRoleModalOpen"
      :role="editingRole"
      :all-permissions="allPermissions"
      :saving="roleSaving"
      :error="roleError"
      @close="closeRolePermissionsModal"
      @save="saveRolePermissions"
    />

    <!-- Toggle User Status Confirmation -->
    <BaseConfirmation
      :model-value="Boolean(pendingToggleUser)"
      :title="toggleConfirmTitle"
      :description="toggleConfirmDescription"
      :confirm-label="isToggleDanger ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan'"
      :danger="isToggleDanger"
      :loading="loading"
      @confirm="confirmToggleUser"
      @cancel="cancelToggleUser"
    />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@features/auth/stores/use_auth_store.js';
import { useUserManagement } from '../composables/use_user_management.js';
import BaseButton from '@/shared/components/BaseButton.vue';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import BaseConfirmation from '@/shared/components/BaseConfirmation.vue';
import UserTable from '../components/UserTable.vue';
import UserFormModal from '../components/UserFormModal.vue';
import RolePermissionMatrix from '../components/RolePermissionMatrix.vue';
import RolePermissionsModal from '../components/RolePermissionsModal.vue';

const authStore = useAuthStore();
const hasPermission = (permission) => authStore.hasPermission(permission);

const {
  users,
  meta,
  filters,
  roles,
  locations,
  roleListWithPermissions,
  allPermissions,
  loading,
  rolesLoading,
  saving,
  roleSaving,
  error,
  roleError,
  formErrors,
  isFormModalOpen,
  isRoleModalOpen,
  editingUser,
  editingRole,
  activeTab,
  fetchUsers,
  fetchFormOptions,
  fetchRolesAndPermissions,
  openCreateModal,
  openEditModal,
  closeFormModal,
  openRolePermissionsModal,
  closeRolePermissionsModal,
  saveUser,
  saveRolePermissions,
  toggleUserStatus,
  confirmToggleUser,
  cancelToggleUser,
  pendingToggleUser,
  toggleConfirmTitle,
  toggleConfirmDescription,
  isToggleDanger,
  changePage,
  resetFilters,
} = useUserManagement();

let searchTimeout = null;
const onSearchInput = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filters.page = 1;
    fetchUsers();
  }, 350);
};

const onSelectRolesTab = () => {
  activeTab.value = 'roles';
  if (roleListWithPermissions.value.length === 0) {
    fetchRolesAndPermissions();
  }
};

onMounted(async () => {
  await Promise.all([
    fetchUsers(),
    fetchFormOptions(),
  ]);
});
</script>
