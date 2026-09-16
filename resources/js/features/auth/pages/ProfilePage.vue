<template>
  <div class="max-w-2xl mx-auto mt-10 p-6 bg-white rounded-xl shadow-md border border-gray-100">
    <!-- Header -->
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
        :disabled="authStore.isLoading"
        @click="handleLogout"
      >
        Keluar
      </button>
    </div>

    <!-- Global Error -->
    <div
      v-if="authStore.error"
      class="mt-4 p-3 text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-md flex items-center justify-between"
    >
      <span>{{ authStore.error }}</span>
      <button
        type="button"
        class="text-rose-500 hover:text-rose-700 text-xs font-semibold cursor-pointer"
        @click="authStore.error = null"
      >
        Tutup
      </button>
    </div>

    <!-- Info Akun (read-only) -->
    <div class="mt-6 space-y-4">
      <div class="grid grid-cols-3 gap-4 py-2 border-b border-gray-50">
        <span class="text-sm font-medium text-gray-500">Username</span>
        <span class="col-span-2 text-sm text-gray-900">{{ authStore.user?.username }}</span>
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

    <!-- Edit Identitas -->
    <div class="mt-8 pt-6 border-t border-gray-100">
      <h2 class="text-base font-bold text-gray-900">
        Edit Identitas
      </h2>
      <p class="text-xs text-gray-500 mb-4">
        Perbarui nama dan alamat email yang tertera pada akun Anda.
      </p>

      <form
        class="space-y-4"
        @submit.prevent="handleUpdateProfile"
      >
        <div>
          <label
            for="profile-name"
            class="block text-sm font-medium text-gray-700"
          >
            Nama
          </label>
          <input
            id="profile-name"
            v-model="profileForm.name"
            type="text"
            required
            maxlength="100"
            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
          <p
            v-if="profileFieldErrors.name"
            class="mt-1 text-xs text-rose-600"
          >
            {{ profileFieldErrors.name[0] }}
          </p>
        </div>

        <div>
          <label
            for="profile-email"
            class="block text-sm font-medium text-gray-700"
          >
            Email
          </label>
          <input
            id="profile-email"
            v-model="profileForm.email"
            type="email"
            required
            maxlength="150"
            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
          <p
            v-if="profileFieldErrors.email"
            class="mt-1 text-xs text-rose-600"
          >
            {{ profileFieldErrors.email[0] }}
          </p>
        </div>

        <div
          v-if="profileSaveSuccess"
          class="text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md p-3"
        >
          Profil berhasil diperbarui.
        </div>

        <div class="flex items-center gap-3">
          <button
            type="submit"
            class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            :disabled="authStore.isLoading || profileFormPristine"
          >
            {{ authStore.isLoading ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </button>
          <button
            v-if="profileFormPristine === false"
            type="button"
            class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors cursor-pointer"
            @click="resetProfileForm"
          >
            Batal
          </button>
        </div>
      </form>
    </div>

    <!-- Ganti Kata Sandi -->
    <div class="mt-8 pt-6 border-t border-gray-100">
      <h2 class="text-base font-bold text-gray-900">
        Ganti Kata Sandi
      </h2>
      <p class="text-xs text-gray-500 mb-4">
        Pastikan kata sandi baru Anda kuat dan tidak digunakan di tempat lain.
      </p>

      <form
        class="space-y-4"
        @submit.prevent="handleUpdatePassword"
      >
        <div>
          <label
            for="profile-current-password"
            class="block text-sm font-medium text-gray-700"
          >
            Kata Sandi Saat Ini
          </label>
          <input
            id="profile-current-password"
            v-model="passwordForm.current_password"
            type="password"
            required
            autocomplete="current-password"
            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
          <p
            v-if="passwordFieldErrors.current_password"
            class="mt-1 text-xs text-rose-600"
          >
            {{ passwordFieldErrors.current_password[0] }}
          </p>
        </div>

        <div>
          <label
            for="profile-new-password"
            class="block text-sm font-medium text-gray-700"
          >
            Kata Sandi Baru
          </label>
          <input
            id="profile-new-password"
            v-model="passwordForm.password"
            type="password"
            required
            minlength="8"
            maxlength="100"
            autocomplete="new-password"
            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
          <p
            v-if="passwordFieldErrors.password"
            class="mt-1 text-xs text-rose-600"
          >
            {{ passwordFieldErrors.password[0] }}
          </p>
        </div>

        <div>
          <label
            for="profile-password-confirmation"
            class="block text-sm font-medium text-gray-700"
          >
            Konfirmasi Kata Sandi Baru
          </label>
          <input
            id="profile-password-confirmation"
            v-model="passwordForm.password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
        </div>

        <div
          v-if="passwordSaveSuccess"
          class="text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md p-3"
        >
          Kata sandi berhasil diperbarui.
        </div>

        <button
          type="submit"
          class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          :disabled="authStore.isLoading || passwordFormPristine"
        >
          {{ authStore.isLoading ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useAuthStore } from '../stores/use_auth_store.js';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const profileSaveSuccess = ref(false);
const passwordSaveSuccess = ref(false);

const profileForm = reactive({
    name: '',
    email: '',
});

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const initialUser = () => ({
    name: authStore.user?.name ?? '',
    email: authStore.user?.email ?? '',
});

Object.assign(profileForm, initialUser());

const profileFormPristine = computed(() => {
    const initial = initialUser();
    return profileForm.name === initial.name && profileForm.email === initial.email;
});

const passwordFormPristine = computed(() => {
    return !passwordForm.current_password && !passwordForm.password && !passwordForm.password_confirmation;
});

const profileFieldErrors = computed(() => {
    const errors = authStore.validationErrors ?? {};
    return {
        name: errors.name ?? null,
        email: errors.email ?? null,
    };
});

const passwordFieldErrors = computed(() => {
    const errors = authStore.validationErrors ?? {};
    return {
        current_password: errors.current_password ?? null,
        password: errors.password ?? null,
    };
});

const resetProfileForm = () => {
    Object.assign(profileForm, initialUser());
    profileSaveSuccess.value = false;
};

async function handleUpdateProfile() {
    profileSaveSuccess.value = false;
    authStore.error = null;
    authStore.validationErrors = {};
    try {
        const ok = await authStore.updateProfile({
            name: profileForm.name,
            email: profileForm.email,
        });
        if (ok && authStore.user) {
            Object.assign(profileForm, initialUser());
            profileSaveSuccess.value = true;
        }
    } catch {
        // Error ditampilkan dari store (authStore.error & validationErrors)
    }
}

async function handleUpdatePassword() {
    passwordSaveSuccess.value = false;
    authStore.error = null;
    authStore.validationErrors = {};
    try {
        const ok = await authStore.updatePassword({
            current_password: passwordForm.current_password,
            password: passwordForm.password,
            password_confirmation: passwordForm.password_confirmation,
        });
        if (ok) {
            Object.assign(passwordForm, {
                current_password: '',
                password: '',
                password_confirmation: '',
            });
            passwordSaveSuccess.value = true;
        }
    } catch {
        // Error ditampilkan dari store
    }
}

async function handleLogout() {
    await authStore.logout();
    router.push('/login');
}
</script>