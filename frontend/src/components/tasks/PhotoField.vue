<template>
  <div class="col-span-2">
    <span class="block font-medium mb-1">{{ photo.label }}</span>
    <div v-if="preview" class="mb-2 relative inline-block">
      <img :src="preview" class="w-32 h-32 object-cover" alt="" />
      <button
        type="button"
        class="absolute top-0 right-0 bg-red-600 text-white px-1"
        :aria-label="t('actions.delete')"
        @click="remove"
        @keyup.enter.prevent="remove"
        @keyup.space.prevent="remove"
      >
        ×
      </button>
    </div>
    <input
      v-if="!preview"
      type="file"
      :aria-label="photo.label"
      @change="onChange"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { uploadFile } from '@/services/uploader';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ photo: any; sectionKey: string; taskId: string; modelValue: any }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: any): void }>();
const { t } = useI18n();

const preview = ref<string | null>(null);

watch(
  () => props.modelValue,
  (val) => {
    preview.value = val?.preview || null;
  },
  { immediate: true }
);

async function onChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) {
    const uploaded = await uploadFile(file, {
      taskId: props.taskId,
      fieldKey: props.photo.key,
      sectionKey: props.sectionKey,
    });
    uploaded.preview = URL.createObjectURL(file);
    preview.value = uploaded.preview;
    emit('update:modelValue', uploaded);
  }
}

function remove() {
  preview.value = null;
  emit('update:modelValue', null);
}
</script>
