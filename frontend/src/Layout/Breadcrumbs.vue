<template>
  <nav v-if="!hidden" class="text-sm text-gray-500 dark:text-gray-400 mb-4">
    <ol class="flex items-center gap-2">
      <li v-for="(item, index) in items" :key="item.to" class="flex items-center">
        <RouterLink
          v-if="index < items.length - 1"
          :to="item.to"
          class="hover:underline"
        >
          {{ t(item.label) }}
        </RouterLink>
        <span v-else class="text-gray-700 dark:text-gray-200">{{ t(item.label) }}</span>
        <span v-if="index < items.length - 1">/</span>
      </li>
    </ol>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

function resolveParent(key: string) {
  if (!key) return undefined;
  const routes = router.getRoutes();
  return routes.find((r) => r.path === key || r.path === `/${key}`);
}

const items = computed(() => {
  const crumbs: { label: string; to: string }[] = [];
  const groupParent = route.meta.groupParent as string | undefined;
  if (groupParent) {
    const parentRoute = resolveParent(groupParent);
    if (parentRoute && parentRoute.meta && parentRoute.meta.breadcrumb && !parentRoute.meta.hide) {
      crumbs.push({ label: parentRoute.meta.breadcrumb as string, to: parentRoute.path });
    }
  }
  route.matched.forEach((r) => {
    if (r.meta && r.meta.breadcrumb && !r.meta.hide) {
      if (!crumbs.some((c) => c.to === r.path)) {
        crumbs.push({ label: r.meta.breadcrumb as string, to: r.path });
      }
    }
  });
  return crumbs;
});

const hidden = computed(() => route.meta.hide);
</script>
