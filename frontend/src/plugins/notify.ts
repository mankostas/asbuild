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
    unauthorized(
      message = 'Παρακαλώ συνδεθείτε για να συνεχίσετε.',
      options?: ToastOptions,
    ) {
      toast.error(message, options);
    },
    forbidden(
      message = 'Δεν έχετε δικαιώματα για αυτή την ενέργεια.',
      options?: ToastOptions,
    ) {
      toast.error(message, options);
    },
    serverError(
      message = 'Προέκυψε σφάλμα διακομιστή.',
      options?: ToastOptions,
    ) {
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
  unauthorized(
    message = 'Παρακαλώ συνδεθείτε για να συνεχίσετε.',
    options?: ToastOptions,
  ) {
    try {
      useToast().error(message, options);
    } catch {}
  },
  forbidden(
    message = 'Δεν έχετε δικαιώματα για αυτή την ενέργεια.',
    options?: ToastOptions,
  ) {
    try {
      useToast().error(message, options);
    } catch {}
  },
  serverError(
    message = 'Προέκυψε σφάλμα διακομιστή.',
    options?: ToastOptions,
  ) {
    try {
      useToast().error(message, options);
    } catch {}
  },
};

export default notify;
