<template>
  <Toast />
  <component :is="layoutComponent" />
</template>

<script setup lang="ts">
import { computed, defineComponent, h } from 'vue';
import { useRoute, RouterView } from 'vue-router';
import AppLayout from './Layout/AppLayout.vue';
import Toast from '@/components/ui/Toast.vue';

const route = useRoute();

const DefaultLayout = defineComponent({
  name: 'DefaultLayout',
  setup() {
    return () => h(RouterView);
  },
});

const layouts: Record<string, any> = {
  app: AppLayout,
  default: DefaultLayout,
};

const layoutComponent = computed(() => layouts[(route.meta.layout as string) || 'app'] || DefaultLayout);
</script>
