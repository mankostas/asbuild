import { defineStore } from "pinia";

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
    return saved ? { ...defaultState, ...JSON.parse(saved) } : { ...defaultState };
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
      const isMonochrome = localStorage.getItem("monochrome") !== null;
      // this.monochrome = !this.monochrome;
      if (isMonochrome) {
        localStorage.removeItem("monochrome");
        document.getElementsByTagName("html")[0].classList.remove("grayscale");
        return;
      }
      localStorage.setItem("monochrome", true);
      document.getElementsByTagName("html")[0].classList.add("grayscale");
      return;
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
