export interface RouteAccess {
  requiredAbilities?: string[];
  requireAllAbilities?: boolean;
  requiredFeatures?: string[];
}

const routeAccessMap: Record<string, RouteAccess> = {
  dashboard: {
    requiredAbilities: ['reports.view'],
    requiredFeatures: ['reports'],
  },
  'tasks.list': {
    requiredAbilities: ['tasks.view'],
    requiredFeatures: ['tasks'],
  },
  'tasks.create': {
    requiredAbilities: ['tasks.create'],
    requiredFeatures: ['tasks'],
  },
  'tasks.edit': {
    requiredAbilities: ['tasks.view', 'tasks.update'],
    requireAllAbilities: true,
    requiredFeatures: ['tasks'],
  },
  'tasks.details': {
    requiredAbilities: ['tasks.view'],
    requiredFeatures: ['tasks'],
  },
  'tasks.board': {
    requiredAbilities: ['tasks.view'],
    requiredFeatures: ['tasks'],
  },
  'tasks.reports': {
    requiredAbilities: ['reports.view'],
    requiredFeatures: ['reports'],
  },
  'taskTypes.list': {
    requiredAbilities: ['task_types.view'],
    requiredFeatures: ['task_types'],
  },
  'taskTypes.create': {
    requiredAbilities: ['task_types.create'],
    requiredFeatures: ['task_types'],
  },
  'taskTypes.edit': {
    requiredAbilities: ['task_types.update'],
    requiredFeatures: ['task_types'],
  },
  'taskStatuses.list': {
    requiredAbilities: ['task_statuses.view'],
    requiredFeatures: ['task_statuses'],
  },
  'taskStatuses.create': {
    requiredAbilities: ['task_statuses.manage'],
    requiredFeatures: ['task_statuses'],
  },
  'taskStatuses.edit': {
    requiredAbilities: ['task_statuses.manage'],
    requiredFeatures: ['task_statuses'],
  },
  'roles.list': {
    requiredAbilities: ['roles.view'],
    requiredFeatures: ['roles'],
  },
  'roles.create': {
    requiredAbilities: ['roles.manage'],
    requiredFeatures: ['roles'],
  },
  'roles.edit': {
    requiredAbilities: ['roles.view', 'roles.manage'],
    requireAllAbilities: true,
    requiredFeatures: ['roles'],
  },
  'manuals.list': {
    requiredAbilities: ['manuals.manage'],
    requiredFeatures: ['manuals'],
  },
  'manuals.create': {
    requiredAbilities: ['manuals.manage'],
    requiredFeatures: ['manuals'],
  },
  'manuals.edit': {
    requiredAbilities: ['manuals.manage'],
    requiredFeatures: ['manuals'],
  },
  'notifications.inbox': {
    requiredAbilities: ['notifications.view'],
    requiredFeatures: ['notifications'],
  },
  'notifications.prefs': {
    requiredAbilities: ['notifications.view'],
    requiredFeatures: ['notifications'],
  },
  'settings.branding': {
    requiredAbilities: ['branding.manage'],
    requiredFeatures: ['branding'],
  },
  'settings.footer': {
    requiredAbilities: ['branding.manage'],
    requiredFeatures: ['branding'],
  },
  'gdpr.index': {
    requiredAbilities: ['gdpr.view', 'gdpr.manage'],
    requireAllAbilities: true,
    requiredFeatures: ['gdpr'],
  },
  reports: {
    requiredAbilities: ['reports.view'],
    requiredFeatures: ['reports'],
  },
  'reports.kpis': {
    requiredAbilities: ['reports.view'],
    requiredFeatures: ['reports'],
  },
  'employees.list': {
    requiredAbilities: ['employees.view'],
    requiredFeatures: ['employees'],
  },
  'employees.create': {
    requiredAbilities: ['employees.manage'],
    requiredFeatures: ['employees'],
  },
  'employees.edit': {
    requiredAbilities: ['employees.view', 'employees.manage'],
    requireAllAbilities: true,
    requiredFeatures: ['employees'],
  },
  'tenants.list': {
    requiredAbilities: ['tenants.view'],
    requiredFeatures: ['tenants'],
  },
  'tenants.create': {
    requiredAbilities: ['tenants.create'],
    requiredFeatures: ['tenants'],
  },
  'tenants.edit': {
    requiredAbilities: ['tenants.view', 'tenants.update'],
    requireAllAbilities: true,
    requiredFeatures: ['tenants'],
  },
  'tenants.view': {
    requiredAbilities: ['tenants.view'],
    requiredFeatures: ['tenants'],
  },
  'teams.list': {
    requiredAbilities: ['teams.view'],
    requiredFeatures: ['teams'],
  },
  'teams.create': {
    requiredAbilities: ['teams.create'],
    requiredFeatures: ['teams'],
  },
  'teams.edit': {
    requiredAbilities: ['teams.view', 'teams.update'],
    requireAllAbilities: true,
    requiredFeatures: ['teams'],
  },
};

