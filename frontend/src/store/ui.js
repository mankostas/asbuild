import { defineStore } from 'pinia';

const PREFIX = 'dc_';

function getItem(key, defaultValue) {
  const value = localStorage.getItem(PREFIX + key);
  try {
    return value !== null ? JSON.parse(value) : defaultValue;
  } catch {
    return defaultValue;
  }
}

function setItem(key, value) {
  localStorage.setItem(PREFIX + key, JSON.stringify(value));
}

const initialTheme = getItem('theme', 'light');
const initialMenuLayout = getItem('menuLayout', 'vertical');
const initialSidebarHidden = getItem('sidebarHidden', false);
const initialMobileSidebar = getItem('mobileSidebar', false);
const initialCWidth = getItem('cWidth', 'fluid');

function applyTheme(theme) {
  document.documentElement.classList.toggle('dark', theme === 'dark');
}

function applyMenuLayout(layout) {
  const cl = document.documentElement.classList;
  cl.remove('menu-vertical', 'menu-horizontal');
  cl.add(`menu-${layout}`);
}

function applySidebar(hidden) {
  document.documentElement.classList.toggle('sidebar-hidden', hidden);
}

function applyWidth(width) {
  const cl = document.documentElement.classList;
  cl.remove('c-boxed', 'c-fluid');
  cl.add(width === 'boxed' ? 'c-boxed' : 'c-fluid');
}

// apply initial classes
applyTheme(initialTheme);
applyMenuLayout(initialMenuLayout);
applySidebar(initialSidebarHidden);
applyWidth(initialCWidth);

export const useUiStore = defineStore('ui', {
  state: () => ({
    theme: initialTheme,
    menuLayout: initialMenuLayout,
    sidebarHidden: initialSidebarHidden,
    mobileSidebar: initialMobileSidebar,
    cWidth: initialCWidth,
  }),
  actions: {
    toggleTheme() {
      this.theme = this.theme === 'dark' ? 'light' : 'dark';
      applyTheme(this.theme);
      setItem('theme', this.theme);
    },
    setMenuLayout(layout) {
      this.menuLayout = layout;
      applyMenuLayout(layout);
      setItem('menuLayout', layout);
    },
    openSidebar() {
      this.sidebarHidden = false;
      applySidebar(false);
      setItem('sidebarHidden', false);
    },
    closeSidebar() {
      this.sidebarHidden = true;
      applySidebar(true);
      setItem('sidebarHidden', true);
    },
    toggleSidebar() {
      this.sidebarHidden = !this.sidebarHidden;
      applySidebar(this.sidebarHidden);
      setItem('sidebarHidden', this.sidebarHidden);
    },
    openMobileSidebar() {
      this.mobileSidebar = true;
      setItem('mobileSidebar', true);
    },
    closeMobileSidebar() {
      this.mobileSidebar = false;
      setItem('mobileSidebar', false);
    },
    toggleMobileSidebar() {
      this.mobileSidebar = !this.mobileSidebar;
      setItem('mobileSidebar', this.mobileSidebar);
    },
    setContainerWidth(width) {
      this.cWidth = width;
      applyWidth(width);
      setItem('cWidth', width);
    },
  },
});

