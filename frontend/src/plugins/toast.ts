import ToastService from 'primevue/toastservice';
import { useToast as usePrimeToast } from 'primevue/usetoast';

export const toastPlugin = ToastService;
export const useToast = usePrimeToast;

/**
 * Basic wrapper around PrimeVue's toast service so modules outside of Vue
 * components can trigger notifications. The exported `toast` object exposes a
 * single `show` method that displays an error toast. If the toast service isn't
 * available (for example during tests), the call is silently ignored.
 */
const toast = {
  show(message: string) {
    try {
      const t = usePrimeToast();
      t.add({ severity: 'error', summary: message, detail: '' });
    } catch {
      // When called outside of a Vue component before the plugin is
      // initialised, `usePrimeToast` will throw. In that case we simply ignore
      // the request to avoid crashing.
    }
  },
};

export default toast;
