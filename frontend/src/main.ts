// @ts-nocheck
import { createApp } from 'vue';
import router from './router';
import stores from './stores';

createApp({ template: '<router-view />' })
  .use(stores)
  .use(router)
  .mount('#app');
