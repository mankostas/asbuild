<template>
  <div
    v-if="queue.length"
    class="fixed bottom-4 right-4 w-64 bg-white shadow-lg p-3 border rounded"
  >
    <h3 class="font-bold mb-2">Upload Queue</h3>
    <div v-for="item in queue" :key="item.id" class="flex justify-between mb-1">
      <span>{{ item.id }}</span>
      <button class="text-blue-600" @click="retry(item.id)">Retry</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useDraftsStore } from '@/stores/drafts';

const drafts = useDraftsStore();
const { queue } = storeToRefs(drafts);

function retry(id: string) {
  drafts.retry(id);
}
</script>
