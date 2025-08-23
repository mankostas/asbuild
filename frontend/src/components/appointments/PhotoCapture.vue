<template>
  <div>
    <button @click="onSelect" class="bg-blue-600 text-white px-2 py-1">Add Photos</button>
    <div v-if="previews.length" class="flex gap-2 mt-2">
      <img
        v-for="(src, idx) in previews"
        :key="idx"
        :src="src"
        class="w-20 h-20 object-cover border"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { pickFiles } from '@/services/native';

const emit = defineEmits(['update']);
const previews = ref<string[]>([]);

async function onSelect() {
  const files = await pickFiles({ multiple: true, accept: 'image/*', capture: 'environment' });
  if (!files.length) return;
  const result: File[] = [];
  previews.value = [];
  for (const file of files) {
    const compressed = await compress(file);
    result.push(compressed);
    previews.value.push(URL.createObjectURL(compressed));
  }
  emit('update', result);
}

function compress(file: File): Promise<File> {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d')!;
      const scale = 800 / Math.max(img.width, img.height);
      canvas.width = img.width * scale;
      canvas.height = img.height * scale;
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      canvas.toBlob(
        (blob) => {
          if (blob) {
            resolve(new File([blob], file.name, { type: 'image/jpeg' }));
          } else {
            resolve(file);
          }
        },
        'image/jpeg',
        0.8
      );
    };
    img.src = URL.createObjectURL(file);
  });
}
</script>
