<template>
  <div
    class="mobile-sidebar bg-white dark:bg-slate-800 shadow-base"
  >
    <div class="logo-segment flex justify-between items-center px-4 py-6">
        <router-link :to="{ name: 'dashboard' }">
        <img
          :src="$store.brandingStore.branding.logo || logoLight"
          alt=""
          v-if="!this.$store.themeSettingsStore.isDark"
        />

        <img
          :src="$store.brandingStore.branding.logo_dark || logoDark"
          alt=""
          v-if="this.$store.themeSettingsStore.isDark"
        />
      </router-link>
      <span
        class="cursor-pointer text-slate-900 dark:text-white text-2xl"
        @click="toggleMsidebar"
        ><Icon icon="heroicons:x-mark"
      /></span>
    </div>

    <perfect-scrollbar class="sidebar-menu px-4 h-[calc(100%-100px)]">
      <Navmenu :items="menuItems" />
    </perfect-scrollbar>
  </div>
</template>
<script>
import { Icon } from "@iconify/vue";
import { defineComponent } from "vue";
import { menuItems } from "@/constant/data";
import Navmenu from "./Navmenu";
import logoLight from "@/assets/images/logo/logo.svg";
import logoDark from "@/assets/images/logo/logo-white.svg";

export default defineComponent({
  components: {
    Icon,
    Navmenu,
  },
  data() {
    return {
      menuItems,
      openClass: "w-[248px]",
      closeClass: "w-[72px] close_sidebar",
      logoLight,
      logoDark,
    };
  },
  methods: {
    toggleMsidebar() {
      this.$store.themeSettingsStore.toggleMsidebar();
    },
  },
});
</script>
<style lang="scss" scoped>
.mobile-sidebar {
  @apply fixed ltr:left-0 rtl:right-0 top-0   h-full   z-[9999]  w-[280px];
}
</style>
