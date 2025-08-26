export const menuItems = [
  {
    title: "Dashboard",
    icon: "heroicons-outline:home",
    link: "dashboard",
  },
  {
    title: "Appointments",
    icon: "heroicons-outline:calendar",
    link: "appointments",
  },
  {
    title: "Employees",
    icon: "heroicons-outline:users",
    link: "employees",
  },
  {
    title: "Reports",
    icon: "heroicons-outline:chart-bar",
    link: "reports",
  },
  {
    title: "Notifications",
    icon: "heroicons-outline:bell",
    link: "notifications",
  },
  {
    title: "Settings",
    icon: "heroicons-outline:cog",
    child: [
      { childtitle: "Settings", childlink: "settings" },
      { childtitle: "GDPR", childlink: "settings-gdpr" },
    ],
  },
];

export const topMenu = [
  { title: "Dashboard", icon: "heroicons-outline:home", link: "dashboard" },
  { title: "Appointments", icon: "heroicons-outline:calendar", link: "appointments" },
  { title: "Employees", icon: "heroicons-outline:users", link: "employees" },
  { title: "Reports", icon: "heroicons-outline:chart-bar", link: "reports" },
  { title: "Notifications", icon: "heroicons-outline:bell", link: "notifications" },
  { title: "Settings", icon: "heroicons-outline:cog", link: "settings" },
  { title: "GDPR", icon: "heroicons-outline:shield-check", link: "settings-gdpr" },
];
