<template>
  <div class="relative">
    <TenantsList />
    <RouterView #default="{ Component, route }">
      <component :is="Component" v-if="Component && route?.meta?.modal" />
    </RouterView>
  </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router';
import TenantsList from './TenantsList.vue';
</script>
