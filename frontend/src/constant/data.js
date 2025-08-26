export const menuItems = [
  {
    isHeadr: true,
    title: "menu",
  },
  {
    title: "Dashboard",
    icon: "heroicons-outline:home",
    isOpen: true,
    child: [
      { childtitle: "Analytics Dashboard", childlink: "home" },
      { childtitle: "Ecommerce Dashboard", childlink: "ecommerce" },
      { childtitle: "Project Dashboard", childlink: "project" },
      { childtitle: "CRM Dashboard", childlink: "crm" },
      { childtitle: "Banking Dashboard", childlink: "banking" },
    ],
  },
  {
    title: "changelog",
    icon: "heroicons-outline:document-text",
    link: "/docs/changelog",
    badge: "1.0.0",
  },
];
