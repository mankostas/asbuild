<template>
  <div class="col-span-2">
    <span class="block font-medium mb-1">{{ photo.label }}</span>
    <input type="file" multiple :aria-label="photo.label" @change="onChange" />
    <ul class="mt-2 list-disc list-inside">
      <li v-for="(p, idx) in modelValue || []" :key="idx">{{ p.filename || p }}</li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { uploadFile } from '@/services/uploader';
const props = defineProps<{ photo: any; sectionKey: string; modelValue: any[] }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: any[]): void }>();

async function onChange(e: Event) {
  const files = Array.from((e.target as HTMLInputElement).files || []);
  const uploaded: any[] = [];
  for (const file of files) {
    uploaded.push(await uploadFile(file, { fieldKey: props.photo.key, sectionKey: props.sectionKey }));
  }
  const arr = [...(props.modelValue || []), ...uploaded];
  emit('update:modelValue', arr);
}
</script>
