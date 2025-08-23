// @ts-nocheck
import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/', component: { template: '<div>Home</div>' } },
];

export default createRouter({
  history: createWebHistory(),
  routes,
});
