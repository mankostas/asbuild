<template>
  <button
    type="button"
    class="h-[28px] w-[28px] lg:h-[32px] lg:w-[32px] lg:bg-slate-100 lg:dark:bg-slate-900 dark:text-white text-slate-900 cursor-pointer rounded-full text-[20px] flex flex-col items-center justify-center"
    @click="toogleDark"
  >
    <Icon v-if="!themeSettingsStore.isDark" icon="heroicons-outline:moon" />
    <Icon v-else icon="heroicons-outline:sun" />
  </button>
</template>
<script setup>
import Icon from "@/components/Icon";
import { useThemeSettingsStore } from "@/store/themeSettings";

const themeSettingsStore = useThemeSettingsStore();

async function toogleDark() {
  themeSettingsStore.toogleDark();
  await themeSettingsStore.persistRemote();
}
</script>
<style lang=""></style>
