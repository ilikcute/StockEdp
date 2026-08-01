<template>
  <div class="max-w-2xl mx-auto mt-10 p-6 bg-white rounded-xl shadow-md border border-gray-100">
    <div class="flex items-center justify-between pb-6 border-b border-gray-100">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Profil Pengguna
        </h1>
        <p class="text-sm text-gray-500">
          Informasi akun aktif Anda
        </p>
      </div>
      <button
        class="px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition-colors"
        @click="handleLogout"
      >
        Keluar
      </button>
    </div>

    <div class="mt-6 space-y-4">
      <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-50">
        <span class="text-sm font-medium text-gray-500">Nama</span>
        <span class="col-span-2 text-sm text-gray-900">{{ authStore.user?.name }}</span>
      </div>
      <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-50">
        <span class="text-sm font-medium text-gray-500">Username</span>
        <span class="col-span-2 text-sm text-gray-900">{{ authStore.user?.username }}</span>
      </div>
      <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-50">
        <span class="text-sm font-medium text-gray-500">Email</span>
        <span class="col-span-2 text-sm text-gray-900">{{ authStore.user?.email }}</span>
      </div>
      <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-50">
        <span class="text-sm font-medium text-gray-500">Role / Peran</span>
        <div class="col-span-2 flex flex-wrap gap-1.5">
          <span
            v-for="role in authStore.user?.roles"
            :key="role"
            class="px-2 py-0.5 text-xs font-semibold text-blue-700 bg-blue-50 rounded"
          >
            {{ role }}
          </span>
        </div>
      </div>
      <div class="grid grid-cols-3 gap-4 py-2">
        <span class="text-sm font-medium text-gray-500">Daftar Izin</span>
        <div class="col-span-2 flex flex-wrap gap-1.5">
          <span
            v-for="perm in authStore.user?.permissions"
            :key="perm"
            class="px-2 py-0.5 text-xs text-gray-600 bg-gray-100 rounded"
          >
            {{ perm }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/use_auth_store.js';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

async function handleLogout() {
    await authStore.logout();
    router.push('/login');
}
</script>
