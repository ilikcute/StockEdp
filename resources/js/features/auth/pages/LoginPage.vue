<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8 bg-white p-8 rounded-xl shadow-md border border-gray-100">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 tracking-tight">
          Sistem Inventory
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Silakan masuk ke akun Anda
        </p>
      </div>

      <!-- Error Banner -->
      <div
        v-if="authStore.error && !hasValidationErrors"
        class="p-4 bg-red-50 border-l-4 border-red-500 rounded text-sm text-red-700"
        role="alert"
      >
        {{ authStore.error }}
      </div>

      <form
        class="mt-8 space-y-6"
        @submit.prevent="handleSubmit"
      >
        <div class="space-y-4 rounded-md">
          <!-- Identity Field -->
          <div>
            <label
              for="login-identity"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Email atau Username
            </label>
            <input
              id="login-identity"
              v-model="form.login"
              type="text"
              required
              :disabled="authStore.isLoading"
              class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="{ 'border-red-500': authStore.validationErrors.login }"
              placeholder="username atau email"
            >
            <p
              v-if="authStore.validationErrors.login"
              class="mt-1 text-xs text-red-600"
            >
              {{ authStore.validationErrors.login[0] }}
            </p>
          </div>

          <!-- Password Field -->
          <div>
            <label
              for="login-password"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Kata Sandi
            </label>
            <input
              id="login-password"
              v-model="form.password"
              type="password"
              required
              :disabled="authStore.isLoading"
              class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="{ 'border-red-500': authStore.validationErrors.password }"
              placeholder="••••••••"
            >
            <p
              v-if="authStore.validationErrors.password"
              class="mt-1 text-xs text-red-600"
            >
              {{ authStore.validationErrors.password[0] }}
            </p>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="authStore.isLoading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-semibold rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-55 disabled:cursor-not-allowed"
          >
            <span v-if="authStore.isLoading">Memverifikasi...</span>
            <span v-else>Masuk</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { useAuthStore } from '../stores/use_auth_store.js';
import { useRouter, useRoute } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const form = reactive({
    login: '',
    password: '',
});

const hasValidationErrors = computed(() => {
    return Object.keys(authStore.validationErrors).length > 0;
});

async function handleSubmit() {
    try {
        const success = await authStore.login({
            login: form.login,
            password: form.password,
        });

        if (success) {
            // Redirect ke halaman yang dituju sebelumnya, atau default ke home/profile
            const redirectPath = route.query.redirect ?? '/profile';
            router.push(redirectPath);
        }
    } catch {
        // Error validation dan banner ditangani oleh Store secara otomatis.
    }
}
</script>
