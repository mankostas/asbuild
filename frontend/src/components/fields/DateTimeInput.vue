<template>
  <Textinput
    :id="id"
    type="datetime-local"
    :label="label"
    :modelValue="display"
    :isReadonly="readonly"
    @update:modelValue="onChange"
  />
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import { toISO, parseISO } from '@/utils/datetime';

const props = defineProps<{
  modelValue: string | null;
  label: string;
  readonly?: boolean;
  id?: string;
}>();

const emit = defineEmits<{ (e: 'update:modelValue', value: string | null): void }>();

const display = ref('');

watch(
  () => props.modelValue,
  (v) => {
    if (v) {
      const d = parseISO(v);
      display.value = d.toISOString().slice(0, 16);
    } else {
      display.value = '';
    }
  },
  { immediate: true },
);

function onChange(val: string) {
  display.value = val;
  emit('update:modelValue', val ? toISO(val) : null);
}
</script>
