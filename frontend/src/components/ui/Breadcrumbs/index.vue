<template>
  <div
    v-if="route.meta.breadcrumb"
    class="flex space-x-3 rtl:space-x-reverse"
  >
    <h4
      v-if="!route.meta.groupParent"
      :class="route.meta.groupParent ? 'lg:border-r lg:border-secondary-500' : ''"
      class="font-medium lg:text-2xl text-xl capitalize text-slate-900 inline-block ltr:pr-4 rtl:pl-4"
    >
      {{ t(route.meta.breadcrumb as string) }}
    </h4>
    <ul v-else class="breadcrumbs">
      <li class="text-primary-500">
        <RouterLink :to="{ name: 'dashboard' }" class="text-lg">
          <Icon icon="heroicons-outline:home" />
        </RouterLink>
        <span class="breadcrumbs-icon rtl:transform rtl:rotate-180">
          <Icon icon="heroicons:chevron-right" />
        </span>
      </li>
      <li v-if="parentRoute" class="text-primary-500">
        <RouterLink :to="{ name: parentRoute.name }" class="capitalize">
          {{ t(parentRoute.meta.breadcrumb as string) }}
        </RouterLink>
        <span class="breadcrumbs-icon rtl:transform rtl:rotate-180">
          <Icon icon="heroicons:chevron-right" />
        </span>
      </li>
      <li class="capitalize text-slate-500 dark:text-slate-400">
        {{ t(route.meta.breadcrumb as string) }}
      </li>
    </ul>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import Icon from '@/components/Icon';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const parentRoute = computed(() => {
  if (!route.meta.groupParent) return undefined;
  return router.getRoutes().find((r) => r.name === route.meta.groupParent);
});
</script>
<style lang="scss">
.breadcrumbs {
  @apply flex text-sm space-x-2 items-center;
  li {
    @apply relative flex items-center space-x-2 capitalize font-normal rtl:space-x-reverse;
    .breadcrumbs-icon {
      @apply text-lg text-secondary-500 dark:text-slate-500;
    }
  }
}
</style>
