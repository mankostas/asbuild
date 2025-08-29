<template>
  <router-link :to="{ name: 'dashboard' }">
    <img
      :src="branding.branding.logo || logoLight"
      alt=""
      class="h-8"
      v-if="!themeSettingsStore.isDark"
    />
    <img
      :src="branding.branding.logo_dark || logoDark"
      alt=""
      class="h-8"
      v-else
    />
  </router-link>
</template>
<script setup lang="ts">
import { useThemeSettingsStore } from '@/store/themeSettings';
import { useBrandingStore } from '@/stores/branding';
import logoLight from '@/assets/images/logo/logo-c.svg';
import logoDark from '@/assets/images/logo/logo-c-white.svg';

const themeSettingsStore = useThemeSettingsStore();
const branding = useBrandingStore();
</script>
