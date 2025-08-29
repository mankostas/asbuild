<template>
  <div v-if="schema?.sections">
    <SectionCard
      v-for="section in schema.sections"
      :key="section.key"
      :section="section"
      :form="form"
      :errors="errors"
      :task-id="taskId"
      :readonly="readonly"
      @update="onUpdate"
      @error="onError"
    />
  </div>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import SectionCard from '@/components/tasks/SectionCard.vue';

const props = defineProps<{ schema: any; modelValue: any; taskId: number; readonly?: boolean }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: any): void }>();

const form = reactive<any>({ ...props.modelValue });
const errors = reactive<Record<string, string>>({});

watch(
  () => props.modelValue,
  (val) => {
    Object.assign(form, val || {});
  },
  { deep: true },
);

watch(
  form,
  (val) => {
    emit('update:modelValue', { ...val });
  },
  { deep: true },
);

function onUpdate(payload: { key: string; value: any }) {
  form[payload.key] = payload.value;
}

function onError(payload: { key: string; msg: string }) {
  if (payload.msg) {
    errors[payload.key] = payload.msg;
  } else {
    delete errors[payload.key];
  }
}
</script>
