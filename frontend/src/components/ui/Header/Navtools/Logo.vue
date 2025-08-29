<template>
  <router-link :to="{ name: 'dashboard' }">
    <img
      :src="branding.branding.logo || logoLight"
      alt=""
      v-if="!themeSettingsStore.isDark"
    />
    <img
      :src="branding.branding.logo_dark || logoDark"
      alt=""
      v-else
    />
  </router-link>
</template>
<script setup lang="ts">
import { useThemeSettingsStore } from '@/store/themeSettings';
import { useBrandingStore } from '@/stores/branding';
import logoLight from '@/assets/images/logo/logo.svg';
import logoDark from '@/assets/images/logo/logo-white.svg';

const themeSettingsStore = useThemeSettingsStore();
const branding = useBrandingStore();
</script>
