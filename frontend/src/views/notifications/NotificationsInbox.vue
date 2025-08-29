<template>
  <div class="max-w-2xl mx-auto space-y-2">
    <div
      v-for="n in notifications"
      :key="n.id"
      class="border-b py-2 flex justify-between items-center"
    >
      <div>
        <a v-if="n.link" :href="n.link" class="text-blue-600">{{ $msg(n.message) }}</a>
        <span v-else>{{ $msg(n.message) }}</span>
      </div>
      <button
        v-if="!n.read_at"
        class="text-sm text-blue-500"
        @click="markRead(n.id)"
      >
        Mark read
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useNotificationStore } from '@/stores/notifications';

const store = useNotificationStore();
const { notifications } = storeToRefs(store);

function markRead(id: number) {
  store.markRead(id);
}

onMounted(() => {
  store.fetchAll();
});
</script>
