<template>
  <Settings v-if="can('themes.manage')" />
</template>
<script setup>
import Settings from '@/components/ui/Settings/index.vue';
import { can } from '@/stores/auth';
</script>
