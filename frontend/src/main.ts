// @ts-nocheck
import { createApp } from 'vue';
import router from './router';
import stores from './stores';
import AppShell from './components/layout/AppShell.vue';
import { toastPlugin } from './plugins/toast';
import i18n from './i18n';
import './styles/tokens.css';

createApp(AppShell)
  .use(stores)
  .use(i18n)
  .use(router)
  .use(toastPlugin)
  .mount('#app');
