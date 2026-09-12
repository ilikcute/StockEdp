import { reactive } from 'vue';

const state = reactive({
    toasts: [],
});

let nextId = 1;

/**
 * Tampilkan notifikasi toast singkat di pojok kanan atas.
 *
 * @param {string} message Pesan yang akan ditampilkan.
 * @param {{ type?: 'success'|'error'|'warning'|'info', title?: string, duration?: number }} [options]
 *   duration diatur 0 agar tidak hilang otomatis.
 */
export function showToast(message, { type = 'info', title = '', duration = 4000 } = {}) {
    const id = nextId;
    nextId += 1;

    state.toasts.push({ id, message, type, title, duration });

    if (duration > 0) {
        setTimeout(() => dismissToast(id), duration);
    }

    return id;
}

export function dismissToast(id) {
    const index = state.toasts.findIndex((toast) => toast.id === id);
    if (index !== -1) {
        state.toasts.splice(index, 1);
    }
}

export function useToast() {
    return {
        toasts: state.toasts,
        showToast,
        dismissToast,
    };
}