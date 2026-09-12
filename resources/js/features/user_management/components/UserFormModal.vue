<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-xs flex items-center justify-center p-4"
    @click.self="$emit('close')"
  >
    <div class="relative w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl border border-gray-200 space-y-5">
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-gray-100 pb-4">
        <div>
          <h2 class="text-lg font-bold text-gray-900">
            {{ isEditing ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ isEditing ? 'Perbarui informasi profil, peran, dan hak akses lokasi pengguna.' : 'Buat akun pengguna baru dan tentukan peran serta lokasi akses.' }}
          </p>
        </div>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-lg hover:bg-gray-100 cursor-pointer"
          @click="$emit('close')"
        >
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
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Error Alert -->
      <div
        v-if="error"
        class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-xs flex items-start gap-2"
      >
        <svg
          class="w-4 h-4 text-rose-600 shrink-0 mt-0.5"
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
        <span>{{ error }}</span>
      </div>

      <!-- Form Body -->
      <form
        class="space-y-4"
        @submit.prevent="handleSubmit"
      >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- 1. Nama Lengkap -->
          <div class="sm:col-span-2">
            <label
              for="form-name"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Nama Lengkap <span class="text-rose-500">*</span>
            </label>
            <input
              id="form-name"
              v-model="form.name"
              type="text"
              required
              placeholder="Contoh: Budi Santoso"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
            >
            <p
              v-if="formErrors.name"
              class="text-xs text-rose-600 mt-1"
            >
              {{ formErrors.name[0] }}
            </p>
          </div>

          <!-- 2. Username -->
          <div>
            <label
              for="form-username"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Username <span class="text-rose-500">*</span>
            </label>
            <input
              id="form-username"
              v-model="form.username"
              type="text"
              required
              placeholder="Contoh: budi.s"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
            >
            <p
              v-if="formErrors.username"
              class="text-xs text-rose-600 mt-1"
            >
              {{ formErrors.username[0] }}
            </p>
          </div>

          <!-- 3. Email -->
          <div>
            <label
              for="form-email"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Email <span class="text-rose-500">*</span>
            </label>
            <input
              id="form-email"
              v-model="form.email"
              type="email"
              required
              placeholder="Contoh: budi@company.com"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
            >
            <p
              v-if="formErrors.email"
              class="text-xs text-rose-600 mt-1"
            >
              {{ formErrors.email[0] }}
            </p>
          </div>

          <!-- 4. Password -->
          <div class="sm:col-span-2">
            <label
              for="form-password"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Password <span
                v-if="!isEditing"
                class="text-rose-500"
              >*</span>
              <span
                v-else
                class="text-xs text-gray-400 font-normal ml-1"
              >(Kosongkan jika tidak ingin mengubah password)</span>
            </label>
            <input
              id="form-password"
              v-model="form.password"
              type="password"
              :required="!isEditing"
              minlength="8"
              placeholder="Minimal 8 karakter"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
            >
            <p
              v-if="formErrors.password"
              class="text-xs text-rose-600 mt-1"
            >
              {{ formErrors.password[0] }}
            </p>
          </div>
        </div>

        <!-- 5. Penugasan Peran (Roles) -->
        <div class="pt-2">
          <label class="block text-xs font-semibold text-gray-700 mb-2">
            Peran Pengguna (Role) <span class="text-rose-500">*</span>
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            <label
              v-for="role in roles"
              :key="role.id"
              :class="[
                'flex items-start p-3 rounded-lg border cursor-pointer transition-all',
                form.role_ids.includes(role.id)
                  ? 'border-indigo-600 bg-indigo-50/40 ring-1 ring-indigo-600'
                  : 'border-gray-200 bg-white hover:bg-gray-50'
              ]"
            >
              <input
                v-model="form.role_ids"
                type="checkbox"
                :value="role.id"
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-0.5"
              >
              <div class="ml-2.5 text-left">
                <div class="text-xs font-bold text-gray-900">
                  {{ role.name }}
                </div>
                <div class="text-[11px] text-gray-500 mt-0.5 leading-tight">
                  {{ role.description || role.code }}
                </div>
              </div>
            </label>
          </div>
          <p
            v-if="formErrors.role_ids"
            class="text-xs text-rose-600 mt-1"
          >
            {{ formErrors.role_ids[0] }}
          </p>
        </div>

        <!-- 6. Penugasan Lokasi Gudang -->
        <div class="pt-2">
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-semibold text-gray-700">
              Akses Lokasi Gudang
            </label>
            <span class="text-[11px] text-gray-400">
              Kosongkan jika diizinkan akses seluruh lokasi (global)
            </span>
          </div>

          <div class="max-h-36 overflow-y-auto rounded-lg border border-gray-200 p-2.5 bg-gray-50/50 grid grid-cols-1 sm:grid-cols-2 gap-2">
            <label
              v-for="loc in locations"
              :key="loc.id"
              class="flex items-center gap-2 p-1.5 rounded hover:bg-white text-xs text-gray-800 cursor-pointer"
            >
              <input
                v-model="form.location_ids"
                type="checkbox"
                :value="loc.id"
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
              >
              <span class="font-mono font-semibold text-gray-700">{{ loc.code }}</span>
              <span class="text-gray-500 truncate">- {{ loc.name }}</span>
            </label>
          </div>
        </div>

        <!-- 7. Status Aktif Toggle -->
        <div class="pt-2 flex items-center justify-between border-t border-gray-100">
          <div>
            <div class="text-xs font-semibold text-gray-900">
              Status Akun Aktif
            </div>
            <div class="text-[11px] text-gray-500">
              Akun nonaktif tidak akan diizinkan masuk / login ke dalam sistem.
            </div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              v-model="form.is_active"
              type="checkbox"
              class="sr-only peer"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600" />
          </label>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
          <button
            type="button"
            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors cursor-pointer"
            @click="$emit('close')"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-600 cursor-pointer"
          >
            <svg
              v-if="saving"
              class="animate-spin -ml-0.5 mr-1 h-4 w-4 text-white"
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
            <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Pengguna' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  user: {
    type: Object,
    default: null,
  },
  roles: {
    type: Array,
    default: () => [],
  },
  locations: {
    type: Array,
    default: () => [],
  },
  saving: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  },
  formErrors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['close', 'save']);

const isEditing = computed(() => !!props.user?.id);

const form = reactive({
  name: '',
  username: '',
  email: '',
  password: '',
  role_ids: [],
  location_ids: [],
  is_active: true,
});

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      if (props.user) {
        form.name = props.user.name || '';
        form.username = props.user.username || '';
        form.email = props.user.email || '';
        form.password = '';
        form.role_ids = Array.isArray(props.user.role_ids) ? [...props.user.role_ids] : [];
        form.location_ids = Array.isArray(props.user.location_ids) ? [...props.user.location_ids] : [];
        form.is_active = props.user.is_active ?? true;
      } else {
        form.name = '';
        form.username = '';
        form.email = '';
        form.password = '';
        form.role_ids = props.roles.length > 0 ? [props.roles[0].id] : [];
        form.location_ids = [];
        form.is_active = true;
      }
    }
  },
  { immediate: true }
);

const handleSubmit = () => {
  const payload = {
    name: form.name,
    username: form.username,
    email: form.email,
    role_ids: form.role_ids,
    location_ids: form.location_ids,
    is_active: form.is_active,
  };

  if (form.password) {
    payload.password = form.password;
  }

  emit('save', payload);
};
</script>
