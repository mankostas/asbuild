<template>
  <Settings v-if="hasFeature('themes') && can('themes.manage')" />
</template>
<script setup>
import Settings from '@/components/ui/Settings/index.vue';
import { can, hasFeature } from '@/stores/auth';
</script>
