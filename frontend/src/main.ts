import "animate.css";
import "flatpickr/dist/flatpickr.css";
import "sweetalert2/dist/sweetalert2.min.css";
import { createApp, watch } from "vue";
import "simplebar-vue/dist/simplebar.min.css";
import VueGoodTablePlugin from "vue-good-table-next";
import "vue-good-table-next/dist/vue-good-table-next.css";
import VueSweetalert2 from "vue-sweetalert2";
import { notifyPlugin } from "./plugins/notify";
import "vue-toastification/dist/index.css";
import VueApexCharts from "vue3-apexcharts";
import VueClickAway from "vue3-click-away";
import PerfectScrollbar from "vue3-perfect-scrollbar";
import "vue3-perfect-scrollbar/dist/vue3-perfect-scrollbar.css";
import VueFlatPickr from "vue-flatpickr-component";
import App from "./App.vue";
import "./assets/scss/auth.scss";
import "./assets/scss/tailwind.scss";
import router from "./router";
import stores from "./stores";
import i18n from "./i18n";
import VCalendar from "v-calendar";
import "v-calendar/dist/style.css";
import { VueQueryPlugin } from "@tanstack/vue-query";
import { useThemeSettingsStore } from "./stores/themeSettings";
import { useBrandingStore } from "./stores/branding";
import VueDOMPurifyHTML from "vue-dompurify-html";

const app = createApp(App)
  .use(stores)
  .use(i18n)
  .use(router)
  .use(VueSweetalert2)
  .use(notifyPlugin, {
    toastClassName: "dashcode-toast",
    bodyClassName: "dashcode-toast-body",
  })
  .use(VueClickAway)
  .use(VueFlatPickr)
  .use(VueGoodTablePlugin)
  .use(VueApexCharts)
  .use(PerfectScrollbar)
  .use(VCalendar)
  .use(VueDOMPurifyHTML);

app.use(VueQueryPlugin);

async function bootstrap() {
  app.config.globalProperties.$store = {};
  const themeSettingsStore = useThemeSettingsStore();
  await themeSettingsStore.load();
  const brandingStore = useBrandingStore();
  await brandingStore.load();
  app.config.globalProperties.$store.themeSettingsStore = themeSettingsStore;
  app.config.globalProperties.$store.brandingStore = brandingStore;

  // Apply any saved theme customizer settings on startup and persist future
  // changes so user preferences survive page reloads and new sessions.
  document.documentElement.setAttribute("dir", "ltr");
  document.body.classList.remove(
    themeSettingsStore.theme === "dark" ? "light" : "dark"
  );
  document.body.classList.add(themeSettingsStore.theme);
  document.body.classList.toggle("semi-dark", themeSettingsStore.semidark);

  themeSettingsStore.$subscribe(() => {
    themeSettingsStore.persistLocal();
  });

  router.isReady().then(() => {
    watch(
      () => themeSettingsStore.isOpenSettings,
      (open) => {
        if (!open) {
          themeSettingsStore.persistRemote();
        }
      }
    );

    app.mount("#app");
  });
}

bootstrap();

if (import.meta.env.VITE_SENTRY_DSN) {
  const script = document.createElement('script');
  script.src = 'https://browser.sentry-cdn.com/7.120.0/bundle.min.js';
  script.crossOrigin = 'anonymous';
  script.onload = () => {
    // @ts-ignore
    Sentry.init({ dsn: import.meta.env.VITE_SENTRY_DSN });
  };
  document.head.appendChild(script);
}

if ('serviceWorker' in navigator && import.meta.env.PROD) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js');
  });
}
