<template>
  <span
    @click="toogleDark"
    class="h-[28px] w-[28px] lg:h-[32px] lg:w-[32px] lg:bg-slate-100 lg:dark:bg-slate-900 dark:text-white text-slate-900 cursor-pointer rounded-full text-[20px] flex flex-col items-center justify-center"
  >
    <Icon icon="heroicons-outline:moon" v-if="!themeSettingsStore.isDark" />
    <Icon icon="heroicons-outline:sun" v-else />
  </span>
</template>
<script setup>
import Icon from "@/components/Icon";
import { useThemeSettingsStore } from "@/store/themeSettings";

const themeSettingsStore = useThemeSettingsStore();

function toogleDark() {
  themeSettingsStore.toogleDark();
}
</script>
<style lang=""></style>