export function accessForRoute(name?: string): RouteAccess {
  if (!name) {
    return {};
  }
  return routeAccessMap[name] || {};
}

export interface SidebarMenuChild extends RouteAccess {
  childtitle: string;
  childlink: string;
}

export interface SidebarMenuItem extends RouteAccess {
  title: string;
  icon?: string;
  link?: string;
  child?: SidebarMenuChild[];
  isHeadr?: boolean;
}

export const menuItems: SidebarMenuItem[] = [
  {
    title: 'Dashboard',
    icon: 'heroicons-outline:home',
    link: 'dashboard',
    ...accessForRoute('dashboard'),
  },
  {
    title: 'Tasks',
    icon: 'heroicons-outline:calendar',
    link: 'tasks.list',
    ...accessForRoute('tasks.list'),
  },
  {
    title: 'Task Board',
    icon: 'heroicons-outline:view-columns',
    link: 'tasks.board',
    ...accessForRoute('tasks.board'),
  },
  {
    title: 'Task Reports',
    icon: 'heroicons-outline:chart-bar',
    link: 'tasks.reports',
    ...accessForRoute('tasks.reports'),
  },
  {
    title: 'Task Types',
    icon: 'heroicons-outline:tag',
    link: 'taskTypes.list',
    ...accessForRoute('taskTypes.list'),
  },
  {
    title: 'Teams',
    icon: 'heroicons-outline:user-group',
    link: 'teams.list',
    ...accessForRoute('teams.list'),
  },
  {
    title: 'Task Statuses',
    icon: 'heroicons-outline:check-circle',
    link: 'taskStatuses.list',
    ...accessForRoute('taskStatuses.list'),
  },
  {
    title: 'Roles',
    icon: 'heroicons-outline:key',
    link: 'roles.list',
    ...accessForRoute('roles.list'),
  },
  {
    title: 'Manuals',
    icon: 'heroicons-outline:book-open',
    link: 'manuals.list',
    ...accessForRoute('manuals.list'),
  },
  {
    title: 'Users',
    icon: 'heroicons-outline:users',
    child: [
      {
        childtitle: 'Employees',
        childlink: 'employees.list',
        ...accessForRoute('employees.list'),
      },
      {
        childtitle: 'Tenants',
        childlink: 'tenants.list',
        ...accessForRoute('tenants.list'),
      },
    ],
  },
  {
    title: 'Reports',
    icon: 'heroicons-outline:chart-bar',
    link: 'reports.kpis',
    ...accessForRoute('reports.kpis'),
  },
  {
    title: 'Notifications',
    icon: 'heroicons-outline:bell',
    link: 'notifications.inbox',
    ...accessForRoute('notifications.inbox'),
  },
  {
    title: 'Settings',
    icon: 'heroicons-outline:cog',
    child: [
      { childtitle: 'Profile', childlink: 'settings.profile', ...accessForRoute('settings.profile') },
      {
        childtitle: 'Branding',
        childlink: 'settings.branding',
        ...accessForRoute('settings.branding'),
      },
      {
        childtitle: 'Footer',
        childlink: 'settings.footer',
        ...accessForRoute('settings.footer'),
      },
      {
        childtitle: 'GDPR',
        childlink: 'gdpr.index',
        ...accessForRoute('gdpr.index'),
      },
    ],
  },
];

export interface TopMenuItem extends RouteAccess {
  title: string;
  icon?: string;
  link?: string;
  child?: SidebarMenuChild[];
}

