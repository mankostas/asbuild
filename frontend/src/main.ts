import { createApp } from 'vue';
import router from './router';
import stores from './stores';
import AppShell from './components/layout/AppShell.vue';
import { toastPlugin } from './plugins/toast';
import i18n from './i18n';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import 'primeicons/primeicons.css';
import './styles/tokens.css';

createApp(AppShell)
  .use(stores)
  .use(i18n)
  .use(router)
  .use(toastPlugin)
  .use(PrimeVue, { theme: { preset: Aura } })
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

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js');
  });
}
