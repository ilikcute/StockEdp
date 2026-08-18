<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">
          Pengelolaan Pengguna & Hak Akses
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Kelola akun pengguna, peran otorisasi (RBAC), dan penugasan akses lokasi gudang.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="hasPermission('users.manage')"
          id="btn-create-user"
          type="button"
          class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors cursor-pointer"
          @click="openCreateModal"
        >
          <svg
            class="w-4 h-4 mr-1.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          Tambah Pengguna
        </button>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200">
      <nav
        class="-mb-px flex space-x-8"
        aria-label="Tabs"
      >
        <button
          type="button"
          :class="[
            activeTab === 'users'
              ? 'border-indigo-600 text-indigo-600 font-semibold'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
            'whitespace-nowrap border-b-2 py-3 px-1 text-sm cursor-pointer transition-colors'
          ]"
          @click="activeTab = 'users'"
        >
          Daftar Pengguna
          <span
            v-if="meta.total"
            class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
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
            'whitespace-nowrap border-b-2 py-3 px-1 text-sm cursor-pointer transition-colors'
          ]"
          @click="onSelectRolesTab"
        >
          Peran & Hak Akses (Roles & Permissions)
        </button>
      </nav>
    </div>

    <!-- Error Banner -->
    <div
      v-if="error"
      class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-sm flex items-center justify-between shadow-xs"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-rose-600 shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ error }}</span>
      </div>
      <button
        type="button"
        class="text-xs font-semibold text-rose-600 hover:text-rose-800 underline ml-2 cursor-pointer"
        @click="fetchUsers"
      >
        Coba Lagi
      </button>
    </div>

    <!-- TAB 1: DAFTAR PENGGUNA -->
    <div
      v-if="activeTab === 'users'"
      class="space-y-4"
    >
      <!-- Filters Section -->
      <div class="bg-white rounded-xl p-4 shadow-xs border border-gray-200">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- Search Input -->
          <div>
            <label
              for="user-search"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Cari Pengguna
            </label>
            <input
              id="user-search"
              v-model="filters.search"
              type="text"
              placeholder="Cari nama, username, email..."
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
              @input="onSearchInput"
            >
          </div>

          <!-- Role Filter -->
          <div>
            <label
              for="user-role-filter"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Peran (Role)
            </label>
            <select
              id="user-role-filter"
              v-model="filters.role_id"
              class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 shadow-xs"
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
          </div>

          <!-- Location Filter -->
          <div>
            <label
              for="user-location-filter"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Akses Lokasi Gudang
            </label>
            <select
              id="user-location-filter"
              v-model="filters.location_id"
              class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 shadow-xs"
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
          </div>

          <!-- Status Filter -->
          <div>
            <label
              for="user-status-filter"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Status Akun
            </label>
            <select
              id="user-status-filter"
              v-model="filters.is_active"
              class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 shadow-xs"
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
          </div>
        </div>

        <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-end">
          <button
            type="button"
            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors cursor-pointer"
            @click="resetFilters"
          >
            Reset Filter
          </button>
        </div>
      </div>

      <!-- Users Table -->
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
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@features/auth/stores/use_auth_store.js';
import { useUserManagement } from '../composables/use_user_management.js';
import UserTable from '../components/UserTable.vue';
import UserFormModal from '../components/UserFormModal.vue';
import RolePermissionMatrix from '../components/RolePermissionMatrix.vue';

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
