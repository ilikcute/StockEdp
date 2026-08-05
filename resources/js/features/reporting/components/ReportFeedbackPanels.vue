<template>
  <div>
    <!-- 1. Forbidden 403 -->
    <div
      v-if="feedbackState === 'forbidden'"
      class="mt-4 rounded-md bg-red-50 p-4 border border-red-200"
    >
      <h3 class="text-sm font-medium text-red-800">
        Akses Ditolak
      </h3>
      <p class="mt-2 text-sm text-red-700">
        Anda tidak memiliki izin untuk melihat laporan ini.
      </p>
    </div>

    <!-- 2. Validation Errors (422, Local, or Backend Validation Errors) -->
    <div
      v-else-if="feedbackState === 'validation'"
      class="mt-4 rounded-md bg-yellow-50 p-4 border border-yellow-200 space-y-2"
    >
      <p
        v-if="localValidationError"
        class="text-sm text-yellow-700 font-medium"
      >
        {{ localValidationError }}
      </p>

      <ul
        v-if="validationErrors && Object.keys(validationErrors).length > 0"
        class="list-disc pl-5 text-sm text-yellow-700"
      >
        <li
          v-for="(errors, field) in validationErrors"
          :key="field"
        >
          <span class="font-medium">{{ field }}:</span> {{ Array.isArray(errors) ? errors.join(', ') : errors }}
        </li>
      </ul>
    </div>

    <!-- 3. Error / Network / Server (500, Network Failure) -->
    <div
      v-else-if="feedbackState === 'error'"
      class="mt-4 rounded-md bg-red-50 p-4 border border-red-200"
    >
      <h3 class="text-sm font-medium text-red-800">
        Gagal Memuat Data
      </h3>
      <p class="mt-2 text-sm text-red-700">
        {{ error }}
      </p>
      <div class="mt-4 flex gap-2">
        <button
          type="button"
          class="text-sm font-medium text-red-800 hover:text-red-900 bg-red-100 px-3 py-1.5 rounded-md"
          @click="emit('retry')"
        >
          Coba Lagi
        </button>
        <button
          type="button"
          class="text-sm font-medium text-gray-700 hover:text-gray-900 bg-gray-100 px-3 py-1.5 rounded-md"
          @click="emit('reset-filters')"
        >
          Reset Filter
        </button>
      </div>
    </div>

    <!-- 4. Loading State -->
    <div
      v-else-if="feedbackState === 'loading'"
      class="mt-4 py-8 text-center"
    >
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-500 border-t-transparent" />
      <p class="mt-2 text-sm text-gray-500">
        Memuat data laporan...
      </p>
    </div>

    <!-- 5. Empty Result State -->
    <div
      v-else-if="feedbackState === 'empty'"
      class="mt-4 rounded-md bg-gray-50 p-8 text-center border border-gray-200"
    >
      <p class="text-sm text-gray-500">
        {{ emptyMessage }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
    status: { type: Number, default: null },
    validationErrors: { type: Object, default: () => ({}) },
    localValidationError: { type: String, default: '' },
    hasData: { type: Boolean, default: false },
    hasFetched: { type: Boolean, default: false },
    emptyMessage: { type: String, default: 'Tidak ada data yang sesuai filter.' },
});

const emit = defineEmits(['retry', 'reset-filters']);

const feedbackState = computed(() => {
    const hasValidationErrors = Object.keys(props.validationErrors || {}).length > 0;

    if (props.status === 403) {
        return 'forbidden';
    }

    if (
        props.status === 422
        || props.localValidationError
        || hasValidationErrors
    ) {
        return 'validation';
    }

    if (props.error) {
        return 'error';
    }

    if (props.loading) {
        return 'loading';
    }

    if (props.hasFetched && !props.hasData) {
        return 'empty';
    }

    return null;
});
</script>
