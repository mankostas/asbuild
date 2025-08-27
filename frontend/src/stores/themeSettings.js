import { defineStore } from "pinia";
import api from "@/services/api";
import { useAuthStore } from "./auth";

// Default state for the theme customizer.  We merge any persisted values from
// localStorage with these defaults when the store is first created so that user
// preferences survive refreshes and future logins.
const defaultState = {
  sidebarCollaspe: false,
  sidebarHidden: false,
  mobielSidebar: false,
  semidark: false,
  monochrome: false,
  semiDarkTheme: "semi-light",
  isDark: false,
  skin: "default",
  theme: "light",
  isOpenSettings: false,
  cWidth: "full",
  menuLayout: "vertical",
  navbarType: "sticky",
  isMouseHovered: false,
  footerType: "static",
  direction: false,
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
    const monochrome =
      parsed.monochrome ?? localStorage.getItem("monochrome") !== null;
    return { ...defaultState, ...parsed, monochrome };
  },
  actions: {
    async load() {
      const auth = useAuthStore();
      if (!auth.isAuthenticated) return;
      try {
        const { data } = await api.get("/settings/theme");
        Object.assign(this.$state, data);
      } catch (e) {}
    },

    persist() {
      localStorage.setItem("themeSettings", JSON.stringify(this.$state));
      const auth = useAuthStore();
      if (!auth.isAuthenticated) return;
      api.put("/settings/theme", this.$state).catch(() => {});
    },

    setSidebarCollasp() {
      this.sidebarCollasp = !this.sidebarCollasp;
    },

    toogleDark() {
      this.isDark = !this.isDark;
      document.body.classList.remove(this.theme);
      this.theme = this.theme === "dark" ? "light" : "dark";
      document.body.classList.add(this.theme);
      localStorage.setItem("theme", this.theme);
    },

    toggleMonochrome() {
      this.monochrome = !this.monochrome;
      document.documentElement.classList.toggle("grayscale", this.monochrome);
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
      localStorage.setItem("semiDark", this.semidark);
    },
    toggleCartDrawer() {
      this.cartOpener = !this.cartOpener;
    },
  },
});
