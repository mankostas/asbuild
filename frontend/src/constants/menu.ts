import { reactive } from 'vue';
import { abilityFor, onPermissionsLoaded } from '@/services/permissions';

export interface RouteAccess {
  requiredAbilities?: string[];
  requireAllAbilities?: boolean;
  requiredFeatures?: string[];
}

interface RouteAccessConfig {
  feature?: string;
  abilities?: string[];
  requireAllAbilities?: boolean;
}

const routeAccessConfig: Record<string, RouteAccessConfig> = {
  dashboard: { feature: 'dashboard', abilities: ['view'] },
  'tasks.list': { feature: 'tasks', abilities: ['view'] },
  'tasks.create': { feature: 'tasks', abilities: ['create'] },
  'tasks.edit': {
    feature: 'tasks',
    abilities: ['view', 'update'],
    requireAllAbilities: true,
  },
  'tasks.details': { feature: 'tasks', abilities: ['view'] },
  'tasks.board': { feature: 'tasks', abilities: ['view'] },
  'tasks.reports': { feature: 'reports', abilities: ['view'] },
  'taskTypes.list': { feature: 'task_types', abilities: ['view'] },
  'taskTypes.create': { feature: 'task_types', abilities: ['create'] },
  'taskTypes.edit': { feature: 'task_types', abilities: ['update'] },
  'taskStatuses.list': { feature: 'task_statuses', abilities: ['view'] },
  'taskStatuses.create': { feature: 'task_statuses', abilities: ['manage'] },
  'taskStatuses.edit': { feature: 'task_statuses', abilities: ['manage'] },
  'roles.list': { feature: 'roles', abilities: ['view'] },
  'roles.create': { feature: 'roles', abilities: ['create'] },
  'roles.edit': {
    feature: 'roles',
    abilities: ['update'],
    requireAllAbilities: true,
  },
  'manuals.list': { feature: 'manuals', abilities: ['view'] },
  'manuals.create': { feature: 'manuals', abilities: ['manage'] },
  'manuals.edit': { feature: 'manuals', abilities: ['manage'] },
  'notifications.inbox': { feature: 'notifications', abilities: ['view'] },
  'notifications.prefs': { feature: 'notifications', abilities: ['view'] },
  'settings.branding': { feature: 'branding', abilities: ['manage'] },
  'settings.footer': { feature: 'branding', abilities: ['manage'] },
  'gdpr.index': { feature: 'gdpr', abilities: ['view'] },
  reports: { feature: 'reports', abilities: ['view'] },
  'reports.kpis': { feature: 'reports', abilities: ['view'] },
  'employees.list': { feature: 'employees', abilities: ['view'] },
  'employees.create': { feature: 'employees', abilities: ['create'] },
  'employees.edit': {
    feature: 'employees',
    abilities: ['view', 'manage'],
    requireAllAbilities: true,
  },
  'clients.list': { feature: 'clients', abilities: ['view'] },
  'clients.create': { feature: 'clients', abilities: ['create'] },
  'clients.edit': {
    feature: 'clients',
    abilities: ['view', 'manage'],
    requireAllAbilities: true,
  },
  'tenants.list': { feature: 'tenants', abilities: ['view'] },
  'tenants.create': { feature: 'tenants', abilities: ['create'] },
  'tenants.edit': {
    feature: 'tenants',
    abilities: ['view', 'update'],
    requireAllAbilities: true,
  },
  'tenants.view': { feature: 'tenants', abilities: ['view'] },
  'teams.list': { feature: 'teams', abilities: ['view'] },
  'teams.create': { feature: 'teams', abilities: ['create'] },
  'teams.edit': {
    feature: 'teams',
    abilities: ['view', 'update'],
    requireAllAbilities: true,
  },
};

// Routes that should be explicitly represented in the access map even when they
// do not require any abilities or features. Keeping them here allows future
// adjustments to be made without touching the consuming code.
const additionalRouteAccess: Record<string, RouteAccess> = {
  'settings.profile': {
    requiredAbilities: [],
    requiredFeatures: [],
  },
};

const routeAccessMap = reactive<Record<string, RouteAccess>>({});

function resolveAbilityList(
  feature: string | undefined,
  abilitySpecs: string[] | undefined,
): string[] {
  if (!abilitySpecs || abilitySpecs.length === 0) {
    return [];
  }

  const resolved = abilitySpecs
    .map((spec) => {
      if (spec.includes('.')) {
        const [abilityFeature, ...rest] = spec.split('.');
        if (!abilityFeature || rest.length === 0) {
          return spec;
        }
        const ability = rest.join('.');
        return abilityFor(abilityFeature, ability);
      }

      if (!feature) {
        return undefined;
      }

      return abilityFor(feature, spec);
    })
    .filter((ability): ability is string => Boolean(ability));

  return Array.from(new Set(resolved));
}

