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
    component: () => import('@/views/appointments/AppointmentDetails.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.appointmentDetail',
      title: 'Appointment Detail',
      layout: 'app',
      groupParent: 'appointments.list',
    },
  },
  {
    path: '/appointments/create',
    name: 'appointments.create',
    component: () => import('@/views/appointments/AppointmentForm.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.appointmentCreate',
      title: 'Create Appointment',
      layout: 'app',
      groupParent: 'appointments.list',
    },
  },
  {
    path: '/appointments/:id/edit',
    name: 'appointments.edit',
    component: () => import('@/views/appointments/AppointmentForm.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.appointmentEdit',
      title: 'Edit Appointment',
      layout: 'app',
      groupParent: 'appointments.list',
    },
  },
  {
    path: '/types',
    name: 'types.list',
    component: () => import('@/views/types/TypesList.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.types',
      title: 'Appointment Types',
      layout: 'app',
    },
  },
  {
    path: '/types/create',
    name: 'types.create',
    component: () => import('@/views/types/TypeForm.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.typeCreate',
      title: 'Create Type',
      layout: 'app',
      groupParent: 'types.list',
    },
  },
  {
    path: '/types/:id/edit',
    name: 'types.edit',
    component: () => import('@/views/types/TypeForm.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.typeEdit',
      title: 'Edit Type',
      layout: 'app',
      groupParent: 'types.list',
    },
  },
  {
    path: '/manuals',
    name: 'manuals.list',
    component: () => import('@/views/manuals/ManualsList.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.manuals',
      title: 'Manuals',
      layout: 'app',
    },
  },
  {
    path: '/manuals/create',
    name: 'manuals.create',
    component: () => import('@/views/manuals/ManualForm.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.manualCreate',
      title: 'Upload Manual',
      layout: 'app',
      groupParent: 'manuals.list',
    },
  },
  {
    path: '/manuals/:id/edit',
    name: 'manuals.edit',
    component: () => import('@/views/manuals/ManualForm.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.manualEdit',
      title: 'Edit Manual',
      layout: 'app',
      groupParent: 'manuals.list',
    },
  },
  {
    path: '/notifications',
    name: 'notifications.inbox',
    component: () => import('@/views/notifications/NotificationsInbox.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.notifications',
      title: 'Notifications',
      layout: 'app',
    },
  },
  {
    path: '/notifications/preferences',
    name: 'notifications.prefs',
    component: () =>
      import('@/views/notifications/NotificationPreferences.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.notifications',
      title: 'Notification Preferences',
      layout: 'app',
      groupParent: 'notifications.inbox',
    },
  },
  {
    path: '/settings/profile',
    name: 'settings.profile',
    component: () => import('@/views/settings/Profile.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.profile',
      title: 'Profile',
      layout: 'app',
      groupParent: 'settings',
    },
  },
  {
    path: '/settings/branding',
    name: 'settings.branding',
    component: () => import('@/views/settings/Branding.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.branding',
      title: 'Branding',
      layout: 'app',
      groupParent: 'settings',
    },
  },
  { path: '/settings', redirect: '/settings/profile' },
  { path: '/settings/gdpr', redirect: '/gdpr' },
  {
    path: '/gdpr',
    name: 'gdpr.index',
    component: () => import('@/views/gdpr/Gdpr.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.gdpr',
      title: 'GDPR',
      layout: 'app',
    },
  },
  { path: '/reports', redirect: '/reports/kpis' },
  {
    path: '/reports/kpis',
    name: 'reports.kpis',
    component: () => import('@/views/reports/Reports.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.reports',
      title: 'Reports - KPIs',
      layout: 'app',
    },
  },
  {
    path: '/employees',
    name: 'employees.list',
    component: () => import('@/views/employees/EmployeesList.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.employees',
      title: 'Employees',
      layout: 'app',
    },
  },
  {
    path: '/employees/create',
    name: 'employees.create',
    component: () => import('@/views/employees/EmployeeForm.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.employeeCreate',
      title: 'Invite Employee',
      layout: 'app',
      groupParent: 'employees.list',
    },
  },
  {
    path: '/employees/:id/edit',
    name: 'employees.edit',
    component: () => import('@/views/employees/EmployeeForm.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.employeeEdit',
      title: 'Edit Employee',
      layout: 'app',
      groupParent: 'employees.list',
    },
  },
  {
    path: '/tenants',
    name: 'tenants.list',
    component: () => import('@/views/tenants/TenantsList.vue'),
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
