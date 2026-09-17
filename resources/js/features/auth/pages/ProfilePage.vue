<template>
  <div class="max-w-5xl mx-auto space-y-3.5">
    <!-- Top Header Bar -->
    <div class="bg-white rounded-xl shadow-2xs border border-gray-200 px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
            />
          </svg>
        </div>
        <div>
          <h1 class="text-sm font-bold text-gray-900 leading-tight">
            Profil Pengguna
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            Informasi akun aktif, kredensial login, dan pengaturan keamanan.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200/80 hover:bg-rose-100 rounded-lg transition-colors cursor-pointer shadow-2xs"
          :disabled="authStore.isLoading"
          @click="showLogoutModal = true"
        >
          <svg
            class="w-3.5 h-3.5 text-rose-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
          <span>Keluar Sesi</span>
        </button>
      </div>
    </div>

    <!-- Global Error Notification -->
    <div
      v-if="authStore.error"
      class="p-3 text-xs text-rose-800 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between shadow-2xs animate-in fade-in"
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
        <span>{{ authStore.error }}</span>
      </div>
      <button
        type="button"
        class="text-rose-600 hover:text-rose-800 text-xs font-semibold cursor-pointer"
        @click="authStore.error = null"
      >
        Tutup
      </button>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-start">
      <!-- LEFT COLUMN: User Card & Permissions (col-span-5) -->
      <div class="lg:col-span-5 space-y-3.5">
        <!-- Card 1: User Identity Summary -->
        <div class="bg-white rounded-xl shadow-2xs border border-gray-200 overflow-hidden">
          <!-- Banner Header -->
          <div class="bg-gradient-to-r from-indigo-600 via-indigo-700 to-indigo-800 px-4 pt-4 pb-8">
            <div class="flex items-center justify-between text-indigo-100 text-[11px]">
              <span class="font-mono font-medium">ID Akun: #{{ authStore.user?.id ?? '-' }}</span>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-100 border border-emerald-400/30">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                {{ authStore.user?.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </div>
          </div>

          <!-- Avatar & Details overlapping banner -->
          <div class="px-4 pb-4 -mt-6">
            <div class="flex items-end gap-3">
              <div class="w-13 h-13 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-bold text-lg flex items-center justify-center shadow-md ring-3 ring-white shrink-0">
                {{ userInitials }}
              </div>
              <div class="min-w-0 pb-0.5">
                <h2 class="text-sm font-bold text-gray-900 truncate">
                  {{ authStore.user?.name || '-' }}
                </h2>
                <p class="text-[11px] font-mono text-gray-500 truncate">
                  @{{ authStore.user?.username || '-' }}
                </p>
              </div>
            </div>

            <div class="mt-3.5 pt-3 border-t border-gray-100 space-y-2 text-xs">
              <div class="flex items-center justify-between">
                <span class="text-gray-500 text-[11px]">Alamat Email</span>
                <span
                  class="font-medium text-gray-800 truncate max-w-[200px]"
                  :title="authStore.user?.email"
                >
                  {{ authStore.user?.email || '-' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-500 text-[11px]">Role / Peran</span>
                <div class="flex flex-wrap gap-1 justify-end">
                  <span
                    v-for="role in authStore.user?.roles"
                    :key="role"
                    class="px-2 py-0.5 text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200/70 rounded-md"
                  >
                    {{ role }}
                  </span>
                  <span
                    v-if="!authStore.user?.roles?.length"
                    class="text-gray-400 text-[11px]"
                  >-</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 2: Permissions (Daftar Izin) -->
        <div class="bg-white rounded-xl shadow-2xs border border-gray-200 p-3.5">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-1.5">
              <svg
                class="w-4 h-4 text-indigo-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                />
              </svg>
              <h3 class="text-xs font-bold text-gray-900">
                Hak Akses & Izin
              </h3>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-mono font-semibold bg-gray-100 text-gray-600 rounded-full border border-gray-200">
              {{ authStore.user?.permissions?.length || 0 }} Izin
            </span>
          </div>

          <!-- Quick Search for permissions if many -->
          <div
            v-if="(authStore.user?.permissions?.length || 0) > 8"
            class="mb-2"
          >
            <div class="relative">
              <input
                v-model="permissionSearch"
                type="text"
                placeholder="Cari izin akses..."
                class="w-full text-xs rounded-lg border border-gray-200 bg-gray-50/70 py-1 pl-7 pr-2.5 text-gray-700 placeholder:text-gray-400 focus:bg-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              >
              <svg
                class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
              </svg>
            </div>
          </div>

          <!-- Permissions Tag Cloud -->
          <div class="max-h-48 overflow-y-auto custom-scrollbar p-1.5 bg-gray-50/60 rounded-lg border border-gray-100 flex flex-wrap gap-1">
            <span
              v-for="perm in filteredPermissions"
              :key="perm"
              class="px-1.5 py-0.5 text-[10px] font-mono text-gray-700 bg-white border border-gray-200/80 rounded shadow-2xs hover:border-indigo-300 transition-colors"
            >
              {{ perm }}
            </span>
            <div
              v-if="filteredPermissions.length === 0"
              class="w-full text-center py-3 text-[11px] text-gray-400"
            >
              Tidak ada izin yang cocok
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Profile Edit & Password Change Forms (col-span-7) -->
      <div class="lg:col-span-7 space-y-3.5">
        <!-- Card 1: Edit Identitas -->
        <div class="bg-white rounded-xl shadow-2xs border border-gray-200 p-4">
          <div class="flex items-center gap-2 pb-3 mb-3.5 border-b border-gray-100">
            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
            </div>
            <div>
              <h2 class="text-xs font-bold text-gray-900">
                Edit Identitas
              </h2>
              <p class="text-[11px] text-gray-500">
                Perbarui nama lengkap dan alamat email akun Anda.
              </p>
            </div>
          </div>

          <form
            class="space-y-3"
            @submit.prevent="handleUpdateProfile"
          >
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <!-- Nama -->
              <div>
                <label
                  for="profile-name"
                  class="block text-xs font-medium text-gray-700 mb-1"
                >
                  Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input
                  id="profile-name"
                  v-model="profileForm.name"
                  type="text"
                  required
                  maxlength="100"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  placeholder="Nama lengkap Anda"
                >
                <p
                  v-if="profileFieldErrors.name"
                  class="mt-1 text-[11px] text-rose-600"
                >
                  {{ profileFieldErrors.name[0] }}
                </p>
              </div>

              <!-- Email -->
              <div>
                <label
                  for="profile-email"
                  class="block text-xs font-medium text-gray-700 mb-1"
                >
                  Alamat Email <span class="text-rose-500">*</span>
                </label>
                <input
                  id="profile-email"
                  v-model="profileForm.email"
                  type="email"
                  required
                  maxlength="150"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  placeholder="email@perusahaan.com"
                >
                <p
                  v-if="profileFieldErrors.email"
                  class="mt-1 text-[11px] text-rose-600"
                >
                  {{ profileFieldErrors.email[0] }}
                </p>
              </div>
            </div>

            <!-- Success Alert -->
            <div
              v-if="profileSaveSuccess"
              class="flex items-center justify-between text-xs text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2 animate-in fade-in"
            >
              <div class="flex items-center gap-1.5">
                <svg
                  class="w-4 h-4 text-emerald-600 shrink-0"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  />
                </svg>
                <span>Identitas profil berhasil diperbarui.</span>
              </div>
              <button
                type="button"
                class="text-emerald-600 hover:text-emerald-800 text-[10px] font-semibold cursor-pointer"
                @click="profileSaveSuccess = false"
              >
                Tutup
              </button>
            </div>

            <!-- Buttons -->
            <div class="flex items-center gap-2 pt-1">
              <button
                type="submit"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-2xs disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                :disabled="authStore.isLoading || profileFormPristine"
              >
                <svg
                  v-if="authStore.isLoading"
                  class="w-3.5 h-3.5 animate-spin"
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
                    d="M4 12a8 8 0 018-8v8z"
                  />
                </svg>
                <svg
                  v-else
                  class="w-3.5 h-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  />
                </svg>
                <span>{{ authStore.isLoading ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
              </button>
              <button
                v-if="!profileFormPristine"
                type="button"
                class="px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors cursor-pointer shadow-2xs"
                @click="resetProfileForm"
              >
                Batal
              </button>
            </div>
          </form>
        </div>

        <!-- Card 2: Ganti Kata Sandi -->
        <div class="bg-white rounded-xl shadow-2xs border border-gray-200 p-4">
          <div class="flex items-center gap-2 pb-3 mb-3.5 border-b border-gray-100">
            <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                />
              </svg>
            </div>
            <div>
              <h2 class="text-xs font-bold text-gray-900">
                Ganti Kata Sandi
              </h2>
              <p class="text-[11px] text-gray-500">
                Gunakan kata sandi baru yang kuat (minimal 8 karakter).
              </p>
            </div>
          </div>

          <form
            class="space-y-3"
            @submit.prevent="handleUpdatePassword"
          >
            <!-- Current Password -->
            <div>
              <label
                for="profile-current-password"
                class="block text-xs font-medium text-gray-700 mb-1"
              >
                Kata Sandi Saat Ini <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input
                  id="profile-current-password"
                  v-model="passwordForm.current_password"
                  :type="showCurrentPassword ? 'text' : 'password'"
                  required
                  autocomplete="current-password"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono"
                  placeholder="Masukkan kata sandi lama"
                >
                <button
                  type="button"
                  class="absolute right-2 top-1.5 text-gray-400 hover:text-gray-600 cursor-pointer"
                  tabindex="-1"
                  title="Lihat / sembunyikan kata sandi"
                  @click="showCurrentPassword = !showCurrentPassword"
                >
                  <svg
                    v-if="showCurrentPassword"
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                    />
                  </svg>
                  <svg
                    v-else
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                </button>
              </div>
              <p
                v-if="passwordFieldErrors.current_password"
                class="mt-1 text-[11px] text-rose-600"
              >
                {{ passwordFieldErrors.current_password[0] }}
              </p>
            </div>

            <!-- New Password & Confirmation Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label
                  for="profile-new-password"
                  class="block text-xs font-medium text-gray-700 mb-1"
                >
                  Kata Sandi Baru <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <input
                    id="profile-new-password"
                    v-model="passwordForm.password"
                    :type="showNewPassword ? 'text' : 'password'"
                    required
                    minlength="8"
                    maxlength="100"
                    autocomplete="new-password"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono"
                    placeholder="Minimal 8 karakter"
                  >
                  <button
                    type="button"
                    class="absolute right-2 top-1.5 text-gray-400 hover:text-gray-600 cursor-pointer"
                    tabindex="-1"
                    title="Lihat / sembunyikan kata sandi"
                    @click="showNewPassword = !showNewPassword"
                  >
                    <svg
                      v-if="showNewPassword"
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                      />
                    </svg>
                    <svg
                      v-else
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                      />
                    </svg>
                  </button>
                </div>
                <p
                  v-if="passwordFieldErrors.password"
                  class="mt-1 text-[11px] text-rose-600"
                >
                  {{ passwordFieldErrors.password[0] }}
                </p>
              </div>

              <div>
                <label
                  for="profile-password-confirmation"
                  class="block text-xs font-medium text-gray-700 mb-1"
                >
                  Ulangi Kata Sandi Baru <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <input
                    id="profile-password-confirmation"
                    v-model="passwordForm.password_confirmation"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    :class="[
                      'block w-full rounded-lg border bg-white px-2.5 py-1.5 pr-8 text-xs shadow-2xs focus:outline-none focus:ring-1 font-mono',
                      passwordMismatch ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                    ]"
                    placeholder="Ulangi kata sandi baru"
                  >
                  <button
                    type="button"
                    class="absolute right-2 top-1.5 text-gray-400 hover:text-gray-600 cursor-pointer"
                    tabindex="-1"
                    title="Lihat / sembunyikan kata sandi"
                    @click="showConfirmPassword = !showConfirmPassword"
                  >
                    <svg
                      v-if="showConfirmPassword"
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                      />
                    </svg>
                    <svg
                      v-else
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                      />
                    </svg>
                  </button>
                </div>
                <p
                  v-if="passwordMismatch"
                  class="mt-1 text-[11px] text-rose-600 font-medium"
                >
                  Konfirmasi kata sandi tidak cocok.
                </p>
              </div>
            </div>

            <!-- Success Alert -->
            <div
              v-if="passwordSaveSuccess"
              class="flex items-center justify-between text-xs text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2 animate-in fade-in"
            >
              <div class="flex items-center gap-1.5">
                <svg
                  class="w-4 h-4 text-emerald-600 shrink-0"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  />
                </svg>
                <span>Kata sandi berhasil diperbarui.</span>
              </div>
              <button
                type="button"
                class="text-emerald-600 hover:text-emerald-800 text-[10px] font-semibold cursor-pointer"
                @click="passwordSaveSuccess = false"
              >
                Tutup
              </button>
            </div>

            <!-- Submit Button -->
            <div class="pt-1">
              <button
                type="submit"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-2xs disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                :disabled="authStore.isLoading || passwordFormPristine || passwordMismatch"
              >
                <svg
                  v-if="authStore.isLoading"
                  class="w-3.5 h-3.5 animate-spin"
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
                    d="M4 12a8 8 0 018-8v8z"
                  />
                </svg>
                <svg
                  v-else
                  class="w-3.5 h-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                  />
                </svg>
                <span>{{ authStore.isLoading ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal Logout -->
    <BaseConfirmation
      v-model="showLogoutModal"
      title="Keluar dari Akun?"
      description="Apakah Anda yakin ingin mengakhiri sesi aktif dan keluar dari aplikasi StockEdp?"
      confirm-label="Ya, Keluar"
      cancel-label="Batal"
      variant="danger"
      :loading="authStore.isLoading"
      @confirm="confirmLogout"
      @cancel="showLogoutModal = false"
    />
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useAuthStore } from '../stores/use_auth_store.js';
import { useRouter } from 'vue-router';
import BaseConfirmation from '@/shared/components/BaseConfirmation.vue';

const authStore = useAuthStore();
const router = useRouter();

const profileSaveSuccess = ref(false);
const passwordSaveSuccess = ref(false);
const showLogoutModal = ref(false);

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const permissionSearch = ref('');

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

const userInitials = computed(() => {
    const name = authStore.user?.name || authStore.user?.username || 'U';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

const filteredPermissions = computed(() => {
    const list = authStore.user?.permissions || [];
    if (!permissionSearch.value.trim()) return list;
    const q = permissionSearch.value.toLowerCase().trim();
    return list.filter(p => p.toLowerCase().includes(q));
});

const profileFormPristine = computed(() => {
    const initial = initialUser();
    return profileForm.name === initial.name && profileForm.email === initial.email;
});

const passwordFormPristine = computed(() => {
    return !passwordForm.current_password && !passwordForm.password && !passwordForm.password_confirmation;
});

const passwordMismatch = computed(() => {
    if (!passwordForm.password || !passwordForm.password_confirmation) return false;
    return passwordForm.password !== passwordForm.password_confirmation;
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

async function confirmLogout() {
    showLogoutModal.value = false;
    await authStore.logout();
    router.push('/login');
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 5px;
  width: 5px;
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