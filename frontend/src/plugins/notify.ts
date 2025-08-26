import Toast, { useToast, type ToastOptions } from 'vue-toastification';

export const notifyPlugin = Toast;

export function useNotify() {
  const toast = useToast();
  return {
    success(message: string, options?: ToastOptions) {
      toast.success(message, options);
    },
    info(message: string, options?: ToastOptions) {
      toast.info(message, options);
    },
    error(message: string, options?: ToastOptions) {
      toast.error(message, options);
    },
  };
}

const notify = {
  success(message: string, options?: ToastOptions) {
    try {
      useToast().success(message, options);
    } catch {}
  },
  info(message: string, options?: ToastOptions) {
    try {
      useToast().info(message, options);
    } catch {}
  },
  error(message: string, options?: ToastOptions) {
    try {
      useToast().error(message, options);
    } catch {}
  },
};

export default notify;
