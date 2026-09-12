<template>
  <div class="min-h-[60vh] flex flex-col items-center justify-center text-center px-4">
    <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-md border border-gray-100">
      <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg
          class="w-8 h-8 text-red-600"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>

      <p class="text-xs font-semibold text-red-600 uppercase tracking-wider mb-1">
        Error 403
      </p>
      
      <h1 class="text-2xl font-bold text-gray-900 mb-2">
        Akses Ditolak
      </h1>
      
      <p class="text-sm text-gray-600 mb-6">
        Anda tidak memiliki izin atau hak akses untuk membuka halaman ini.
      </p>

      <router-link
        to="/profile"
        class="inline-flex w-full items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
      >
        Kembali ke Profil Saya
      </router-link>
    </div>
  </div>
</template>

<script setup>
// Halaman 403 — tidak memerlukan logika tambahan.
</script>
