import { defineStore } from "pinia";

// Default state for the theme customizer.  When the store initializes we merge
// any persisted settings from localStorage so that user choices persist across
// refreshes and future logins.
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
  state: () => {
    const saved = localStorage.getItem("themeSettings");
    const parsed = saved ? JSON.parse(saved) : {};
    const monochrome =
      parsed.monochrome ?? localStorage.getItem("monochrome") !== null;
    return { ...defaultState, ...parsed, monochrome };
  },
  actions: {
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
