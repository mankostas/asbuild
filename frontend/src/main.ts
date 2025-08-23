// @ts-nocheck
import { createApp } from 'vue';
import router from './router';
import stores from './stores';
import AppShell from './components/layout/AppShell.vue';
import { toastPlugin } from './plugins/toast';
import './styles/tokens.css';

createApp(AppShell)
  .use(stores)
  .use(router)
  .use(toastPlugin)
  .mount('#app');
