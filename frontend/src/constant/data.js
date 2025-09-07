import avatarImg from "@/assets/images/logo/logo.svg";

export const menuItems = [
  {
    title: "Dashboard",
    icon: "heroicons-outline:home",
    link: "dashboard",
  },
  {
    title: "Tasks",
    icon: "heroicons-outline:calendar",
    link: "tasks.list",
    requiredAbilities: ["tasks.view", "tasks.manage"],
  },
  {
    title: "Task Board",
    icon: "heroicons-outline:view-columns",
    link: "tasks.board",
    requiredAbilities: ["tasks.view", "tasks.update"],
  },
  {
    title: "Task Reports",
    icon: "heroicons-outline:chart-bar",
    link: "tasks.reports",
    requiredAbilities: ["reports.view"],
  },
  {
    title: "Task Types",
    icon: "heroicons-outline:tag",
    link: "taskTypes.list",
    requiredAbilities: [
      "task_types.view",
      "task_types.manage",
      "task_sla_policies.manage",
      "task_automations.manage",
      "task_field_snippets.manage",
    ],
  },
  {
    title: "Teams",
    icon: "heroicons-outline:user-group",
    link: "teams.list",
    requiredAbilities: ["teams.view", "teams.manage"],
  },
  {
    title: "Task Statuses",
    icon: "heroicons-outline:check-circle",
    link: "taskStatuses.list",
    requiredAbilities: ["task_statuses.view", "task_statuses.manage"],
  },
  {
    title: "Roles",
    icon: "heroicons-outline:key",
    link: "roles.list",
    requiredAbilities: ["roles.view", "roles.manage"],
    admin: true,
  },
  {
    title: "Manuals",
    icon: "heroicons-outline:book-open",
    link: "manuals.list",
    admin: true,
  },
  {
    title: "Employees",
    icon: "heroicons-outline:users",
    link: "employees.list",
    requiredAbilities: ["employees.view", "employees.manage"],
  },
  {
    title: "Reports",
    icon: "heroicons-outline:chart-bar",
    link: "reports.kpis",
    requiredAbilities: ["reports.view"],
    admin: true,
  },
  {
    title: "Tenants",
    icon: "heroicons-outline:building-office",
    link: "tenants.list",
    admin: true,
  },
  {
    title: "Notifications",
    icon: "heroicons-outline:bell",
    link: "notifications.inbox",
    requiredAbilities: ["notifications.view", "notifications.manage"],
    requiredFeatures: ["notifications"],
  },
  {
    title: "Settings",
    icon: "heroicons-outline:cog",
    child: [
      { childtitle: "Profile", childlink: "settings.profile" },
      {
        childtitle: "Branding",
        childlink: "settings.branding",
        requiredAbilities: ["branding.manage"],
        requiredFeatures: ["branding"],
      },
      {
        childtitle: "Footer",
        childlink: "settings.footer",
        requiredAbilities: ["branding.manage"],
        requiredFeatures: ["branding"],
      },
      {
        childtitle: "GDPR",
        childlink: "gdpr.index",
        requiredAbilities: ["gdpr.view", "gdpr.manage"],
      },
    ],
  },
];

