<template>
  <div class="col-span-2">
    <span class="block font-medium mb-1">{{ photo.label }}</span>
    <input
      type="file"
      multiple
      :aria-label="photo.label"
      @change="onChange"
    />
    <ul class="mt-2 grid grid-cols-3 gap-2">
      <li v-for="(p, idx) in items" :key="idx" class="relative">
        <img :src="p.preview" class="w-32 h-32 object-cover" alt="" />
        <button
          type="button"
          class="absolute top-0 right-0 bg-red-600 text-white px-1"
          :aria-label="t('actions.delete')"
          @click="remove(idx)"
          @keyup.enter.prevent="remove(idx)"
          @keyup.space.prevent="remove(idx)"
        >
          ×
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { uploadFile } from '@/services/uploader';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ photo: any; sectionKey: string; taskId: number; modelValue: any[] }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: any[]): void }>();
const { t } = useI18n();

const items = ref<any[]>([]);

watch(
  () => props.modelValue,
  (val) => {
    items.value = (val || []).map((v: any) => ({ ...v }));
  },
  { immediate: true }
);

async function onChange(e: Event) {
  const files = Array.from((e.target as HTMLInputElement).files || []);
  for (const file of files) {
    const uploaded = await uploadFile(file, {
      taskId: props.taskId,
      fieldKey: props.photo.key,
      sectionKey: props.sectionKey,
    });
    uploaded.preview = URL.createObjectURL(file);
    items.value.push(uploaded);
  }
  emit('update:modelValue', [...items.value]);
}

function remove(idx: number) {
  items.value.splice(idx, 1);
  emit('update:modelValue', [...items.value]);
}
</script>
