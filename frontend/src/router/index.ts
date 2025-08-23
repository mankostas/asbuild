// @ts-nocheck
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
  { path: '/', component: { template: '<div>Home</div>' }, meta: { requiresAuth: true } },
  { path: '/login', component: () => import('@/views/Auth/LoginView.vue') },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();

  if (!auth.isAuthenticated && auth.refreshToken) {
    try {
      await auth.refresh();
    } catch (e) {}
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next('/login');
  }

  if (to.path === '/login' && auth.isAuthenticated) {
    return next('/');
  }

  next();
});

export default router;
