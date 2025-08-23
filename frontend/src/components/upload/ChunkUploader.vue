<template>
  <div>
    <input type="file" @change="onSelect" />
    <div v-if="progress">{{ progress }}%</div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { uploadFile } from '@/services/uploader';

const progress = ref(0);

function onSelect(e: Event) {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;
  uploadFile(file, { onProgress: (p: number) => (progress.value = p) }).catch(() => {});
}
</script>
