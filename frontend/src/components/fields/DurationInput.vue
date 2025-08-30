<template>
  <input
    :id="id"
    type="number"
    class="border rounded p-2 w-full"
    :value="minutes"
    :readonly="readonly"
    :aria-label="ariaLabel"
    min="0"
    @input="onInput"
  />
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{ modelValue: string | null; readonly?: boolean; ariaLabel: string; id?: string }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: string | null): void }>();

const minutes = ref('');

watch(
  () => props.modelValue,
  (v) => {
    const m = v && /^PT(\d+)M$/.exec(v);
    minutes.value = m ? m[1] : '';
  },
  { immediate: true }
);

function onInput(e: Event) {
  const val = (e.target as HTMLInputElement).value;
  emit('update:modelValue', val ? `PT${val}M` : null);
}
</script>
