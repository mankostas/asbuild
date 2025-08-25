<template>
  <div>
    <Button label="Add Photos" @click="onSelect" />
  </div>
</template>

<script setup lang="ts">
import { pickFiles } from '@/services/native';
import Button from 'primevue/button';

const emit = defineEmits(['update']);

async function onSelect() {
  const files = await pickFiles({ multiple: true, accept: 'image/*', capture: 'environment' });
  if (!files.length) return;
  const result: File[] = [];
  for (const file of files) {
    const compressed = await compress(file);
    result.push(compressed);
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
