import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { setTokens } from '@/services/authStorage';

const APP_NAME = import.meta.env.VITE_APP_NAME || 'AsBuild';

export const routes = [
  {
    path: '/',
    redirect: '/appointments',
    meta: { requiresAuth: true, layout: 'app', hide: true },
  },
  {
    path: '/appointments',
    component: () => import('@/views/AppointmentList.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.appointments',
      title: 'Appointments',
      layout: 'app',
      groupParent: 'appointments',
    },
  },
  {
    path: '/appointments/:id',
    component: () => import('@/views/AppointmentDetail.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.appointmentDetail',
      title: 'Appointment Detail',
      layout: 'app',
      groupParent: '/appointments',
    },
  },
  {
    path: '/manuals',
    component: () => import('@/views/ManualList.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.manuals',
      title: 'Manuals',
      layout: 'app',
      groupParent: 'manuals',
    },
  },
  {
    path: '/manuals/:id',
    component: () => import('@/views/ManualDetail.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.manualDetail',
      title: 'Manual Detail',
      layout: 'app',
      groupParent: '/manuals',
    },
  },
  {
    path: '/notifications',
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
    component: () => import('@/views/SettingsView.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.settings',
      title: 'Settings',
      layout: 'app',
      groupParent: 'settings',
    },
  },
  {
    path: '/settings/gdpr',
    component: () => import('@/views/Settings/GdprView.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.gdpr',
      title: 'GDPR',
      layout: 'app',
      groupParent: '/settings',
    },
  },
  {
    path: '/reports',
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
    component: () => import('@/views/auth/Login.vue'),
    meta: { layout: 'default', title: 'Sign in', hide: true },
  },
  {
    path: '/:pathMatch(.*)*',
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
