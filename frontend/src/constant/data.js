import avatarImg from "@/assets/images/logo/logo.svg";

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
      { childtitle: "Footer", childlink: "settings.footer", admin: true },
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
  {
    title: "Footer",
    icon: "heroicons-outline:document-text",
    link: "settings.footer",
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

// Quick access options for creating new resources
export const addNewOptions = [
  {
    label: 'Appointment',
    icon: 'heroicons-outline:calendar',
    link: 'appointments.create',
  },
  {
    label: 'Type',
    icon: 'heroicons-outline:tag',
    link: 'types.create',
    admin: true,
  },
  {
    label: 'Manual',
    icon: 'heroicons-outline:book-open',
    link: 'manuals.create',
    admin: true,
  },
  {
    label: 'Employee',
    icon: 'heroicons-outline:users',
    link: 'employees.create',
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
