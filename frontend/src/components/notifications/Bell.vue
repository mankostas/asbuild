<template>
  <Button class="p-overlay-badge" text to="/notifications">
    <template #icon>
      <Icon icon="heroicons-outline:bell" class="w-5 h-5" />
    </template>
    <Badge v-if="unreadCount" :value="unreadCount" />
  </Button>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount, watch } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import { useNotificationStore } from '@/stores/notifications';
import Icon from '@/components/ui/Icon';

const store = useNotificationStore();
const { unreadCount } = storeToRefs(store);
const route = useRoute();

async function refresh() {
  await store.fetchUnreadCount();
}

let interval: any;

onMounted(() => {
  refresh();
  interval = setInterval(refresh, 60000);
});

onBeforeUnmount(() => {
  clearInterval(interval);
});

watch(
  () => route.fullPath,
  () => refresh(),
);
</script>