export const topMenu: TopMenuItem[] = [
  {
    title: 'Dashboard',
    icon: 'heroicons-outline:home',
    link: 'dashboard',
    ...accessForRoute('dashboard'),
  },
  {
    title: 'Tasks',
    icon: 'heroicons-outline:calendar',
    link: 'tasks.list',
    ...accessForRoute('tasks.list'),
  },
  {
    title: 'Task Board',
    icon: 'heroicons-outline:view-columns',
    link: 'tasks.board',
    ...accessForRoute('tasks.board'),
  },
  {
    title: 'Task Reports',
    icon: 'heroicons-outline:chart-bar',
    link: 'tasks.reports',
    ...accessForRoute('tasks.reports'),
  },
  {
    title: 'Task Types',
    icon: 'heroicons-outline:tag',
    link: 'taskTypes.list',
    ...accessForRoute('taskTypes.list'),
  },
  {
    title: 'Teams',
    icon: 'heroicons-outline:user-group',
    link: 'teams.list',
    ...accessForRoute('teams.list'),
  },
  {
    title: 'Task Statuses',
    icon: 'heroicons-outline:check-circle',
    link: 'taskStatuses.list',
    ...accessForRoute('taskStatuses.list'),
  },
  {
    title: 'Roles',
    icon: 'heroicons-outline:key',
    link: 'roles.list',
    ...accessForRoute('roles.list'),
  },
  {
    title: 'Manuals',
    icon: 'heroicons-outline:book-open',
    link: 'manuals.list',
    ...accessForRoute('manuals.list'),
  },
  {
    title: 'Users',
    icon: 'heroicons-outline:users',
    child: [
      {
        childtitle: 'Employees',
        childlink: 'employees.list',
        ...accessForRoute('employees.list'),
      },
      {
        childtitle: 'Tenants',
        childlink: 'tenants.list',
        ...accessForRoute('tenants.list'),
      },
    ],
  },
  {
    title: 'Reports',
    icon: 'heroicons-outline:chart-bar',
    link: 'reports.kpis',
    ...accessForRoute('reports.kpis'),
  },
  {
    title: 'Notifications',
    icon: 'heroicons-outline:bell',
    link: 'notifications.inbox',
    ...accessForRoute('notifications.inbox'),
  },
  {
    title: 'Settings',
    icon: 'heroicons-outline:cog',
    link: 'settings.profile',
    ...accessForRoute('settings.profile'),
  },
  {
    title: 'Branding',
    icon: 'heroicons-outline:sparkles',
    link: 'settings.branding',
    ...accessForRoute('settings.branding'),
  },
  {
    title: 'Footer',
    icon: 'heroicons-outline:document-text',
    link: 'settings.footer',
    ...accessForRoute('settings.footer'),
  },
  {
    title: 'GDPR',
    icon: 'heroicons-outline:shield-check',
    link: 'gdpr.index',
    ...accessForRoute('gdpr.index'),
  },
];

export interface QuickAction extends RouteAccess {
  label: string;
  icon: string;
  link: string;
}

export const addNewOptions: QuickAction[] = [
  {
    label: 'Task',
    icon: 'heroicons-outline:calendar',
    link: 'tasks.create',
    ...accessForRoute('tasks.create'),
  },
  {
    label: 'Task Type',
    icon: 'heroicons-outline:tag',
    link: 'taskTypes.create',
    ...accessForRoute('taskTypes.create'),
  },
  {
    label: 'Manual',
    icon: 'heroicons-outline:book-open',
    link: 'manuals.create',
    ...accessForRoute('manuals.create'),
  },
  {
    label: 'Employee',
    icon: 'heroicons-outline:users',
    link: 'employees.create',
    ...accessForRoute('employees.create'),
  },
  {
    label: 'Task Status',
    icon: 'heroicons-outline:check-circle',
    link: 'taskStatuses.create',
    ...accessForRoute('taskStatuses.create'),
  },
  {
    label: 'Role',
    icon: 'heroicons-outline:key',
    link: 'roles.create',
    ...accessForRoute('roles.create'),
  },
];

export const routeAccess = routeAccessMap;
