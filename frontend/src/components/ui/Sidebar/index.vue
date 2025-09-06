<template>
    <div :class="$store.themeSettingsStore.semidark ? 'dark' : ''">
      <div
        :class="`sidebar-wrapper bg-white dark:bg-slate-800 shadow-base ${
          $store.themeSettingsStore.sidebarCollasp
            ? closeClass
            : openClass
        }
        ${$store.themeSettingsStore.isMouseHovered ? 'sidebar-hovered' : ''}

        `"
        tabindex="0"
        role="complementary"
      >
      <div
        :class="`logo-segment flex justify-between items-center bg-white dark:bg-slate-800 z-[9] py-6  sticky top-0   px-4  ${
          $store.themeSettingsStore.sidebarCollasp
            ? closeClass
            : openClass
        }
        ${$store.themeSettingsStore.isMouseHovered ? 'logo-hovered' : ''}

        `"
      >
        <router-link
          v-if="
            !$store.themeSettingsStore.sidebarCollasp ||
            $store.themeSettingsStore.isMouseHovered
          "
          :to="{ name: 'dashboard' }"
        >
          <img
            v-if="
              !$store.themeSettingsStore.isDark &&
              !$store.themeSettingsStore.semidark
            "
            :src="$store.brandingStore.branding.logo || logoLight"
            alt=""
          />

          <img
            v-if="
              $store.themeSettingsStore.isDark ||
              $store.themeSettingsStore.semidark
            "
            :src="$store.brandingStore.branding.logo_dark || logoDark"
            alt=""
          />
        </router-link>
        <router-link
          v-if="
            $store.themeSettingsStore.sidebarCollasp &&
            !$store.themeSettingsStore.isMouseHovered
          "
          :to="{ name: 'dashboard' }"
        >
          <img
            v-if="
              !$store.themeSettingsStore.isDark &&
              !$store.themeSettingsStore.semidark
            "
            src="@/assets/images/logo/logo-c.svg"
            alt=""
          />
          <img
            v-if="
              $store.themeSettingsStore.isDark ||
              $store.themeSettingsStore.semidark
            "
            src="@/assets/images/logo/logo-c-white.svg"
            alt=""
          />
        </router-link>
        <button
          v-if="
            !$store.themeSettingsStore.sidebarCollasp ||
            $store.themeSettingsStore.isMouseHovered
          "
          type="button"
          class="cursor-pointer text-slate-900 dark:text-white text-2xl"
          @click="
            $store.themeSettingsStore.sidebarCollasp =
              !$store.themeSettingsStore.sidebarCollasp
          "
        >
          <!-- <Icon icon="heroicons-outline:menu-alt-3"
        /> -->
          <div
            class="h-4 w-4 border-[1.5px] border-slate-900 dark:border-slate-700 rounded-full transition-all duration-150"
            :class="
              $store.themeSettingsStore.sidebarCollasp
                ? ''
                : 'ring-2 ring-inset ring-offset-4 ring-black-900 dark:ring-slate-400 bg-slate-900 dark:bg-slate-400 dark:ring-offset-slate-700'
            "
          ></div>
        </button>
      </div>
      <div
        class="h-[60px] absolute top-[80px] nav-shadow z-[1] w-full transition-all duration-200 pointer-events-none"
        :class="[shallShadowBottom ? ' opacity-100' : ' opacity-0']"
      ></div>

      <perfect-scrollbar
        ref="shadowbase"
        class="sidebar-menu px-4 h-[calc(100%-80px)]"
        @ps-scroll-y="
          (evt) => {
            shallShadowBottom = evt.srcElement.scrollTop > 0;
          }
        "
      >
        <Navmenu :items="menuItems" />
      </perfect-scrollbar>
    </div>
  </div>
</template>
<script>
// import { Icon } from "@iconify/vue";
import { defineComponent } from "vue";
import { menuItems } from "@/constant/data";
import Navmenu from "./Navmenu";
import { gsap } from "gsap";
import { ref, onMounted } from "vue";
import logoLight from "@/assets/images/logo/logo.svg";
import logoDark from "@/assets/images/logo/logo-white.svg";

export default defineComponent({
  components: {
    // Icon,
    Navmenu,
  },

  setup() {
    // Shadow bottom is UI specific and can be removed by user => It's not in `useVerticalNavMenu`
    const shallShadowBottom = ref(false);

    const enterWidget = (el) => {
      gsap.fromTo(
        el,
        { x: 0, opacity: 0, scale: 0.5 },
        { x: 0, opacity: 1, duration: 0.3, scale: 1 }
      );
    };
    const leaveWidget = (el) => {
      gsap.fromTo(
        el,
        { x: 0, opacity: 1, scale: 1 },
        { x: 0, opacity: 0, duration: 0.3, scale: 0.5 }
      );
    };

    return {
      enterWidget,
      leaveWidget,
      shallShadowBottom,
    };
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
});
</script>
<style lang="scss">
.sidebar-wrapper {
  @apply fixed start-0 top-0 h-screen z-[999];
  transition: width 0.2s cubic-bezier(0.39, 0.575, 0.565, 1);
  will-change: width;
}

.nav-shadow {
  background: linear-gradient(
    rgb(255, 255, 255) 5%,
    rgba(255, 255, 255, 75%) 45%,
    rgba(255, 255, 255, 20%) 80%,
    transparent
  );
}
.dark {
  .nav-shadow {
    background: linear-gradient(
      rgba(#1e293b, 100%) 5%,
      rgba(#1e293b, 75%) 45%,
      rgba(#1e293b, 20%) 80%,
      transparent
    );
  }
}
.sidebar-wrapper.sidebar-hovered {
  width: 248px !important;
}
.logo-segment.logo-hovered {
  width: 248px !important;
}
</style>
