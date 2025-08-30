<template>
  <input
    :id="id"
    type="datetime-local"
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
    if (v) {
      const d = parseISO(v);
      value.value = d.toISOString().slice(0, 16);
    } else {
      value.value = '';
    }
  },
  { immediate: true }
);

function onInput(e: Event) {
  const val = (e.target as HTMLInputElement).value;
  emit('update:modelValue', val ? toISO(val) : null);
}
</script>
