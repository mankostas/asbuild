import { createApp } from 'vue';
import router from './router';
import stores from './stores';
import App from './App.vue';
import i18n from './i18n';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import ConfirmDialog from 'primevue/confirmdialog';
import VueGoodTablePlugin from 'vue-good-table-next';
import 'vue-good-table-next/dist/vue-good-table-next.css';
import 'primeicons/primeicons.css';
import './styles/tokens.css';
import './assets/main.css';

createApp(App)
  .use(stores)
  .use(i18n)
  .use(router)
  .use(PrimeVue, { theme: { preset: Aura } })
  .use(ToastService)
  .use(ConfirmationService)
  .use(VueGoodTablePlugin)
  .component('ConfirmDialog', ConfirmDialog)
  .mount('#app');

if (import.meta.env.VITE_SENTRY_DSN) {
  const script = document.createElement('script');
  script.src = 'https://browser.sentry-cdn.com/7.120.0/bundle.min.js';
  script.crossOrigin = 'anonymous';
  script.onload = () => {
    // @ts-ignore
    Sentry.init({ dsn: import.meta.env.VITE_SENTRY_DSN });
  };
  document.head.appendChild(script);
}

if ('serviceWorker' in navigator && import.meta.env.PROD) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js');
  });
}
