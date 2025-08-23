<template>
  <div>
    <button @click="onSelect" class="bg-blue-600 text-white px-2 py-1">Choose File</button>
    <div v-if="progress">{{ progress }}%</div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { uploadFile } from '@/services/uploader';
import { pickFiles } from '@/services/native';

const progress = ref(0);

async function onSelect() {
  const files = await pickFiles();
  const file = files[0];
  if (!file) return;
  uploadFile(file, { onProgress: (p: number) => (progress.value = p) }).catch(() => {});
}
</script>
