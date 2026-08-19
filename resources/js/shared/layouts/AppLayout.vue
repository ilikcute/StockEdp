<template>
  <div class="app-layout min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-white border-b border-gray-100 shadow-2xs sticky top-0 z-30 pt-safe">
      <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-14 sm:h-16 flex items-center justify-between">
        <div class="flex items-center space-x-4 lg:space-x-8">
          <router-link
            to="/dashboard"
            class="flex items-center gap-2 font-bold text-gray-900 tracking-tight"
          >
            <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs font-black shadow-xs">
              EDP
            </span>
            <span class="text-base sm:text-lg font-bold">StockEdp</span>
          </router-link>

          <!-- Desktop Navigation -->
          <DesktopNavigation v-if="authStore.isAuthenticated" />
        </div>

        <div class="flex items-center space-x-2 sm:space-x-3">
          <!-- User Dropdown / Info (Desktop) -->
          <div
            v-if="authStore.isAuthenticated"
            class="hidden lg:flex items-center space-x-4"
          >
            <div class="text-right">
              <p class="text-xs font-bold text-gray-900">
                {{ authStore.user?.name }}
              </p>
              <p class="text-[11px] text-gray-500 font-mono">
                {{ authStore.user?.username }}
              </p>
            </div>

            <button
              class="px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors cursor-pointer"
              @click="handleLogout"
            >
              Keluar
            </button>
          </div>

          <!-- Mobile Menu Trigger Button -->
          <button
            v-if="authStore.isAuthenticated"
            type="button"
            class="lg:hidden p-2 rounded-lg text-gray-700 hover:text-blue-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 cursor-pointer min-w-[40px] min-h-[40px] flex items-center justify-center"
            :aria-expanded="isMobileMenuOpen"
            aria-controls="mobile-navigation"
            aria-label="Buka menu navigasi"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
          >
            <svg
              v-if="!isMobileMenuOpen"
              class="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
            <svg
              v-else
              class="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Navigation Drawer -->
      <MobileNavigation
        v-if="authStore.isAuthenticated"
        :is-open="isMobileMenuOpen"
        @close="isMobileMenuOpen = false"
        @logout="handleLogout"
      />
    </header>

    <!-- Main Content with responsive padding & bottom-bar offset on mobile -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 pb-20 md:pb-8">
      <router-view />
    </main>

    <!-- Mobile Bottom Navigation Bar -->
    <MobileBottomBar
      v-if="authStore.isAuthenticated"
      @toggle-menu="isMobileMenuOpen = !isMobileMenuOpen"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import DesktopNavigation from './navigation/DesktopNavigation.vue';
import MobileNavigation from './navigation/MobileNavigation.vue';
import MobileBottomBar from './navigation/MobileBottomBar.vue';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const isMobileMenuOpen = ref(false);

watch(() => route.path, () => {
    isMobileMenuOpen.value = false;
});

async function handleLogout() {
    isMobileMenuOpen.value = false;
    await authStore.logout();
    router.push('/login');
}
</script>
