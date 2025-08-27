<template>
  <main class="app-wrapper">
    <Header :class="window.width > 1280 ? switchHeaderClass() : ''" />
    <!-- end header -->

    <Sidebar
      v-if="
        this.$store.themeSettingsStore.menuLayout === 'vertical' &&
        this.$store.themeSettingsStore.sidebarHidden === false &&
        window.width > 1280
      "
    />
    <!-- main sidebar end -->
    <Transition name="mobilemenu">
      <mobile-sidebar
        v-if="window.width < 1280 && this.$store.themeSettingsStore.mobielSidebar"
      />
    </Transition>
    <Transition name="overlay-fade">
      <div
        v-if="window.width < 1280 && this.$store.themeSettingsStore.mobielSidebar"
        class="overlay bg-slate-900 bg-opacity-70 backdrop-filter backdrop-blur-[3px] backdrop-brightness-10 fixed inset-0 z-[999]"
        @click="this.$store.themeSettingsStore.mobielSidebar = false"
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
        :class="this.$route.meta.appheight ? 'h-full' : 'page-min-height'"
      >
        <div
          :class="` transition-all duration-150 ${
            this.$store.themeSettingsStore.cWidth === 'boxed'
              ? 'container mx-auto'
              : 'container-fluid'
          }`"
        >
          <Breadcrumbs v-if="!this.$route.meta.hide" />
          <router-view v-slot="{ Component }">
            <transition name="router-animation" mode="in-out" appear>
              <component :is="Component"></component>
            </transition>
          </router-view>
        </div>
      </div>
    </div>
    <!-- end page content -->
    <FooterMenu v-if="window.width < 768" />
    <Footer
      :class="window.width > 1280 ? switchHeaderClass() : ''"
      v-if="window.width > 768"
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
  mixins: [window],
  components: {
    Header,
    Footer,
    Sidebar,
    Settings,
    Breadcrumbs,
    FooterMenu,
    MobileSidebar,
  },
  methods: {
    switchHeaderClass() {
      if (
        this.$store.themeSettingsStore.menuLayout === "horizontal" ||
        this.$store.themeSettingsStore.sidebarHidden
      ) {
        return "ltr:ml-0 rtl:mr-0";
      } else if (this.$store.themeSettingsStore.sidebarCollasp) {
        return "ltr:ml-[72px] rtl:mr-[72px]";
      } else {
        return "ltr:ml-[248px] rtl:mr-[248px]";
      }
    },
  },
};
</script>
<style lang="scss">
.router-animation-enter-active,
.router-animation-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.router-animation-enter-from,
.router-animation-leave-to {
  opacity: 0;
  transform: translate3d(0, 4%, 0) scale(0.93);
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
