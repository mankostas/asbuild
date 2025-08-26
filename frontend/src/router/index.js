import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { setTokens } from '@/services/authStorage';

const APP_NAME = import.meta.env.VITE_APP_NAME || 'AsBuild';

export const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/views/home/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.dashboard',
      title: 'Dashboard',
      layout: 'app',
    },
  },
  {
    path: '/appointments',
    name: 'appointments.list',
    component: () => import('@/views/appointments/AppointmentsList.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.appointments',
      title: 'Appointments',
      layout: 'app',
    },
  },
  {
    path: '/appointments/:id',
    name: 'appointments.details',
    component: () => import('@/views/AppointmentDetail.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.appointmentDetail',
      title: 'Appointment Detail',
      layout: 'app',
      groupParent: 'appointments.list',
    },
  },
  {
    path: '/manuals',
    name: 'manuals',
    component: () => import('@/views/ManualList.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.manuals',
      title: 'Manuals',
      layout: 'app',
    },
  },
  {
    path: '/manuals/:id',
    name: 'manual-detail',
    component: () => import('@/views/ManualDetail.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.manualDetail',
      title: 'Manual Detail',
      layout: 'app',
      groupParent: 'manuals',
    },
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('@/views/NotificationCenter.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.notifications',
      title: 'Notifications',
      layout: 'app',
    },
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('@/views/SettingsView.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.settings',
      title: 'Settings',
      layout: 'app',
    },
  },
  {
    path: '/settings/gdpr',
    name: 'settings-gdpr',
    component: () => import('@/views/Settings/GdprView.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.gdpr',
      title: 'GDPR',
      layout: 'app',
      groupParent: 'settings',
    },
  },
  {
    path: '/reports',
    name: 'reports',
    component: () => import('@/views/ReportsDashboard.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.reports',
      title: 'Reports',
      layout: 'app',
    },
  },
  {
    path: '/employees',
    name: 'employees',
    component: () => import('@/views/EmployeeList.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.employees',
      title: 'Employees',
      layout: 'app',
    },
  },
  {
    path: '/tenants',
    name: 'tenants',
    component: () => import('@/views/TenantList.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      super: true,
      breadcrumb: 'routes.tenants',
      title: 'Tenants',
      layout: 'app',
    },
  },
  {
    path: '/auth/login',
    name: 'login',
    component: () => import('@/views/auth/Login.vue'),
    meta: { layout: 'default', title: 'Sign in', hide: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/_errors/NotFound.vue'),
    meta: { title: 'Not Found', layout: 'default', hide: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();

  document.title = to.meta?.title || APP_NAME;

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
      return next('/auth/login');
    }

  if (to.meta.admin) {
    const roles = auth.user?.roles?.map((r) => r.name) || [];
    if (to.meta.super) {
      if (!roles.includes('SuperAdmin')) {
        return next('/');
      }
    } else if (!roles.some((r) => ['ClientAdmin', 'SuperAdmin'].includes(r))) {
      return next('/');
    }
  }

  if (to.path === '/auth/login' && auth.isAuthenticated) {
    return next('/');
  }

  next();
});

export default router;
