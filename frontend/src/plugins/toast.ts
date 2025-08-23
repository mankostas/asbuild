import { App, inject, reactive } from 'vue';

export interface Toast {
  id: number;
  message: string;
}

const ToastSymbol = Symbol('toast');

function createToast() {
  const toasts = reactive<Toast[]>([]);

  function show(message: string, timeout = 3000) {
    const id = Date.now() + Math.random();
    toasts.push({ id, message });
    if (timeout) {
      setTimeout(() => dismiss(id), timeout);
    }
  }

  function dismiss(id: number) {
    const idx = toasts.findIndex((t) => t.id === id);
    if (idx > -1) toasts.splice(idx, 1);
  }

  return { toasts, show, dismiss };
}

export const toastPlugin = {
  install(app: App) {
    const toast = createToast();
    app.provide(ToastSymbol, toast);
    app.config.globalProperties.$toast = toast;
  },
};

export function useToast() {
  const toast = inject<{ toasts: Toast[]; show: Function; dismiss: Function }>(ToastSymbol);
  if (!toast) throw new Error('Toast plugin not installed');
  return toast;
}
