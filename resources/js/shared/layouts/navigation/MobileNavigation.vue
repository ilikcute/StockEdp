<template>
  <div
    v-if="isOpen"
    id="mobile-navigation"
    class="lg:hidden border-b border-gray-200 bg-white px-4 pt-2 pb-4 space-y-4 max-h-[85vh] overflow-y-auto shadow-lg"
  >
    <!-- Profil -->
    <div class="space-y-1">
      <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider px-2">
        Pengguna
      </div>
      <router-link
        to="/profile"
        class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50"
        active-class="bg-blue-50 text-blue-600 font-semibold"
        @click="emit('close')"
      >
        Profil Saya
      </router-link>
    </div>

    <!-- Master Data -->
    <div
      v-if="hasMasterPermission"
      class="space-y-1 border-t border-gray-100 pt-3"
    >
      <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider px-2">
        Master Data
      </div>
      <template
        v-for="item in masterNavLinks"
        :key="item.to"
      >
        <router-link
          v-if="authStore.hasPermission(item.permission)"
          :to="item.to"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50"
          active-class="bg-blue-50 text-blue-600 font-semibold"
          @click="emit('close')"
        >
          {{ item.label }}
        </router-link>
      </template>
    </div>

    <!-- Persediaan -->
    <div
      v-if="hasInventoryPermission"
      class="space-y-1 border-t border-gray-100 pt-3"
    >
      <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider px-2">
        Persediaan
      </div>
      <template
        v-for="item in inventoryNavLinks"
        :key="item.to"
      >
        <router-link
          v-if="authStore.hasPermission(item.permission)"
          :to="item.to"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50"
          active-class="bg-blue-50 text-blue-600 font-semibold"
          @click="emit('close')"
        >
          {{ item.label }}
        </router-link>
      </template>
    </div>

    <!-- Laporan Persediaan -->
    <div
      v-if="hasInventoryReportPermission"
      class="space-y-1 border-t border-gray-100 pt-3"
    >
      <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider px-2">
        Laporan Persediaan
      </div>
      <template
        v-for="item in inventoryReportNavLinks"
        :key="item.to"
      >
        <router-link
          v-if="authStore.hasPermission(item.permission)"
          :to="item.to"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50"
          active-class="bg-blue-50 text-blue-600 font-semibold"
          @click="emit('close')"
        >
          {{ item.label }}
        </router-link>
      </template>
    </div>

    <!-- Laporan Transaksi -->
    <div
      v-if="hasTransactionReportPermission"
      class="space-y-1 border-t border-gray-100 pt-3"
    >
      <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider px-2">
        Laporan Transaksi
      </div>
      <template
        v-for="item in transactionReportNavLinks"
        :key="item.to"
      >
        <router-link
          v-if="authStore.hasPermission(item.permission)"
          :to="item.to"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50"
          active-class="bg-blue-50 text-blue-600 font-semibold"
          @click="emit('close')"
        >
          {{ item.label }}
        </router-link>
      </template>
    </div>

    <!-- User Info & Logout -->
    <div class="border-t border-gray-100 pt-3 flex items-center justify-between">
      <div class="text-xs text-gray-600">
        Login sebagai: <span class="font-semibold text-gray-900">{{ authStore.user?.name }}</span>
      </div>
      <button
        type="button"
        class="text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-md"
        @click="emit('logout')"
      >
        Keluar
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import {
    masterNavLinks,
    inventoryNavLinks,
    inventoryReportNavLinks,
    transactionReportNavLinks,
    masterPermissions,
    inventoryPermissions,
    inventoryReportPermissions,
    transactionReportPermissions,
    hasAnyPermission,
} from './navigationPermissions';

defineProps({
    isOpen: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'logout']);

const authStore = useAuthStore();

const hasMasterPermission = computed(() => hasAnyPermission(authStore, masterPermissions));
const hasInventoryPermission = computed(() => hasAnyPermission(authStore, inventoryPermissions));
const hasInventoryReportPermission = computed(() => hasAnyPermission(authStore, inventoryReportPermissions));
const hasTransactionReportPermission = computed(() => hasAnyPermission(authStore, transactionReportPermissions));
</script>
