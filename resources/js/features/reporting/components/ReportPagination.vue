<template>
  <div
    v-if="pagination && pagination.total > 0"
    class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4"
  >
    <p class="text-sm text-gray-700">
      Menampilkan
      <span class="font-medium">{{ fromItem }}</span>
      sampai
      <span class="font-medium">{{ toItem }}</span>
      dari
      <span class="font-medium">{{ pagination.total }}</span>
      item
    </p>
    <div class="flex flex-wrap gap-2 items-center">
      <button
        type="button"
        class="px-3 py-1 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50 shadow-xs disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
        :disabled="pagination.current_page <= 1 || loading"
        @click="emitPage(1)"
      >
        Pertama
      </button>
      <button
        type="button"
        class="px-3 py-1 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50 shadow-xs disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
        :disabled="pagination.current_page <= 1 || loading"
        @click="emitPage(pagination.current_page - 1)"
      >
        Sebelumnya
      </button>
      <template
        v-for="page in visiblePages"
        :key="page"
      >
        <button
          type="button"
          class="px-3 py-1 text-sm rounded-md border shadow-xs cursor-pointer"
          :class="page === pagination.current_page
            ? 'border-indigo-600 bg-indigo-50 text-indigo-700 font-medium'
            : 'border-gray-300 bg-white hover:bg-gray-50 text-gray-700'"
          :disabled="loading"
          @click="emitPage(page)"
        >
          {{ page }}
        </button>
      </template>
      <button
        type="button"
        class="px-3 py-1 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50 shadow-xs disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
        :disabled="pagination.current_page >= pagination.last_page || loading"
        @click="emitPage(pagination.current_page + 1)"
      >
        Berikutnya
      </button>
      <button
        type="button"
        class="px-3 py-1 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50 shadow-xs disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
        :disabled="pagination.current_page >= pagination.last_page || loading"
        @click="emitPage(pagination.last_page)"
      >
        Terakhir
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    pagination: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['page-change']);

const fromItem = computed(() => {
    if (!props.pagination || props.pagination.total === 0) return 0;
    return ((props.pagination.current_page - 1) * props.pagination.per_page) + 1;
});

const toItem = computed(() => {
    if (!props.pagination) return 0;
    const end = props.pagination.current_page * props.pagination.per_page;
    return end > props.pagination.total ? props.pagination.total : end;
});

const visiblePages = computed(() => {
    if (!props.pagination) return [];

    const current = props.pagination.current_page;
    const last = props.pagination.last_page;
    const windowSize = 5;
    let start = Math.max(1, current - Math.floor(windowSize / 2));
    let end = Math.min(last, start + windowSize - 1);

    if (end - start + 1 < windowSize) {
        start = Math.max(1, end - windowSize + 1);
    }

    const pages = [];
    for (let i = start; i <= end; i += 1) {
        pages.push(i);
    }

    return pages;
});

function emitPage(page) {
    if (page >= 1 && page <= props.pagination.last_page) {
        emit('page-change', page);
    }
}
</script>
