<template>
  <Card bodyClass="p-4 border border-dashed rounded-2xl">
    <InputGroup :label="tr(photo.label)">
      <template #default="{ id }">
        <div class="space-y-2">
          <div v-if="preview" class="relative inline-block">
            <img :src="preview" class="w-32 h-32 object-cover rounded-lg" :alt="tr(photo.label)" />
            <Button
              type="button"
              btnClass="btn-outline-danger absolute top-0 right-0 p-1"
              :aria-label="t('actions.delete')"
              @click="remove"
              @keyup.enter.prevent="remove"
              @keyup.space.prevent="remove"
            >
              <Icon icon="heroicons-outline:x-mark" aria-hidden="true" />
            </Button>
          </div>
          <div v-else>
            <Button
              type="button"
              btnClass="btn-outline-primary"
              :aria-label="t('actions.chooseFile')"
              @click="fileInput?.click()"
            >
              {{ t('actions.chooseFile') }}
            </Button>
            <input
              :id="id"
              ref="fileInput"
              type="file"
              class="sr-only"
              :aria-label="tr(photo.label)"
              @change="onChange"
            />
          </div>
        </div>
      </template>
    </InputGroup>
  </Card>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { uploadFile } from '@/services/uploader';
import { useI18n } from 'vue-i18n';
import Card from '@dc/components/Card';
import Button from '@dc/components/Button';
import InputGroup from '@dc/components/InputGroup';
import Icon from '@dc/components/Icon';
import { resolveI18n } from '@/utils/i18n';

const props = defineProps<{ photo: any; sectionKey: string; taskId: number; modelValue: any }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: any): void }>();
const { t, locale } = useI18n();

const preview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

watch(
  () => props.modelValue,
  (val) => {
    preview.value = val?.preview || null;
  },
  { immediate: true }
);

function tr(val: any) {
  return resolveI18n(val, locale.value);
}

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
