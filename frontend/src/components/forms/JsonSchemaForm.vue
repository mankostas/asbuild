<template>
  <div v-if="schema?.sections" id="task-type-preview">
    <SectionCard
      v-for="section in schema.sections"
      :key="section.key"
      :section="section"
      :form="form"
      :errors="errors"
      :task-id="taskId"
      :readonly="readonly"
      :visible="logic.visible"
      :required="logic.required"
      :show-targets="logic.showTargets"
      @update="onUpdate"
      @error="onError"
    />
  </div>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import SectionCard from '@/components/tasks/SectionCard.vue';
import { evaluateLogic } from '@/utils/logic';

const props = defineProps<{ schema: any; modelValue: any; taskId: number; readonly?: boolean }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: any): void }>();

const form = reactive<any>({ ...props.modelValue });
const errors = reactive<Record<string, string>>({});
const logic = reactive<{ visible: Set<string>; required: Set<string>; showTargets: Set<string> }>(
  {
    visible: new Set(),
    required: new Set(),
    showTargets: new Set(),
  },
);

watch(
  () => props.modelValue,
  (val) => {
    Object.assign(form, val || {});
    recomputeLogic();
  },
  { deep: true },
);

watch(
  form,
  (val) => {
    emit('update:modelValue', { ...val });
    recomputeLogic();
  },
  { deep: true },
);

recomputeLogic();

function recomputeLogic() {
  const res = evaluateLogic(props.schema, form);
  logic.visible = res.visible;
  logic.required = res.required;
  logic.showTargets = res.showTargets;
}

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

defineExpose({ errors });
</script>
