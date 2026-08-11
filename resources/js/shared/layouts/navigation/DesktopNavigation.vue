<template>
  <nav class="hidden lg:flex items-center space-x-1">
    <!-- Dashboard -->
    <router-link
      v-if="authStore.hasPermission('dashboard.view')"
      to="/dashboard"
      class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
      active-class="text-blue-600 bg-blue-50/50 font-semibold"
    >
      Dashboard
    </router-link>

    <!-- Profil -->
    <router-link
      to="/profile"
      class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
      active-class="text-blue-600 bg-blue-50/50"
    >
      Profil Saya
    </router-link>

    <!-- Master Data Links -->
    <template
      v-for="item in masterNavLinks"
      :key="item.to"
    >
      <router-link
        v-if="authStore.hasPermission(item.permission)"
        :to="item.to"
        class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
        active-class="text-blue-600 bg-blue-50/50"
      >
        {{ item.label }}
      </router-link>
    </template>

    <!-- Dropdown Persediaan -->
    <div
      v-if="hasInventoryPermission"
      class="relative"
    >
      <button
        type="button"
        class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
        :class="{ 'text-blue-600 bg-blue-50/50': isInventoryActive }"
        @click="toggleInventoryMenu"
      >
        Persediaan
        <svg
          class="ml-1 h-4 w-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </button>

      <div
        v-if="isInventoryOpen"
        class="absolute left-0 mt-2 w-56 rounded-md shadow-lg bg-white border border-gray-300 z-50 py-1"
      >
        <template
          v-for="item in inventoryNavLinks"
          :key="item.to"
        >
          <router-link
            v-if="authStore.hasPermission(item.permission)"
            :to="item.to"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            active-class="bg-gray-100 text-blue-600 font-medium"
            @click="isInventoryOpen = false"
          >
            {{ item.label }}
          </router-link>
        </template>
      </div>
    </div>

    <!-- Dropdown Laporan -->
    <div
      v-if="hasReportPermission"
      class="relative"
    >
      <button
        type="button"
        class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
        :class="{ 'text-blue-600 bg-blue-50/50': isReportActive }"
        @click="toggleReportMenu"
      >
        Laporan
        <svg
          class="ml-1 h-4 w-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </button>

      <div
        v-if="isReportOpen"
        class="absolute left-0 mt-2 w-64 rounded-md shadow-lg bg-white border border-gray-300 z-50 py-1"
      >
        <div
          v-if="hasInventoryReportPermission"
          class="px-4 py-1 text-[11px] font-semibold text-gray-400 uppercase tracking-wider"
        >
          Persediaan
        </div>
        <template
          v-for="item in inventoryReportNavLinks"
          :key="item.to"
        >
          <router-link
            v-if="authStore.hasPermission(item.permission)"
            :to="item.to"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            active-class="bg-gray-100 text-blue-600 font-medium"
            @click="isReportOpen = false"
          >
            {{ item.label }}
          </router-link>
        </template>

        <div
          v-if="hasTransactionReportPermission"
          class="border-t border-gray-100 my-1"
        />
        <div
          v-if="hasTransactionReportPermission"
          class="px-4 py-1 text-[11px] font-semibold text-gray-400 uppercase tracking-wider"
        >
          Transaksi
        </div>
        <template
          v-for="item in transactionReportNavLinks"
          :key="item.to"
        >
          <router-link
            v-if="authStore.hasPermission(item.permission)"
            :to="item.to"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            active-class="bg-gray-100 text-blue-600 font-medium"
            @click="isReportOpen = false"
          >
            {{ item.label }}
          </router-link>
        </template>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import {
    masterNavLinks,
    inventoryNavLinks,
    inventoryReportNavLinks,
    transactionReportNavLinks,
    inventoryPermissions,
    inventoryReportPermissions,
    transactionReportPermissions,
    reportPermissions,
    hasAnyPermission,
} from './navigationPermissions';

const authStore = useAuthStore();
const route = useRoute();

const isInventoryOpen = ref(false);
const isReportOpen = ref(false);

const hasInventoryPermission = computed(() => hasAnyPermission(authStore, inventoryPermissions));
const hasReportPermission = computed(() => hasAnyPermission(authStore, reportPermissions));
const hasInventoryReportPermission = computed(() => hasAnyPermission(authStore, inventoryReportPermissions));
const hasTransactionReportPermission = computed(() => hasAnyPermission(authStore, transactionReportPermissions));

const isInventoryActive = computed(() => route.path.startsWith('/inventory/'));
const isReportActive = computed(() => route.path.startsWith('/reports/'));

function toggleInventoryMenu() {
    isInventoryOpen.value = !isInventoryOpen.value;
    isReportOpen.value = false;
}

function toggleReportMenu() {
    isReportOpen.value = !isReportOpen.value;
    isInventoryOpen.value = false;
}
</script>
