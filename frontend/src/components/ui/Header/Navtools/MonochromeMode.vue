<template>
  <span
    @click="toggleMonochrome"
    class="lg:h-[32px] lg:w-[32px] lg:bg-slate-100 lg:dark:bg-slate-900 text-slate-900 dark:text-white cursor-pointer rounded-full text-[20px] flex flex-col items-center justify-center"
  >
    <Icon icon="mdi:palette-outline" class="font-bold" />
  </span>
</template>
<script setup>
import Icon from "@/components/Icon";
import { useThemeSettingsStore } from "@/store/themeSettings";

const themeSettingsStore = useThemeSettingsStore();

function toggleMonochrome() {
  themeSettingsStore.toggleMonochrome();
}
</script>
<style lang=""></style>
