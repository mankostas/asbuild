// @ts-nocheck
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
  { path: '/', redirect: '/appointments', meta: { requiresAuth: true } },
  {
    path: '/appointments',
    component: () => import('@/views/AppointmentList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/appointments/:id',
    component: () => import('@/views/AppointmentDetail.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/manuals',
    component: () => import('@/views/ManualList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/manuals/:id',
    component: () => import('@/views/ManualDetail.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/notifications',
    component: () => import('@/views/NotificationCenter.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings',
    component: () => import('@/views/SettingsView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings/gdpr',
    component: () => import('@/views/Settings/GdprView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/reports',
    component: () => import('@/views/ReportsDashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/employees',
    component: () => import('@/views/EmployeeList.vue'),
    meta: { requiresAuth: true, admin: true },
  },
  {
    path: '/tenants',
    component: () => import('@/views/TenantList.vue'),
    meta: { requiresAuth: true },
  },
  { path: '/login', component: () => import('@/views/Auth/LoginView.vue') },
  { path: '/:pathMatch(.*)*', component: () => import('@/views/ErrorView.vue') },
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

  if (to.meta.admin && !auth.user?.roles?.some((r: any) => r.name === 'ClientAdmin')) {
    return next('/');
  }

  if (to.path === '/login' && auth.isAuthenticated) {
    return next('/');
  }

  next();
});

export default router;
