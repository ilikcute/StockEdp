<template>
  <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto touch-scroll">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 uppercase tracking-wider">
            <th class="py-3 px-4">
              Pengguna
            </th>
            <th class="py-3 px-4">
              Peran (Role)
            </th>
            <th class="py-3 px-4">
              Akses Lokasi Gudang
            </th>
            <th class="py-3 px-4">
              Terakhir Login
            </th>
            <th class="py-3 px-4 text-center">
              Status
            </th>
            <th class="py-3 px-4 text-right">
              Tindakan
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-sm">
          <tr
            v-if="loading"
            class="text-center"
          >
            <td
              colspan="6"
              class="py-12 text-gray-500"
            >
              <div class="inline-flex items-center gap-2">
                <svg
                  class="animate-spin h-5 w-5 text-indigo-600"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v8H4z"
                  />
                </svg>
                <span class="text-sm font-medium">Memuat data pengguna...</span>
              </div>
            </td>
          </tr>

          <tr
            v-else-if="users.length === 0"
            class="text-center"
          >
            <td
              colspan="6"
              class="py-12 text-gray-500"
            >
              <div class="flex flex-col items-center justify-center space-y-1">
                <svg
                  class="w-8 h-8 text-gray-400 mb-1"
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
                <span class="font-medium text-gray-700">Tidak ada data pengguna</span>
                <span class="text-xs text-gray-400">Silakan sesuaikan filter pencarian atau tambahkan pengguna baru.</span>
              </div>
            </td>
          </tr>

          <tr
            v-for="user in users"
            :key="user.id"
            class="hover:bg-gray-50/80 transition-colors align-top"
          >
            <!-- 1. Pengguna (Nama, Username, Email) -->
            <td class="py-3 px-4">
              <div class="font-semibold text-gray-900 flex items-center gap-1.5">
                <span>{{ user.name }}</span>
                <span
                  v-if="currentUserId === user.id"
                  class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20"
                >
                  Anda
                </span>
              </div>
              <div class="text-xs text-gray-500 font-mono mt-0.5">
                @{{ user.username }}
              </div>
              <div class="text-xs text-gray-400 mt-0.5">
                {{ user.email }}
              </div>
            </td>

            <!-- 2. Peran (Role) -->
            <td class="py-3 px-4">
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="role in user.roles"
                  :key="role.id"
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset',
                    role.code === 'ADMIN'
                      ? 'bg-purple-50 text-purple-700 ring-purple-600/20 font-semibold'
                      : role.code === 'INVENTORY_SUPERVISOR'
                        ? 'bg-blue-50 text-blue-700 ring-blue-600/20'
                        : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                  ]"
                >
                  {{ role.name }}
                </span>
                <span
                  v-if="!user.roles || user.roles.length === 0"
                  class="text-xs text-gray-400 italic"
                >
                  Tanpa Peran
                </span>
              </div>
            </td>

            <!-- 3. Akses Lokasi Gudang -->
            <td class="py-3 px-4">
              <div
                v-if="user.locations && user.locations.length > 0"
                class="flex flex-wrap gap-1 max-w-xs"
              >
                <span
                  v-for="loc in user.locations"
                  :key="loc.id"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700 font-mono"
                >
                  {{ loc.code }}
                </span>
              </div>
              <span
                v-else
                class="text-xs text-gray-400 italic"
              >
                Seluruh Lokasi / Global
              </span>
            </td>

            <!-- 4. Terakhir Login -->
            <td class="py-3 px-4 text-xs text-gray-500">
              <div v-if="user.last_login_at">
                {{ formatDateTime(user.last_login_at) }}
              </div>
              <span
                v-else
                class="text-gray-400 italic"
              >
                Belum pernah login
              </span>
            </td>

            <!-- 5. Status Aktif -->
            <td class="py-3 px-4 text-center">
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset',
                  user.is_active
                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                    : 'bg-gray-100 text-gray-600 ring-gray-500/20'
                ]"
              >
                {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>

            <!-- 6. Tindakan -->
            <td class="py-3 px-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors cursor-pointer"
                  @click="$emit('edit', user)"
                >
                  Edit
                </button>

                <button
                  type="button"
                  :disabled="currentUserId === user.id"
                  :title="currentUserId === user.id ? 'Tidak dapat menonaktifkan akun sendiri' : (user.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun')"
                  :class="[
                    'rounded-md px-2.5 py-1 text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed',
                    user.is_active
                      ? 'border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100'
                      : 'border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                  ]"
                  @click="$emit('toggle-status', user)"
                >
                  {{ user.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div
      v-if="meta.total > 0"
      class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-3 border-t border-gray-200 bg-gray-50/50 text-xs text-gray-500"
    >
      <div class="text-gray-600 font-medium">
        Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total }} pengguna
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          :disabled="meta.current_page <= 1 || loading"
          class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-xs hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
          @click="$emit('change-page', meta.current_page - 1)"
        >
          Sebelumnya
        </button>

        <span class="px-2 font-medium text-gray-700">
          {{ meta.current_page }} / {{ meta.last_page }}
        </span>

        <button
          type="button"
          :disabled="meta.current_page >= meta.last_page || loading"
          class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-xs hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
          @click="$emit('change-page', meta.current_page + 1)"
        >
          Berikutnya
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@features/auth/stores/use_auth_store.js';

defineProps({
  users: {
    type: Array,
    default: () => [],
  },
  meta: {
    type: Object,
    default: () => ({}),
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['edit', 'toggle-status', 'change-page']);

const authStore = useAuthStore();
const currentUserId = computed(() => authStore.user?.id);

const formatDateTime = (isoString) => {
  if (!isoString) return '-';
  try {
    const d = new Date(isoString);
    return d.toLocaleString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return isoString;
  }
};
</script>
