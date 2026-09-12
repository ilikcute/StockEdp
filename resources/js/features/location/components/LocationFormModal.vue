<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="onClose"
    >
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="text-lg font-semibold text-gray-900">
            {{ isEditing ? 'Edit Lokasi' : 'Tambah Lokasi' }}
          </h2>
        </div>

        <form
          class="p-6 space-y-4"
          @submit.prevent="handleSubmit"
        >
          <div>
            <label
              for="code"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Kode Lokasi <span class="text-red-500">*</span></label>
            <input
              id="code"
              v-model="form.code"
              type="text"
              class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 uppercase"
              :class="store.validationErrors?.code ? 'border-red-500' : 'border-gray-300'"
              placeholder="Contoh: G-UTM"
              required
            >
            <p
              v-if="store.validationErrors?.code"
              class="mt-1 text-xs text-red-600"
            >
              {{ store.validationErrors.code[0] }}
            </p>
          </div>

          <div>
            <label
              for="name"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Nama Lokasi <span class="text-red-500">*</span></label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              :class="store.validationErrors?.name ? 'border-red-500' : 'border-gray-300'"
              placeholder="Contoh: Gudang Utama"
              required
            >
            <p
              v-if="store.validationErrors?.name"
              class="mt-1 text-xs text-red-600"
            >
              {{ store.validationErrors.name[0] }}
            </p>
          </div>

          <div>
            <label
              for="type"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Tipe Lokasi <span class="text-red-500">*</span></label>
            <select
              id="type"
              v-model="form.type"
              class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              :class="store.validationErrors?.type ? 'border-red-500' : 'border-gray-300'"
              required
            >
              <option value="MAIN_WAREHOUSE">
                Gudang Induk (Penerimaan GA & Retur)
              </option>
              <option value="FIELD_PERSONNEL">
                Teknisi Lapangan (Unit Bagasi/Mobil Teknisi)
              </option>
              <option value="DAMAGED_STORAGE">
                Gudang Afkir (Barang Rusak)
              </option>
            </select>
            <p
              v-if="store.validationErrors?.type"
              class="mt-1 text-xs text-red-600"
            >
              {{ store.validationErrors.type[0] }}
            </p>
          </div>

          <div v-if="form.type === 'FIELD_PERSONNEL'">
            <label
              for="user_id"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Teknisi Penanggung Jawab <span class="text-red-500">*</span></label>
            <select
              id="user_id"
              v-model="form.user_id"
              class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              :class="store.validationErrors?.user_id ? 'border-red-500' : 'border-gray-300'"
              required
            >
              <option :value="null">
                -- Pilih Teknisi --
              </option>
              <option
                v-for="user in usersList"
                :key="user.id"
                :value="user.id"
              >
                {{ user.name }} ({{ user.email }})
              </option>
            </select>
            <p
              v-if="store.validationErrors?.user_id"
              class="mt-1 text-xs text-red-600"
            >
              {{ store.validationErrors.user_id[0] }}
            </p>
          </div>

          <div>
            <label
              for="phone"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Telepon</label>
            <input
              id="phone"
              v-model="form.phone"
              type="text"
              class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              :class="store.validationErrors?.phone ? 'border-red-500' : 'border-gray-300'"
            >
            <p
              v-if="store.validationErrors?.phone"
              class="mt-1 text-xs text-red-600"
            >
              {{ store.validationErrors.phone[0] }}
            </p>
          </div>

          <div>
            <label
              for="address"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Alamat</label>
            <textarea
              id="address"
              v-model="form.address"
              class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              :class="store.validationErrors?.address ? 'border-red-500' : 'border-gray-300'"
              rows="2"
            />
            <p
              v-if="store.validationErrors?.address"
              class="mt-1 text-xs text-red-600"
            >
              {{ store.validationErrors.address[0] }}
            </p>
          </div>

          <div>
            <label
              for="description"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Deskripsi</label>
            <textarea
              id="description"
              v-model="form.description"
              class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              :class="store.validationErrors?.description ? 'border-red-500' : 'border-gray-300'"
              rows="2"
            />
            <p
              v-if="store.validationErrors?.description"
              class="mt-1 text-xs text-red-600"
            >
              {{ store.validationErrors.description[0] }}
            </p>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
              :disabled="store.isLoading"
              @click="onClose"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition-colors shadow-sm"
              :disabled="store.isLoading"
            >
              {{ store.isLoading ? 'Menyimpan…' : (isEditing ? 'Perbarui' : 'Simpan') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useLocationStore } from '../stores/use_location_store';
import { userApi } from '@/features/user_management/api/user_api';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true
    },
    location: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'saved']);
const store = useLocationStore();
const usersList = ref([]);

const isEditing = computed(() => !!props.location);

const form = reactive({
    code: '',
    name: '',
    type: 'MAIN_WAREHOUSE',
    user_id: null,
    description: '',
    address: '',
    phone: '',
});

const loadUsers = async () => {
    try {
        const res = await userApi.getUsers({ per_page: 100, is_active: true });
        usersList.value = res.data?.data || [];
    } catch {
        usersList.value = [];
    }
};

watch(() => form.type, (newType) => {
    if (newType !== 'FIELD_PERSONNEL') {
        form.user_id = null;
    }
});

watch(() => props.isOpen, async (newVal) => {
    if (newVal) {
        store.clearMessages();
        loadUsers();
        if (props.location) {
            form.code = props.location.code;
            form.name = props.location.name;
            form.type = props.location.type || 'MAIN_WAREHOUSE';
            form.user_id = props.location.user_id || null;
            form.description = props.location.description || '';
            form.address = props.location.address || '';
            form.phone = props.location.phone || '';
        } else {
            form.code = '';
            form.name = '';
            form.type = 'MAIN_WAREHOUSE';
            form.user_id = null;
            form.description = '';
            form.address = '';
            form.phone = '';
        }
    }
});

const handleSubmit = async () => {
    const payload = { ...form };
    if (payload.type !== 'FIELD_PERSONNEL') {
        payload.user_id = null;
    }

    const success = isEditing.value 
        ? await store.updateLocation(props.location.id, payload)
        : await store.createLocation(payload);
        
    if (success) {
        emit('saved');
        onClose();
    }
};

const onClose = () => {
    if (!store.isLoading) {
        emit('close');
    }
};
</script>