export const topMenu = [
  { title: "Dashboard", icon: "heroicons-outline:home", link: "dashboard" },
  {
    title: "Tasks",
    icon: "heroicons-outline:calendar",
    link: "tasks.list",
    requiredAbilities: ["tasks.view", "tasks.manage"],
  },
  {
    title: "Task Board",
    icon: "heroicons-outline:view-columns",
    link: "tasks.board",
    requiredAbilities: ["tasks.view", "tasks.update"],
  },
  {
    title: "Task Reports",
    icon: "heroicons-outline:chart-bar",
    link: "tasks.reports",
    requiredAbilities: ["reports.view"],
  },
  {
    title: "Task Types",
    icon: "heroicons-outline:tag",
    link: "taskTypes.list",
    requiredAbilities: [
      "task_types.view",
      "task_types.manage",
      "task_sla_policies.manage",
      "task_automations.manage",
      "task_field_snippets.manage",
    ],
  },
  {
    title: "Teams",
    icon: "heroicons-outline:user-group",
    link: "teams.list",
    requiredAbilities: ["teams.view", "teams.manage"],
  },
  {
    title: "Task Statuses",
    icon: "heroicons-outline:check-circle",
    link: "taskStatuses.list",
    requiredAbilities: ["task_statuses.view", "task_statuses.manage"],
  },
  {
    title: "Roles",
    icon: "heroicons-outline:key",
    link: "roles.list",
    requiredAbilities: ["roles.view", "roles.manage"],
    admin: true,
  },
  {
    title: "Manuals",
    icon: "heroicons-outline:book-open",
    link: "manuals.list",
    admin: true,
  },
  {
    title: "Employees",
    icon: "heroicons-outline:users",
    link: "employees.list",
    requiredAbilities: ["employees.view", "employees.manage"],
  },
  {
    title: "Reports",
    icon: "heroicons-outline:chart-bar",
    link: "reports.kpis",
    requiredAbilities: ["reports.view"],
    admin: true,
  },
  {
    title: "Notifications",
    icon: "heroicons-outline:bell",
    link: "notifications.inbox",
    requiredAbilities: ["notifications.view", "notifications.manage"],
    requiredFeatures: ["notifications"],
  },
  { title: "Settings", icon: "heroicons-outline:cog", link: "settings.profile" },
  {
    title: "Branding",
    icon: "heroicons-outline:sparkles",
    link: "settings.branding",
    admin: true,
    requiredAbilities: ["branding.manage"],
    requiredFeatures: ["branding"],
  },
  {
    title: "Footer",
    icon: "heroicons-outline:document-text",
    link: "settings.footer",
    admin: true,
    requiredAbilities: ["branding.manage"],
    requiredFeatures: ["branding"],
  },
  {
    title: "GDPR",
    icon: "heroicons-outline:shield-check",
    link: "gdpr.index",
    requiredAbilities: ["gdpr.view", "gdpr.manage"],
  },
  {
    title: "Tenants",
    icon: "heroicons-outline:building-office",
    link: "tenants.list",
    admin: true,
    requiredAbilities: ["tenants.view", "tenants.manage"],
  },
];

// Quick access options for creating new resources
export const addNewOptions = [
  {
    label: 'Task',
    icon: 'heroicons-outline:calendar',
    link: 'tasks.create',
    requiredAbilities: ['tasks.manage'],
  },
  {
    label: 'Task Type',
    icon: 'heroicons-outline:tag',
    link: 'taskTypes.create',
    requiredAbilities: [
      'task_types.create',
      'task_types.manage',
      'task_sla_policies.manage',
      'task_automations.manage',
      'task_field_snippets.manage',
    ],
    admin: true,
  },
  {
    label: 'Manual',
    icon: 'heroicons-outline:book-open',
    link: 'manuals.create',
    requiredAbilities: ['manuals.manage'],
    admin: true,
  },
  {
    label: 'Employee',
    icon: 'heroicons-outline:users',
    link: 'employees.create',
    requiredAbilities: ['employees.manage'],
  },
  {
    label: 'Task Status',
    icon: 'heroicons-outline:check-circle',
    link: 'taskStatuses.create',
    requiredAbilities: ['task_statuses.manage'],
    admin: true,
  },
  {
    label: 'Role',
    icon: 'heroicons-outline:key',
    link: 'roles.create',
    requiredAbilities: ['roles.manage'],
    admin: true,
  },
];

export const notifications = [
  {
    title: "Your order is placed",
    desc: "Amet minim mollit non deser unt ullamco est sit aliqua.",
    image: avatarImg,
    link: "#",
  },
  {
    title: "Congratulations Darlene  🎉",
    desc: "Won the monthly best seller badge",
    unread: true,
    image: avatarImg,
    link: "#",
  },
  {
    title: "Revised Order 👋",
    desc: "Won the monthly best seller badge",
    image: avatarImg,
    link: "#",
  },
  {
    title: "Brooklyn Simmons",
    desc: "Added you to Top Secret Project group...",
    image: avatarImg,
    link: "#",
  },
];

export const message = [
  {
    title: "Wade Warren",
    desc: "Hi! How are you doing?.....",
    active: true,
    hasnotifaction: true,
    notification_count: 1,
    image: avatarImg,
    link: "#",
  },
  {
    title: "Savannah Nguyen",
    desc: "Hi! How are you doing?.....",
    active: false,
    hasnotifaction: false,
    image: avatarImg,
    link: "#",
  },
  {
    title: "Ralph Edwards",
    desc: "Hi! How are you doing?.....",
    active: false,
    hasnotifaction: true,
    notification_count: 8,
    image: avatarImg,
    link: "#",
  },
  {
    title: "Cody Fisher",
    desc: "Hi! How are you doing?.....",
    active: true,
    hasnotifaction: false,
    image: avatarImg,
    link: "#",
  },
];
