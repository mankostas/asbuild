<template>
  <div class="col-span-2">
    <span class="block font-medium mb-1">{{ photo.label }}</span>
    <input type="file" :aria-label="photo.label" @change="onChange" />
  </div>
</template>

<script setup lang="ts">
import { uploadFile } from '@/services/uploader';
const props = defineProps<{ photo: any; sectionKey: string; modelValue: any }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: any): void }>();

async function onChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) {
    const uploaded = await uploadFile(file, { fieldKey: props.photo.key, sectionKey: props.sectionKey });
    emit('update:modelValue', uploaded);
  }
}
</script>
