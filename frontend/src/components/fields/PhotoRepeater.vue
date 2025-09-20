<template>
  <Card bodyClass="p-4 border border-dashed rounded-2xl">
    <InputGroup :label="tr(photo.label)">
      <template #default="{ id }">
        <div class="space-y-2">
          <div>
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
              multiple
              class="sr-only"
              :aria-label="tr(photo.label)"
              @change="onChange"
            />
          </div>
          <ul class="mt-2 grid grid-cols-3 gap-2">
            <li v-for="(p, idx) in items" :key="idx" class="relative">
              <img :src="p.preview" class="w-32 h-32 object-cover rounded-lg" alt="" />
              <Button
                type="button"
                btnClass="btn-outline-danger absolute top-0 right-0 p-1"
                :aria-label="t('actions.delete')"
                @click="remove(idx)"
                @keyup.enter.prevent="remove(idx)"
                @keyup.space.prevent="remove(idx)"
              >
                <Icon icon="heroicons-outline:x-mark" aria-hidden="true" />
              </Button>
            </li>
          </ul>
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

const props = defineProps<{ photo: any; sectionKey: string; taskId: string; modelValue: any[] }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: any[]): void }>();
const { t, locale } = useI18n();

const fileInput = ref<HTMLInputElement | null>(null);
const items = ref<any[]>([]);

watch(
  () => props.modelValue,
  (val) => {
    items.value = (val || []).map((v: any) => ({ ...v }));
  },
  { immediate: true }
);

function tr(val: any) {
  return resolveI18n(val, locale.value);
}

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
