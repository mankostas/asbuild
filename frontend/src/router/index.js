import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { setTokens } from '@/services/authStorage';

const APP_NAME = import.meta.env.VITE_APP_NAME || 'AsBuild';

export const routes = [
  {
    path: '/',
    alias: '/dashboard',
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
      requiredAbilities: ['appointments.view', 'appointments.manage'],
      breadcrumb: 'routes.appointments',
      title: 'Appointments',
      layout: 'app',
    },
  },
  {
    path: '/appointments/calendar',
    name: 'appointments.calendar',
    component: () => import('@/views/appointments/CalendarView.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['appointments.view', 'appointments.manage'],
      breadcrumb: 'routes.appointments',
      title: 'Appointments Calendar',
      layout: 'app',
      groupParent: 'appointments.list',
    },
  },
  {
    path: '/appointments/:id',
    name: 'appointments.details',
    component: () => import('@/views/appointments/AppointmentDetails.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['appointments.view', 'appointments.manage'],
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
      requiredAbilities: ['appointments.create', 'appointments.manage'],
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
      requiredAbilities: ['appointments.update', 'appointments.manage'],
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
      requiredAbilities: ['types.view', 'types.manage'],
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
      requiredAbilities: ['types.create', 'types.manage'],
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
      requiredAbilities: ['types.update', 'types.manage'],
      breadcrumb: 'routes.typeEdit',
      title: 'Edit Type',
      layout: 'app',
      groupParent: 'types.list',
    },
  },
  {
    path: '/statuses',
    name: 'statuses.list',
    component: () => import('@/views/statuses/StatusesList.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['statuses.view', 'statuses.manage'],
      breadcrumb: 'routes.statuses',
      title: 'Statuses',
      layout: 'app',
    },
  },
  {
    path: '/statuses/create',
    name: 'statuses.create',
    component: () => import('@/views/statuses/StatusForm.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['statuses.create', 'statuses.manage'],
      breadcrumb: 'routes.statusCreate',
      title: 'Create Status',
      layout: 'app',
      groupParent: 'statuses.list',
    },
  },
  {
    path: '/statuses/:id/edit',
    name: 'statuses.edit',
    component: () => import('@/views/statuses/StatusForm.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['statuses.update', 'statuses.manage'],
      breadcrumb: 'routes.statusEdit',
      title: 'Edit Status',
      layout: 'app',
      groupParent: 'statuses.list',
    },
  },
  {
    path: '/roles',
    name: 'roles.list',
    component: () => import('@/views/roles/RolesList.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['roles.view', 'roles.manage'],
      breadcrumb: 'routes.roles',
      title: 'Roles',
      layout: 'app',
    },
  },
  {
    path: '/roles/create',
    name: 'roles.create',
    component: () => import('@/views/roles/RoleForm.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['roles.create', 'roles.manage'],
      breadcrumb: 'routes.roleCreate',
      title: 'Create Role',
      layout: 'app',
      groupParent: 'roles.list',
    },
  },
  {
    path: '/roles/:id/edit',
    name: 'roles.edit',
    component: () => import('@/views/roles/RoleForm.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['roles.update', 'roles.manage'],
      breadcrumb: 'routes.roleEdit',
      title: 'Edit Role',
      layout: 'app',
      groupParent: 'roles.list',
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
  {
    path: '/settings/footer',
    name: 'settings.footer',
    component: () => import('@/views/settings/Footer.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      super: true,
      breadcrumb: 'routes.footer',
      title: 'Footer',
      layout: 'app',
      groupParent: 'settings',
    },
  },
  {
    path: '/settings',
    name: 'settings',
    redirect: '/settings/profile',
    meta: {
      requiresAuth: true,
      breadcrumb: 'routes.settings',
      title: 'Settings',
      layout: 'app',
    },
  },
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
      groupParent: 'settings',
    },
  },
  {
    path: '/reports',
    name: 'reports',
    redirect: '/reports/kpis',
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.reports',
      title: 'Reports',
      layout: 'app',
    },
  },
  {
    path: '/reports/kpis',
    name: 'reports.kpis',
    component: () => import('@/views/reports/Reports.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      breadcrumb: 'routes.reports',
      title: 'Reports - KPIs',
      layout: 'app',
      groupParent: 'reports',
    },
  },
  {
    path: '/employees',
    name: 'employees.list',
    component: () => import('@/views/employees/EmployeesList.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['employees.view', 'employees.manage'],
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
      requiredAbilities: ['employees.create', 'employees.manage'],
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
      requiredAbilities: ['employees.update', 'employees.manage'],
      breadcrumb: 'routes.employeeEdit',
      title: 'Edit Employee',
      layout: 'app',
      groupParent: 'employees.list',
    },
  },
  {
    path: '/teams',
    name: 'teams.list',
    component: () => import('@/views/teams/TeamsList.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['teams.view', 'teams.manage'],
      breadcrumb: 'routes.teams',
      title: 'Teams',
      layout: 'app',
    },
  },
  {
    path: '/teams/create',
    name: 'teams.create',
    component: () => import('@/views/teams/TeamForm.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['teams.create', 'teams.manage'],
      breadcrumb: 'routes.teamCreate',
      title: 'Create Team',
      layout: 'app',
      groupParent: 'teams.list',
    },
  },
  {
    path: '/teams/:id/edit',
    name: 'teams.edit',
    component: () => import('@/views/teams/TeamForm.vue'),
    meta: {
      requiresAuth: true,
      requiredAbilities: ['teams.update', 'teams.manage'],
      breadcrumb: 'routes.teamEdit',
      title: 'Edit Team',
      layout: 'app',
      groupParent: 'teams.list',
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
    path: '/tenants/create',
    name: 'tenants.create',
    component: () => import('@/views/tenants/TenantForm.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      super: true,
      breadcrumb: 'routes.tenantCreate',
      title: 'Create Tenant',
      layout: 'app',
      groupParent: 'tenants.list',
    },
  },
  {
    path: '/tenants/:id/edit',
    name: 'tenants.edit',
    component: () => import('@/views/tenants/TenantForm.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      super: true,
      breadcrumb: 'routes.tenantEdit',
      title: 'Edit Tenant',
      layout: 'app',
      groupParent: 'tenants.list',
    },
  },
  {
    path: '/tenants/:id',
    name: 'tenants.view',
    component: () => import('@/views/tenants/TenantDetails.vue'),
    meta: {
      requiresAuth: true,
      admin: true,
      super: true,
      breadcrumb: 'routes.tenantDetail',
      title: 'Tenant Detail',
      layout: 'app',
      groupParent: 'tenants.list',
    },
  },
  {
    path: '/auth/login',
    name: 'login',
    component: () => import('@/views/auth/Login.vue'),
    meta: { layout: 'default', title: 'Sign in', hide: true },
  },
  {
    path: '/auth/register',
    name: 'register',
    component: () => import('@/views/auth/dashcode/RegisterIndex.vue'),
    meta: { layout: 'default', title: 'Register', hide: true },
  },
  {
    path: '/auth/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/auth/dashcode/ForgotPassword.vue'),
    meta: { layout: 'default', title: 'Forgot Password', hide: true },
  },
  {
    path: '/auth/lock',
    name: 'lock',
    component: () => import('@/views/auth/dashcode/LockScreen.vue'),
    meta: { layout: 'default', title: 'Lock Screen', hide: true },
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
    } catch (e) {
      // If refreshing the token fails (e.g. expired refresh token),
      // ensure any stale authentication state is cleared so the
      // navigation guards can redirect the user to the login page
      // without getting stuck in a refresh loop.
      await auth.logout(true);
    }
  }

  if (auth.isAuthenticated && !auth.user) {
    try {
      await auth.fetchUser();
    } catch (e) {
      // If fetching the user fails (e.g. expired/invalid token), ensure the
      // client state is cleared and redirect to the login page for any
      // guarded route. This prevents the app from remaining on a protected
      // page when the session is no longer valid.
      await auth.logout(true);
      if (to.meta.requiresAuth) {
        return next('/auth/login');
      }
    }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next('/auth/login');
  }

  if (to.meta.admin && !auth.isSuperAdmin) {
    return next('/');
  }

  if (to.meta.requiredAbilities?.length && !auth.hasAny(to.meta.requiredAbilities)) {
    return next('/');
  }

  if (to.path === '/auth/login' && auth.isAuthenticated) {
    return next('/');
  }

  next();
});

router.afterEach(() => {
  const appLoading = document.getElementById('loading-bg');
  if (appLoading) {
    appLoading.style.display = 'none';
  }
});

export default router;
