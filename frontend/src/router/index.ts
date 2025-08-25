import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { setTokens } from '@/services/authStorage';

export const routes = [
  { path: '/', redirect: '/appointments', meta: { requiresAuth: true } },
  {
    path: '/appointments',
    component: () => import('@/views/AppointmentList.vue'),
    meta: { requiresAuth: true, breadcrumb: 'routes.appointments' },
  },
  {
    path: '/appointments/:id',
    component: () => import('@/views/AppointmentDetail.vue'),
    meta: { requiresAuth: true, breadcrumb: 'routes.appointmentDetail' },
  },
  {
    path: '/manuals',
    component: () => import('@/views/ManualList.vue'),
    meta: { requiresAuth: true, breadcrumb: 'routes.manuals' },
  },
  {
    path: '/manuals/:id',
    component: () => import('@/views/ManualDetail.vue'),
    meta: { requiresAuth: true, breadcrumb: 'routes.manualDetail' },
  },
  {
    path: '/notifications',
    component: () => import('@/views/NotificationCenter.vue'),
    meta: { requiresAuth: true, breadcrumb: 'routes.notifications' },
  },
  {
    path: '/settings',
    component: () => import('@/views/SettingsView.vue'),
    meta: { requiresAuth: true, breadcrumb: 'routes.settings' },
  },
  {
    path: '/settings/gdpr',
    component: () => import('@/views/Settings/GdprView.vue'),
    meta: { requiresAuth: true, breadcrumb: 'routes.gdpr' },
  },
  {
    path: '/reports',
    component: () => import('@/views/ReportsDashboard.vue'),
    meta: { requiresAuth: true, breadcrumb: 'routes.reports' },
  },
  {
    path: '/employees',
    component: () => import('@/views/EmployeeList.vue'),
    meta: { requiresAuth: true, admin: true, breadcrumb: 'routes.employees' },
  },
  {
    path: '/tenants',
    component: () => import('@/views/TenantList.vue'),
    meta: { requiresAuth: true, breadcrumb: 'routes.tenants' },
  },
  { path: '/login', component: () => import('@/views/Auth/LoginView.vue') },
  {
    path: '/:pathMatch(.*)*',
    component: () => import('@/views/ErrorView.vue'),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();

  if (!auth.isAuthenticated) {
    const access = to.query.access_token || to.query.token;
    const refresh = to.query.refresh_token || to.query.refresh;
    if (access && refresh) {
      auth.accessToken = access;
      auth.refreshToken = refresh;
      setTokens(access, refresh);
      api.defaults.headers.common['Authorization'] = `Bearer ${access}`;
      const query = { ...to.query };
      delete query.access_token;
      delete query.refresh_token;
      delete query.token;
      delete query.refresh;
      return next({
        path: to.path,
        params: to.params,
        query,
        hash: to.hash,
        replace: true,
      });
    }
  }

  if (!auth.isAuthenticated && auth.refreshToken) {
    try {
      await auth.refresh();
    } catch (e) {}
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next('/login');
  }

  if (
    to.meta.admin &&
    !auth.user?.roles?.some((r: any) => r.name === 'ClientAdmin')
  ) {
    return next('/');
  }

  if (to.path === '/login' && auth.isAuthenticated) {
    return next('/');
  }

  next();
});

export default router;