function buildRouteAccess(config: RouteAccessConfig): RouteAccess {
  const access: RouteAccess = {};
  const requiredAbilities = resolveAbilityList(config.feature, config.abilities);
  if (requiredAbilities.length > 0) {
    access.requiredAbilities = requiredAbilities;
  }
  if (config.feature) {
    access.requiredFeatures = [config.feature];
  }
  if (config.requireAllAbilities) {
    access.requireAllAbilities = true;
  }
  return access;
}

function rebuildRouteAccess(): void {
  Object.keys(routeAccessMap).forEach((key) => {
    delete routeAccessMap[key];
  });

  Object.entries(routeAccessConfig).forEach(([route, config]) => {
    routeAccessMap[route] = buildRouteAccess(config);
  });

  Object.entries(additionalRouteAccess).forEach(([route, access]) => {
    routeAccessMap[route] = { ...access };
  });
}

export function accessForRoute(name?: string): RouteAccess {
  if (!name) {
    return {};
  }
  return routeAccessMap[name] || {};
}

export interface SidebarMenuChild extends RouteAccess {
  childtitle: string;
  childlink: string;
  childicon?: string;
  topTitle?: string;
}

export interface SidebarMenuItem extends RouteAccess {
  title: string;
  icon?: string;
  link?: string;
  child?: SidebarMenuChild[];
  isHeadr?: boolean;
}

interface MenuItemDefinition extends SidebarMenuItem {
  topNav?: {
    flattenChildren?: boolean;
  };
}

interface SidebarMenuChildBlueprint {
  childtitle: string;
  childlink: string;
  childicon?: string;
  topTitle?: string;
}

interface MenuItemBlueprint {
  title: string;
  icon?: string;
  link?: string;
  child?: SidebarMenuChildBlueprint[];
  isHeadr?: boolean;
  topNav?: {
    flattenChildren?: boolean;
  };
}

const menuBlueprints: readonly MenuItemBlueprint[] = [
  {
    title: 'Dashboard',
    icon: 'heroicons-outline:home',
    link: 'dashboard',
  },
  {
    title: 'Tasks',
    icon: 'heroicons-outline:calendar',
    link: 'tasks.list',
  },
  {
    title: 'Task Board',
    icon: 'heroicons-outline:view-columns',
    link: 'tasks.board',
  },
  {
    title: 'Task Reports',
    icon: 'heroicons-outline:chart-bar',
    link: 'tasks.reports',
  },
  {
    title: 'Task Types',
    icon: 'heroicons-outline:tag',
    link: 'taskTypes.list',
  },
  {
    title: 'Teams',
    icon: 'heroicons-outline:user-group',
    link: 'teams.list',
  },
  {
    title: 'Task Statuses',
    icon: 'heroicons-outline:check-circle',
    link: 'taskStatuses.list',
  },
  {
    title: 'Roles',
    icon: 'heroicons-outline:key',
    link: 'roles.list',
  },
  {
    title: 'Manuals',
    icon: 'heroicons-outline:book-open',
    link: 'manuals.list',
  },
  {
    title: 'Users',
    icon: 'heroicons-outline:users',
    child: [
      {
        childtitle: 'Employees',
        childlink: 'employees.list',
      },
      {
        childtitle: 'Clients',
        childlink: 'clients.list',
      },
      {
        childtitle: 'Tenants',
        childlink: 'tenants.list',
      },
    ],
  },
  {
    title: 'Reports',
    icon: 'heroicons-outline:chart-bar',
    link: 'reports.kpis',
  },
  {
    title: 'Notifications',
    icon: 'heroicons-outline:bell',
    link: 'notifications.inbox',
  },
  {
    title: 'Settings',
    icon: 'heroicons-outline:cog',
    child: [
      {
        childtitle: 'Profile',
        childlink: 'settings.profile',
        childicon: 'heroicons-outline:cog',
        topTitle: 'Settings',
      },
      {
        childtitle: 'Branding',
        childlink: 'settings.branding',
        childicon: 'heroicons-outline:sparkles',
      },
      {
        childtitle: 'Footer',
        childlink: 'settings.footer',
        childicon: 'heroicons-outline:document-text',
      },
      {
        childtitle: 'GDPR',
        childlink: 'gdpr.index',
        childicon: 'heroicons-outline:shield-check',
      },
    ],
    topNav: {
      flattenChildren: true,
    },
  },
];

function buildMenuItem(blueprint: MenuItemBlueprint): MenuItemDefinition {
  const child = blueprint.child?.map((item) => ({
    ...item,
    ...accessForRoute(item.childlink),
  }));

  return {
    ...blueprint,
    ...(child ? { child } : {}),
    ...accessForRoute(blueprint.link),
  };
}

