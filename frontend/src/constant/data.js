export const menuItems = [
  {
    title: "Dashboard",
    icon: "heroicons-outline:home",
    link: "dashboard",
  },
  {
    title: "Appointments",
    icon: "heroicons-outline:calendar",
    link: "appointments.list",
  },
  {
    title: "Types",
    icon: "heroicons-outline:tag",
    link: "types.list",
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
    admin: true,
  },
  {
    title: "Reports",
    icon: "heroicons-outline:chart-bar",
    link: "reports.kpis",
    admin: true,
  },
  {
    title: "Notifications",
    icon: "heroicons-outline:bell",
    link: "notifications.inbox",
  },
  {
    title: "Settings",
    icon: "heroicons-outline:cog",
    child: [
      { childtitle: "Profile", childlink: "settings.profile" },
      { childtitle: "Branding", childlink: "settings.branding", admin: true },
      { childtitle: "GDPR", childlink: "gdpr.index" },
      { childtitle: "Tenants", childlink: "tenants.list", admin: true },
    ],
  },
];

export const topMenu = [
  { title: "Dashboard", icon: "heroicons-outline:home", link: "dashboard" },
  {
    title: "Appointments",
    icon: "heroicons-outline:calendar",
    link: "appointments.list",
  },
  { title: "Types", icon: "heroicons-outline:tag", link: "types.list", admin: true },
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
    admin: true,
  },
  {
    title: "Reports",
    icon: "heroicons-outline:chart-bar",
    link: "reports.kpis",
    admin: true,
  },
  {
    title: "Notifications",
    icon: "heroicons-outline:bell",
    link: "notifications.inbox",
  },
  { title: "Settings", icon: "heroicons-outline:cog", link: "settings.profile" },
  {
    title: "Branding",
    icon: "heroicons-outline:sparkles",
    link: "settings.branding",
    admin: true,
  },
  { title: "GDPR", icon: "heroicons-outline:shield-check", link: "gdpr.index" },
  {
    title: "Tenants",
    icon: "heroicons-outline:building-office",
    link: "tenants.list",
    admin: true,
  },
];
