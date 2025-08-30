<template>
  <input
    :id="id"
    type="date"
    class="border rounded p-2 w-full"
    :value="value"
    :readonly="readonly"
    :aria-label="ariaLabel"
    @input="onInput"
  />
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { toISO, parseISO } from '@/utils/datetime';

const props = defineProps<{ modelValue: string | null; readonly?: boolean; ariaLabel: string; id?: string }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: string | null): void }>();

const value = ref('');

watch(
  () => props.modelValue,
  (v) => {
    value.value = v ? parseISO(v).toISOString().split('T')[0] : '';
  },
  { immediate: true }
);

function onInput(e: Event) {
  const val = (e.target as HTMLInputElement).value;
  if (!val) {
    emit('update:modelValue', null);
  } else {
    emit('update:modelValue', toISO(val));
  }
}
</script>
