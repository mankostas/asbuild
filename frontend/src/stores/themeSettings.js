import { defineStore } from "pinia";
import api from "@/services/api";
import { useAuthStore } from "./auth";

const safeSetItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
  } catch (e) {}
};

// Default state for the theme customizer.  We merge any persisted values from
// localStorage with these defaults when the store is first created so that user
// preferences survive refreshes and future logins.
const defaultState = {
  // Whether the sidebar is collapsed.  Previous versions misspelled this key
  // as `sidebarCollaspe`, so we migrate that value below when initializing
  // the store to preserve user preferences.
  sidebarCollasp: false,
  sidebarHidden: false,
  mobielSidebar: false,
  semidark: false,
  semiDarkTheme: "semi-light",
  isDark: false,
  theme: "light",
  isOpenSettings: false,
  cWidth: "full",
  menuLayout: "vertical",
  navbarType: "sticky",
  isMouseHovered: false,
  footerType: "static",
  cartOpener: false,
  chartColors: {
    title: "red",
  },
};

export const useThemeSettingsStore = defineStore("themeSettings", {
  // Load any previously saved settings from localStorage.  If none are found we
  // fall back to the defaults above.
  state: () => {
    const saved = localStorage.getItem("themeSettings");
    const parsed = saved ? JSON.parse(saved) : {};
    const {
      skin,
      monochrome,
      direction,
      sidebarCollaspe, // legacy key
      ...clean
    } = parsed;
    const state = {
      ...defaultState,
      ...clean,
      sidebarCollasp:
        clean.sidebarCollasp ?? sidebarCollaspe ?? defaultState.sidebarCollasp,
    };
    // Persist the merged state so that migrations or new defaults overwrite
    // any previously saved values in localStorage.
    safeSetItem("themeSettings", JSON.stringify(state));
    return state;
  },
  actions: {
    async load() {
      const auth = useAuthStore();
      if (!auth.isAuthenticated) return;
      try {
        const { data } = await api.get("/settings/theme");
        Object.assign(this.$state, data);
        this._serverSnapshot = JSON.stringify(this.$state);
        // Ensure localStorage reflects the latest server-provided settings.
        this.persistLocal();
      } catch (e) {}
    },

    persistLocal() {
      safeSetItem("themeSettings", JSON.stringify(this.$state));
    },

    async persistRemote() {
      const auth = useAuthStore();
      if (!auth.isAuthenticated) return;
      try {
        const snapshot = JSON.stringify(this.$state);
        if (this._serverSnapshot === snapshot) return;
        await api.put("/settings/theme", this.$state);
        this._serverSnapshot = snapshot;
      } catch (e) {}
    },

    setSidebarCollasp() {
      this.sidebarCollasp = !this.sidebarCollasp;
    },

    toogleDark() {
      this.isDark = !this.isDark;
      document.body.classList.remove(this.theme);
      this.theme = this.theme === "dark" ? "light" : "dark";
      document.body.classList.add(this.theme);
      safeSetItem("theme", this.theme);
    },

    toggleSettings() {
      this.isOpenSettings = !this.isOpenSettings;
    },
    toggleMsidebar() {
      this.mobielSidebar = !this.mobielSidebar;
    },
    toggleSemiDark() {
      this.semidark = !this.semidark;
      this.semiDarkTheme = this.semidark ? "semi-dark" : "semi-light";
      document.body.classList.toggle(this.semiDarkTheme);
      safeSetItem("semiDark", this.semidark);
    },
    toggleCartDrawer() {
      this.cartOpener = !this.cartOpener;
    },
  },
});
