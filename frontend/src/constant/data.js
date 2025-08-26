export const menuItems = [
  {
    title: "Dashboard",
    icon: "heroicons-outline:home",
    link: "/",
  },
  {
    title: "Appointments",
    icon: "heroicons-outline:calendar",
    child: [
      { childtitle: "Appointments", childlink: "/appointments" },
      { childtitle: "Appointment Types", childlink: "/appointment-types" },
    ],
  },
  {
    title: "Employees",
    icon: "heroicons-outline:users",
    link: "/employees",
  },
  {
    title: "Reports",
    icon: "heroicons-outline:chart-bar",
    link: "/reports",
  },
  {
    title: "Notifications",
    icon: "heroicons-outline:bell",
    link: "/notifications",
  },
  {
    title: "Settings",
    icon: "heroicons-outline:cog",
    child: [
      { childtitle: "Settings", childlink: "/settings" },
      { childtitle: "GDPR", childlink: "/settings/gdpr" },
    ],
  },
];

export const topMenu = [
  { title: "Dashboard", icon: "heroicons-outline:home", link: "/" },
  { title: "Appointments", icon: "heroicons-outline:calendar", link: "/appointments" },
  { title: "Appointment Types", icon: "heroicons-outline:template", link: "/appointment-types" },
  { title: "Employees", icon: "heroicons-outline:users", link: "/employees" },
  { title: "Reports", icon: "heroicons-outline:chart-bar", link: "/reports" },
  { title: "Notifications", icon: "heroicons-outline:bell", link: "/notifications" },
  { title: "Settings", icon: "heroicons-outline:cog", link: "/settings" },
  { title: "GDPR", icon: "heroicons-outline:shield-check", link: "/settings/gdpr" },
];
