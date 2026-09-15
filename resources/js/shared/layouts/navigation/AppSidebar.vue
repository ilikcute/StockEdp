<template>
  <div>
    <!-- Mobile Backdrop Overlay -->
    <div
      v-if="isMobileOpen"
      class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs z-40 lg:hidden transition-opacity duration-200"
      aria-hidden="true"
      @click="$emit('closeMobile')"
    />

    <!-- Sidebar Aside -->
    <aside
      :class="[
        'fixed top-0 bottom-0 left-0 z-50 flex flex-col bg-white border-r border-gray-200 transition-all duration-200 ease-in-out',
        // Mobile visibility
        isMobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0',
        // Desktop width based on collapsed state
        isCollapsed ? 'lg:w-20' : 'w-64'
      ]"
    >
      <!-- Sidebar Header -->
      <div class="h-16 flex items-center justify-between px-4 border-b border-gray-100 shrink-0">
        <router-link
          to="/dashboard"
          class="flex items-center gap-2.5 overflow-hidden group"
          @click="$emit('closeMobile')"
        >
          <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-black shadow-xs shrink-0 group-hover:bg-indigo-700 transition-colors">
            EDP
          </span>
          <div
            v-show="!isCollapsed"
            class="flex flex-col min-w-0 transition-opacity duration-150"
          >
            <span class="text-sm font-bold text-gray-900 truncate tracking-tight">StockEdp</span>
            <span class="text-[10px] text-gray-400 font-medium truncate">Inventory & WMS</span>
          </div>
        </router-link>

        <!-- Toggle Collapse Button (Desktop) -->
        <button
          type="button"
          class="hidden lg:flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer"
          :title="isCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
          @click="$emit('update:isCollapsed', !isCollapsed)"
        >
          <svg
            class="w-4 h-4 transition-transform duration-200"
            :class="{ 'rotate-180': isCollapsed }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
            />
          </svg>
        </button>

        <!-- Close Button (Mobile) -->
        <button
          type="button"
          class="lg:hidden p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer"
          aria-label="Tutup menu"
          @click="$emit('closeMobile')"
        >
          <svg
            class="w-5 h-5"
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

      <!-- Navigation Content (Scrollable) -->
      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4 text-xs font-medium custom-scrollbar">
        <!-- 1. Main Menu Section -->
        <div>
          <div
            v-if="!isCollapsed"
            class="px-2.5 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400"
          >
            Menu Utama
          </div>
          <div class="space-y-1">
            <router-link
              v-if="authStore.hasPermission('dashboard.view')"
              to="/dashboard"
              :class="navLinkClass('/dashboard')"
              :title="isCollapsed ? 'Dashboard' : ''"
              @click="$emit('closeMobile')"
            >
              <svg
                class="w-4 h-4 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                />
              </svg>
              <span
                v-show="!isCollapsed"
                class="truncate"
              >Dashboard</span>
            </router-link>
          </div>
        </div>

        <!-- 2. Persediaan Section (Accordion) -->
        <div v-if="hasInventoryPermission">
          <div
            v-if="!isCollapsed"
            class="flex items-center justify-between px-2.5 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 cursor-pointer select-none group"
            @click="isInventoryOpen = !isInventoryOpen"
          >
            <span>Persediaan</span>
            <svg
              class="w-3.5 h-3.5 transition-transform duration-150 text-gray-400 group-hover:text-gray-600"
              :class="{ 'rotate-180': isInventoryOpen }"
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
          </div>

          <div
            v-show="!isCollapsed && isInventoryOpen || isCollapsed"
            class="space-y-1 mt-0.5"
          >
            <template
              v-for="item in inventoryNavLinks"
              :key="item.to"
            >
              <router-link
                v-if="authStore.hasPermission(item.permission)"
                :to="item.to"
                :class="navLinkClass(item.to)"
                :title="isCollapsed ? item.label : ''"
                @click="$emit('closeMobile')"
              >
                <!-- Contextual icons for inventory -->
                <svg
                  v-if="item.to.includes('store-allocations')"
                  class="w-4 h-4 shrink-0 text-amber-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                  />
                </svg>
                <svg
                  v-else-if="item.to.includes('movements')"
                  class="w-4 h-4 shrink-0 text-indigo-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                  />
                </svg>
                <svg
                  v-else-if="item.to.includes('receipts')"
                  class="w-4 h-4 shrink-0 text-emerald-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                  />
                </svg>
                <svg
                  v-else-if="item.to.includes('issues')"
                  class="w-4 h-4 shrink-0 text-amber-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                  />
                </svg>
                <svg
                  v-else-if="item.to.includes('transfers')"
                  class="w-4 h-4 shrink-0 text-blue-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                  />
                </svg>
                <svg
                  v-else-if="item.to.includes('replenishment')"
                  class="w-4 h-4 shrink-0 text-purple-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z"
                  />
                </svg>
                <svg
                  v-else-if="item.to.includes('adjustments')"
                  class="w-4 h-4 shrink-0 text-slate-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                  />
                </svg>
                <svg
                  v-else
                  class="w-4 h-4 shrink-0 text-teal-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                  />
                </svg>
                <span
                  v-show="!isCollapsed"
                  class="truncate"
                >{{ item.label }}</span>
              </router-link>
            </template>
          </div>
        </div>

        <!-- 3. Laporan Section (Accordion) -->
        <div v-if="hasReportPermission">
          <div
            v-if="!isCollapsed"
            class="flex items-center justify-between px-2.5 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 cursor-pointer select-none group"
            @click="isReportsOpen = !isReportsOpen"
          >
            <span>Laporan & Analitik</span>
            <svg
              class="w-3.5 h-3.5 transition-transform duration-150 text-gray-400 group-hover:text-gray-600"
              :class="{ 'rotate-180': isReportsOpen }"
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
          </div>

          <div
            v-show="!isCollapsed && isReportsOpen || isCollapsed"
            class="space-y-1 mt-0.5"
          >
            <!-- Laporan Persediaan -->
            <template
              v-for="item in inventoryReportNavLinks"
              :key="item.to"
            >
              <router-link
                v-if="authStore.hasPermission(item.permission)"
                :to="item.to"
                :class="navLinkClass(item.to)"
                :title="isCollapsed ? item.label : ''"
                @click="$emit('closeMobile')"
              >
                <svg
                  class="w-4 h-4 shrink-0 text-indigo-400"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
                <span
                  v-show="!isCollapsed"
                  class="truncate"
                >{{ item.label }}</span>
              </router-link>
            </template>

            <!-- Laporan Transaksi -->
            <template
              v-for="item in transactionReportNavLinks"
              :key="item.to"
            >
              <router-link
                v-if="authStore.hasPermission(item.permission)"
                :to="item.to"
                :class="navLinkClass(item.to)"
                :title="isCollapsed ? item.label : ''"
                @click="$emit('closeMobile')"
              >
                <svg
                  class="w-4 h-4 shrink-0 text-slate-400"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
                <span
                  v-show="!isCollapsed"
                  class="truncate"
                >{{ item.label }}</span>
              </router-link>
            </template>
          </div>
        </div>

        <!-- 4. Master Data Section (Accordion) -->
        <div v-if="hasMasterPermission">
          <div
            v-if="!isCollapsed"
            class="flex items-center justify-between px-2.5 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 cursor-pointer select-none group"
            @click="isMasterOpen = !isMasterOpen"
          >
            <span>Master Data</span>
            <svg
              class="w-3.5 h-3.5 transition-transform duration-150 text-gray-400 group-hover:text-gray-600"
              :class="{ 'rotate-180': isMasterOpen }"
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
          </div>

          <div
            v-show="!isCollapsed && isMasterOpen || isCollapsed"
            class="space-y-1 mt-0.5"
          >
            <template
              v-for="item in masterNavLinks"
              :key="item.to"
            >
              <router-link
                v-if="authStore.hasPermission(item.permission)"
                :to="item.to"
                :class="navLinkClass(item.to)"
                :title="isCollapsed ? item.label : ''"
                @click="$emit('closeMobile')"
              >
                <!-- Icons for master items -->
                <svg
                  v-if="item.to === '/products'"
                  class="w-4 h-4 shrink-0 text-blue-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                  />
                </svg>
                <svg
                  v-else-if="item.to === '/stores'"
                  class="w-4 h-4 shrink-0 text-amber-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                  />
                </svg>
                <svg
                  v-else-if="item.to === '/locations'"
                  class="w-4 h-4 shrink-0 text-emerald-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                </svg>
                <svg
                  v-else-if="item.to === '/suppliers'"
                  class="w-4 h-4 shrink-0 text-indigo-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"
                  />
                </svg>
                <svg
                  v-else-if="item.to === '/departments'"
                  class="w-4 h-4 shrink-0 text-teal-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                  />
                </svg>
                <svg
                  v-else-if="item.to === '/users'"
                  class="w-4 h-4 shrink-0 text-rose-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                  />
                </svg>
                <svg
                  v-else
                  class="w-4 h-4 shrink-0 text-gray-400"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                  />
                </svg>
                <span
                  v-show="!isCollapsed"
                  class="truncate"
                >{{ item.label }}</span>
              </router-link>
            </template>
          </div>
        </div>

        <!-- 5. User Account Section -->
        <div>
          <div
            v-if="!isCollapsed"
            class="px-2.5 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400"
          >
            Akun
          </div>
          <div class="space-y-1">
            <router-link
              to="/profile"
              :class="navLinkClass('/profile')"
              :title="isCollapsed ? 'Profil Saya' : ''"
              @click="$emit('closeMobile')"
            >
              <svg
                class="w-4 h-4 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
              <span
                v-show="!isCollapsed"
                class="truncate"
              >Profil Saya</span>
            </router-link>
          </div>
        </div>
      </nav>

      <!-- Sidebar Footer (User Info & Logout) -->
      <div class="p-3 border-t border-gray-100 bg-gray-50/70 shrink-0">
        <div
          v-show="!isCollapsed"
          class="flex items-center justify-between gap-2"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shrink-0">
              {{ (authStore.user?.name || 'U').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0">
              <p class="text-xs font-bold text-gray-900 truncate">
                {{ authStore.user?.name }}
              </p>
              <p class="text-[10px] text-gray-500 font-mono truncate">
                {{ authStore.user?.username }}
              </p>
            </div>
          </div>

          <button
            type="button"
            class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition-colors shrink-0 cursor-pointer"
            title="Keluar dari sistem"
            @click="$emit('logout')"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
          </button>
        </div>

        <!-- Collapsed Logout Icon -->
        <div
          v-show="isCollapsed"
          class="flex justify-center"
        >
          <button
            type="button"
            class="p-2 rounded-lg text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
            title="Keluar dari sistem"
            @click="$emit('logout')"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
          </button>
        </div>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import {
  masterNavLinks,
  inventoryNavLinks,
  inventoryReportNavLinks,
  transactionReportNavLinks,
  masterPermissions,
  inventoryPermissions,
  reportPermissions,
  hasAnyPermission,
} from './navigationPermissions';

defineProps({
  isMobileOpen: { type: Boolean, default: false },
  isCollapsed: { type: Boolean, default: false },
});

defineEmits(['update:isCollapsed', 'closeMobile', 'logout']);

const authStore = useAuthStore();
const route = useRoute();

const isMasterOpen = ref(false);
const isInventoryOpen = ref(true);
const isReportsOpen = ref(false);

const hasMasterPermission = computed(() => hasAnyPermission(authStore, masterPermissions));
const hasInventoryPermission = computed(() => hasAnyPermission(authStore, inventoryPermissions));
const hasReportPermission = computed(() => hasAnyPermission(authStore, reportPermissions));

// Auto-expand section when current route changes
watch(
  () => route.path,
  (path) => {
    if (path.startsWith('/inventory')) {
      isInventoryOpen.value = true;
    } else if (path.startsWith('/reports')) {
      isReportsOpen.value = true;
    } else if (['/products', '/categories', '/units', '/suppliers', '/locations', '/stores', '/departments', '/users'].some(p => path.startsWith(p))) {
      isMasterOpen.value = true;
    }
  },
  { immediate: true }
);

const navLinkClass = (targetPath) => {
  const isActive = route.path === targetPath || (targetPath !== '/dashboard' && route.path.startsWith(targetPath));
  return [
    'flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors group cursor-pointer',
    isActive
      ? 'bg-indigo-50 text-indigo-700 font-semibold shadow-2xs'
      : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/80'
  ];
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
