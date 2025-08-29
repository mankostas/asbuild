<template>
  <router-link :to="{ name: 'dashboard' }">
    <img
      v-if="!themeSettingsStore.isDark"
      :src="branding.branding.logo || logoLight"
      :alt="alt || ''"
    />
    <img
      v-else
      :src="branding.branding.logo_dark || logoDark"
      :alt="alt || ''"
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
const props = defineProps({
  alt: {
    type: String,
    default: '',
  },
});
const { alt } = props;
</script>
