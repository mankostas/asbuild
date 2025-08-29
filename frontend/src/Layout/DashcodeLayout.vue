<template>
  <main class="app-wrapper">
    <Header :class="window.width > 1280 ? switchHeaderClass() : ''" />
    <!-- end header -->

    <Sidebar
      v-if="
        $store.themeSettingsStore.menuLayout === 'vertical' &&
        $store.themeSettingsStore.sidebarHidden === false &&
        window.width > 1280
      "
    />
    <!-- main sidebar end -->
    <Transition name="mobilemenu">
      <mobile-sidebar
        v-if="window.width < 1280 && $store.themeSettingsStore.mobielSidebar"
      />
    </Transition>
    <Transition name="overlay-fade">
      <div
        v-if="window.width < 1280 && $store.themeSettingsStore.mobielSidebar"
        class="overlay bg-slate-900 bg-opacity-70 backdrop-filter backdrop-blur-[3px] backdrop-brightness-10 fixed inset-0 z-[999]"
        role="button"
        tabindex="0"
        @click="$store.themeSettingsStore.mobielSidebar = false"
        @keydown.enter="$store.themeSettingsStore.mobielSidebar = false"
      ></div>
    </Transition>
    <!-- mobile sidebar -->
    <Settings />

    <div
      class="content-wrapper transition-all duration-150"
      :class="window.width > 1280 ? switchHeaderClass() : ''"
    >
      <div
        class="page-content"
        :class="$route.meta.appheight ? 'h-full' : 'page-min-height'"
      >
        <div
          :class="` transition-all duration-150 ${
            $store.themeSettingsStore.cWidth === 'boxed'
              ? 'container mx-auto'
              : 'container-fluid'
          }`"
        >
          <Breadcrumbs v-if="!$route.meta.hide" />
          <router-view #default="{ Component }">
            <Transition name="fade" mode="out-in">
              <component :is="Component" />
            </Transition>
          </router-view>
        </div>
      </div>
    </div>
    <!-- end page content -->
    <FooterMenu v-if="window.width < 768" />
    <Footer
      v-if="window.width > 768"
      :class="window.width > 1280 ? switchHeaderClass() : ''"
    />
  </main>
</template>
<script>
import Breadcrumbs from './Breadcrumbs.vue';
import Footer from './Footer.vue';
import Header from './Header.vue';
import Settings from './Settings.vue';
import Sidebar from './Sidebar.vue';
import window from "@/mixins/window";
import MobileSidebar from "@/components/ui/Sidebar/MobileSidebar.vue";
import FooterMenu from "@/components/ui/Footer/FooterMenu.vue";

export default {
  components: {
    Header,
    Footer,
    Sidebar,
    Settings,
    Breadcrumbs,
    FooterMenu,
    MobileSidebar,
  },
  mixins: [window],
  methods: {
    switchHeaderClass() {
      if (
        this.$store.themeSettingsStore.menuLayout === "horizontal" ||
        this.$store.themeSettingsStore.sidebarHidden
      ) {
        return "ml-0";
      } else if (this.$store.themeSettingsStore.sidebarCollasp) {
        return "ml-[72px]";
      } else {
        return "ml-[248px]";
      }
    },
  },
};
</script>
<style lang="scss">
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes slideLeftTransition {
  0% {
    opacity: 0;
    transform: translateX(-20px);
  }
  100% {
    opacity: 1;
    transform: translateX(0px);
  }
}
.mobilemenu-enter-active {
  animation: slideLeftTransition 0.24s;
}

.mobilemenu-leave-active {
  animation: slideLeftTransition 0.24s reverse;
}

.page-content {
  @apply md:pt-6 md:pb-[37px] md:px-6 pt-[15px] px-[15px] pb-24;
}
.page-min-height {
  min-height: calc(var(--vh, 1vh) * 100 - 132px);
}
</style>
