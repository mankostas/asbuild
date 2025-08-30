<template>
  <input
    :id="id"
    type="time"
    class="border rounded p-2 w-full"
    :value="value"
    :readonly="readonly"
    :aria-label="ariaLabel"
    @input="onInput"
  />
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{ modelValue: string | null; readonly?: boolean; ariaLabel: string; id?: string }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: string | null): void }>();

const value = ref('');

watch(
  () => props.modelValue,
  (v) => {
    value.value = v ? v.slice(0, 5) : '';
  },
  { immediate: true }
);

function onInput(e: Event) {
  const val = (e.target as HTMLInputElement).value;
  emit('update:modelValue', val || null);
}
</script>
