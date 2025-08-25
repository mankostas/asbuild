<template>
  <Panel v-if="queue.length" header="Upload Queue" class="fixed bottom-4 right-4 w-80">
    <div v-for="item in queue" :key="item.id" class="flex items-center gap-2 mb-2">
      <div class="flex-1">
        <div class="font-medium">{{ item.id }}</div>
        <ProgressBar mode="indeterminate" class="h-2 mt-1" />
      </div>
      <Button label="Retry" text @click="retry(item.id)" />
    </div>
  </Panel>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useDraftsStore } from '@/stores/drafts';
import Panel from 'primevue/panel';
import ProgressBar from 'primevue/progressbar';
import Button from 'primevue/button';

const drafts = useDraftsStore();
const { queue } = storeToRefs(drafts);

function retry(id: string) {
  drafts.retry(id);
}
</script>
