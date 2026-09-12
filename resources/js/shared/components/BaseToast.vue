<template>
  <Teleport to="body">
    <div
      :aria-live="'polite'"
      class="pointer-events-none fixed right-4 top-4 z-[100] flex w-full max-w-sm flex-col gap-2"
    >
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-x-4"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition duration-200 ease-in"
        leave-to-class="opacity-0 translate-x-4"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          role="status"
          class="pointer-events-auto flex items-start gap-3 rounded-lg border bg-white p-4 shadow-lg"
          :class="wrapperClasses[toast.type] || wrapperClasses.info"
        >
          <svg
            class="mt-0.5 h-5 w-5 shrink-0"
            :class="iconClasses[toast.type] || iconClasses.info"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              v-if="toast.type === 'success'"
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
            <path
              v-else-if="toast.type === 'error'"
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
            />
            <path
              v-else-if="toast.type === 'warning'"
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"
            />
          </svg>
          <div class="flex-1 text-sm">
            <p
              v-if="toast.title"
              class="font-medium text-gray-900"
            >
              {{ toast.title }}
            </p>
            <p :class="{ 'mt-0.5': toast.title }">
              {{ toast.message }}
            </p>
          </div>
          <button
            type="button"
            :aria-label="`Tutup notifikasi: ${toast.title || toast.message}`"
            class="-m-1 rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
            @click="dismissToast(toast.id)"
          >
            ✕
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '@shared/utils/use_toast.js';

const { toasts, dismissToast } = useToast();

const wrapperClasses = {
    success: 'border-emerald-200',
    error: 'border-rose-200',
    warning: 'border-amber-200',
    info: 'border-sky-200',
};

const iconClasses = {
    success: 'text-emerald-600',
    error: 'text-rose-600',
    warning: 'text-amber-600',
    info: 'text-sky-600',
};
</script>