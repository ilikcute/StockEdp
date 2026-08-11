<template>
  <div
    v-if="totalCount > 0"
    class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4"
  >
    <p class="text-sm text-gray-700">
      Menampilkan
      <span class="font-medium">{{ fromItem }}</span>
      sampai
      <span class="font-medium">{{ toItem }}</span>
      dari
      <span class="font-medium">{{ totalCount }}</span>
      data
    </p>

    <div
      v-if="totalPages > 1"
      class="flex flex-wrap items-center gap-1"
    >
      <!-- First Page -->
      <button
        type="button"
        class="inline-flex items-center justify-center px-2.5 py-1 text-sm rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        :disabled="activePage <= 1 || loading"
        title="Halaman Pertama"
        @click="goToPage(1)"
      >
        &laquo;
      </button>

      <!-- Previous Page -->
      <button
        type="button"
        class="inline-flex items-center justify-center px-2.5 py-1 text-sm rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        :disabled="activePage <= 1 || loading"
        title="Halaman Sebelumnya"
        @click="goToPage(activePage - 1)"
      >
        &lsaquo;
      </button>

      <!-- Page Numbers & Ellipsis -->
      <template
        v-for="item in visibleItems"
        :key="item.key"
      >
        <span
          v-if="item.type === 'ellipsis'"
          class="px-2 py-1 text-sm text-gray-500 select-none"
        >
          ...
        </span>
        <button
          v-else
          type="button"
          class="inline-flex items-center justify-center min-w-[2rem] px-2.5 py-1 text-sm rounded-md border transition-colors font-medium"
          :class="item.value === activePage
            ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
          :disabled="loading"
          @click="goToPage(item.value)"
        >
          {{ item.value }}
        </button>
      </template>

      <!-- Next Page -->
      <button
        type="button"
        class="inline-flex items-center justify-center px-2.5 py-1 text-sm rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        :disabled="activePage >= totalPages || loading"
        title="Halaman Berikutnya"
        @click="goToPage(activePage + 1)"
      >
        &rsaquo;
      </button>

      <!-- Last Page -->
      <button
        type="button"
        class="inline-flex items-center justify-center px-2.5 py-1 text-sm rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        :disabled="activePage >= totalPages || loading"
        title="Halaman Terakhir"
        @click="goToPage(totalPages)"
      >
        &raquo;
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
    currentPage: {
        type: Number,
        default: 1,
    },
    lastPage: {
        type: Number,
        default: 1,
    },
    perPage: {
        type: Number,
        default: 15,
    },
    total: {
        type: Number,
        default: 0,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['change', 'page-change']);

const activePage = computed(() => {
    return props.pagination?.current_page ?? props.currentPage;
});

const totalPages = computed(() => {
    return props.pagination?.last_page ?? props.lastPage;
});

const itemsPerPage = computed(() => {
    return props.pagination?.per_page ?? props.perPage;
});

const totalCount = computed(() => {
    return props.pagination?.total ?? props.total;
});

const fromItem = computed(() => {
    if (totalCount.value === 0) return 0;
    return ((activePage.value - 1) * itemsPerPage.value) + 1;
});

const toItem = computed(() => {
    if (totalCount.value === 0) return 0;
    return Math.min(activePage.value * itemsPerPage.value, totalCount.value);
});

const visibleItems = computed(() => {
    const total = totalPages.value;
    const current = activePage.value;

    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => ({
            type: 'page',
            value: i + 1,
            key: `page-${i + 1}`,
        }));
    }

    const items = [];

    // Always include first page
    items.push({ type: 'page', value: 1, key: 'page-1' });

    let start = Math.max(2, current - 1);
    let end = Math.min(total - 1, current + 1);

    if (current <= 3) {
        start = 2;
        end = 4;
    } else if (current >= total - 2) {
        start = total - 3;
        end = total - 1;
    }

    if (start > 2) {
        items.push({ type: 'ellipsis', key: 'ellipsis-left' });
    }

    for (let i = start; i <= end; i += 1) {
        items.push({ type: 'page', value: i, key: `page-${i}` });
    }

    if (end < total - 1) {
        items.push({ type: 'ellipsis', key: 'ellipsis-right' });
    }

    // Always include last page
    items.push({ type: 'page', value: total, key: `page-${total}` });

    return items;
});

function goToPage(page) {
    if (page >= 1 && page <= totalPages.value && page !== activePage.value) {
        emit('change', page);
        emit('page-change', page);
    }
}
</script>
