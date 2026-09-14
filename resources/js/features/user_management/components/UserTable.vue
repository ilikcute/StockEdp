<template>
  <div class="bg-white rounded-xl shadow-2xs border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left border-collapse text-xs">
        <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
          <tr class="border-b border-gray-200 text-[11px] font-semibold text-gray-600">
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Pengguna
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Peran (Role)
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Akses Lokasi Gudang
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Terakhir Login
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap text-center"
            >
              Status
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Tindakan
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr
            v-if="loading"
            class="text-center"
          >
            <td
              colspan="6"
              class="py-8 text-gray-500 text-xs"
            >
              <div class="inline-flex items-center gap-2">
                <svg
                  class="animate-spin h-4 w-4 text-indigo-600"
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
                <span class="text-xs font-medium">Memuat data pengguna...</span>
              </div>
            </td>
          </tr>

          <tr
            v-else-if="users.length === 0"
            class="text-center"
          >
            <td
              colspan="6"
              class="py-8 text-gray-500 text-xs"
            >
              <div class="flex flex-col items-center justify-center space-y-1">
                <svg
                  class="w-7 h-7 text-gray-400 mb-1"
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
                <span class="font-medium text-gray-700 text-xs">Tidak ada data pengguna</span>
                <span class="text-[11px] text-gray-400">Silakan sesuaikan filter pencarian atau tambahkan pengguna baru.</span>
              </div>
            </td>
          </tr>

          <tr
            v-for="user in users"
            :key="user.id"
            class="hover:bg-gray-50/80 transition-colors align-top"
          >
            <!-- 1. Pengguna (Nama, Username, Email) -->
            <td class="py-1.5 px-2 whitespace-nowrap">
              <div class="font-medium text-gray-900 flex items-center gap-1.5 text-[11px] leading-tight">
                <span>{{ user.name }}</span>
                <span
                  v-if="currentUserId === user.id"
                  class="inline-flex items-center px-1 py-0.2 rounded text-[9px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200"
                >
                  Anda
                </span>
              </div>
              <div class="text-[10px] text-gray-500 font-mono">
                @{{ user.username }}
              </div>
              <div class="text-[10px] text-gray-400">
                {{ user.email }}
              </div>
            </td>

            <!-- 2. Peran (Role) -->
            <td class="py-1.5 px-2 whitespace-nowrap">
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="role in user.roles"
                  :key="role.id"
                  class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                  :class="[
                    role.code === 'ADMIN'
                      ? 'bg-purple-50 text-purple-700 border border-purple-200'
                      : role.code === 'INVENTORY_SUPERVISOR'
                        ? 'bg-blue-50 text-blue-700 border border-blue-200'
                        : 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                  ]"
                >
                  {{ role.name }}
                </span>
                <span
                  v-if="!user.roles || user.roles.length === 0"
                  class="text-[11px] text-gray-400 italic"
                >
                  Tanpa Peran
                </span>
              </div>
            </td>

            <!-- 3. Akses Lokasi Gudang -->
            <td class="py-1.5 px-2 whitespace-nowrap">
              <div
                v-if="user.locations && user.locations.length > 0"
                class="flex flex-wrap gap-1 max-w-xs"
              >
                <span
                  v-for="loc in user.locations"
                  :key="loc.id"
                  class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] bg-gray-100 text-gray-700 font-mono border border-gray-200"
                >
                  {{ loc.code }}
                </span>
              </div>
              <span
                v-else
                class="text-[11px] text-gray-400 italic"
              >
                Seluruh Lokasi / Global
              </span>
            </td>

            <!-- 4. Terakhir Login -->
            <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
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
            <td class="py-1.5 px-2 text-center whitespace-nowrap">
              <span
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                :class="user.is_active
                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                  : 'bg-gray-100 text-gray-600 border border-gray-200'"
              >
                {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>

            <!-- 6. Tindakan -->
            <td class="py-1.5 px-2 text-right whitespace-nowrap text-[11px]">
              <div class="flex items-center justify-end gap-1.5">
                <button
                  type="button"
                  class="rounded border border-gray-300 bg-white px-2 py-0.5 text-[11px] font-semibold text-gray-700 shadow-2xs hover:bg-gray-50 cursor-pointer"
                  @click="$emit('edit', user)"
                >
                  Edit
                </button>

                <button
                  type="button"
                  :disabled="currentUserId === user.id"
                  :title="currentUserId === user.id ? 'Tidak dapat menonaktifkan akun sendiri' : (user.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun')"
                  :class="[
                    'rounded px-2 py-0.5 text-[11px] font-semibold shadow-2xs transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed',
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

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
