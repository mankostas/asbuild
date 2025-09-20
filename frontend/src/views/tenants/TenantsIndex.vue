<template>
  <div class="relative">
    <TenantsList />
    <RouterView #default="{ Component, route }">
      <component
        :is="Component"
        v-if="Component && route?.meta?.modal"
        @close="handleModalClose(route)"
      />
    </RouterView>
  </div>
</template>

<script setup lang="ts">
import { RouterView, useRouter, type RouteLocationNormalizedLoaded } from 'vue-router';
import TenantsList from './TenantsList.vue';

const router = useRouter();

function handleModalClose(route: RouteLocationNormalizedLoaded) {
  const fallbackRouteName = 'tenants.list';
  const targetRouteName = typeof route.meta?.groupParent === 'string'
    ? route.meta.groupParent
    : fallbackRouteName;

  router.push({ name: targetRouteName });
}
</script>
