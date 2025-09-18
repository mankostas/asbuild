<template>
  <div class="relative">
    <ClientsList />
    <RouterView #default="{ Component, route }">
      <component :is="Component" v-if="Component && route?.meta?.modal" />
    </RouterView>
  </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router';
import ClientsList from './ClientsList.vue';
</script>