export interface TopMenuItem extends RouteAccess {
  title: string;
  icon?: string;
  link?: string;
  child?: SidebarMenuChild[];
  isHeadr?: boolean;
}

const toTopMenuItems = (item: MenuItemDefinition): TopMenuItem[] => {
  if (item.topNav?.flattenChildren && item.child) {
    return item.child.map((child, index) => ({
      title: child.topTitle ?? child.childtitle,
      icon: child.childicon ?? (index === 0 ? item.icon : undefined),
      link: child.childlink,
      requiredAbilities: child.requiredAbilities,
      requireAllAbilities: child.requireAllAbilities,
      requiredFeatures: child.requiredFeatures,
    }));
  }

  const { topNav, ...rest } = item;
  return [{ ...rest }];
};

const menuItemsState = reactive<SidebarMenuItem[]>([]);
const topMenuState = reactive<TopMenuItem[]>([]);

function rebuildMenus(): void {
  const definitions = menuBlueprints.map(buildMenuItem);
  menuItemsState.splice(0, menuItemsState.length, ...definitions.map(({ topNav, ...item }) => ({ ...item })));
  topMenuState.splice(0, topMenuState.length, ...definitions.flatMap(toTopMenuItems));
}

export interface MenuAccessEvaluator {
  hasFeature(feature: string): boolean;
  hasAllAbilities(abilities: string[]): boolean;
  hasAnyAbility(abilities: string[]): boolean;
}

const satisfiesAccess = (
  access: RouteAccess,
  evaluator: MenuAccessEvaluator,
): boolean => {
  const features = access.requiredFeatures ?? [];
  if (!features.every((feature) => evaluator.hasFeature(feature))) {
    return false;
  }

  const abilities = access.requiredAbilities ?? [];
  if (abilities.length === 0) {
    return true;
  }

  return access.requireAllAbilities
    ? evaluator.hasAllAbilities(abilities)
    : evaluator.hasAnyAbility(abilities);
};

const filterChildrenByAccess = (
  children: SidebarMenuChild[] | undefined,
  evaluator: MenuAccessEvaluator,
): SidebarMenuChild[] | undefined => {
  if (!children) {
    return undefined;
  }

  const filtered = children.filter((child) => satisfiesAccess(child, evaluator));
  return filtered.length > 0 ? filtered : undefined;
};

export function filterMenuItems<
  T extends RouteAccess & { child?: SidebarMenuChild[] }
>(items: readonly T[], evaluator: MenuAccessEvaluator): T[] {
  return items
    .map((item) => {
      if (!satisfiesAccess(item, evaluator)) {
        return null;
      }

      if (item.child) {
        const child = filterChildrenByAccess(item.child, evaluator);
        if (!child) {
          return null;
        }
        return { ...item, child } as T;
      }

      return { ...item } as T;
    })
    .filter((item): item is T => Boolean(item));
}

export interface QuickAction extends RouteAccess {
  label: string;
  icon: string;
  link: string;
}

interface QuickActionBlueprint {
  label: string;
  icon: string;
  link: string;
}

const quickActionBlueprints: readonly QuickActionBlueprint[] = [
  {
    label: 'Task',
    icon: 'heroicons-outline:calendar',
    link: 'tasks.create',
  },
  {
    label: 'Task Type',
    icon: 'heroicons-outline:tag',
    link: 'taskTypes.create',
  },
  {
    label: 'Manual',
    icon: 'heroicons-outline:book-open',
    link: 'manuals.create',
  },
  {
    label: 'Employee',
    icon: 'heroicons-outline:users',
    link: 'employees.create',
  },
  {
    label: 'Client',
    icon: 'heroicons-outline:user-group',
    link: 'clients.create',
  },
  {
    label: 'Task Status',
    icon: 'heroicons-outline:check-circle',
    link: 'taskStatuses.create',
  },
  {
    label: 'Role',
    icon: 'heroicons-outline:key',
    link: 'roles.create',
  },
];

const addNewOptionsState = reactive<QuickAction[]>([]);

function rebuildQuickActions(): void {
  const actions = quickActionBlueprints.map((action) => ({
    ...action,
    ...accessForRoute(action.link),
  }));
  addNewOptionsState.splice(0, addNewOptionsState.length, ...actions);
}

function rebuildAll(): void {
  rebuildRouteAccess();
  rebuildMenus();
  rebuildQuickActions();
}

rebuildAll();
onPermissionsLoaded(rebuildAll);

export const menuItems: SidebarMenuItem[] = menuItemsState;
export const topMenu: TopMenuItem[] = topMenuState;
export const addNewOptions: QuickAction[] = addNewOptionsState;
export const routeAccess = routeAccessMap;
