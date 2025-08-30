<template>
  <div role="group" :aria-label="ariaLabel" class="flex flex-wrap gap-2">
    <button
      v-for="opt in options"
      :key="opt"
      type="button"
      class="px-2 py-1 border rounded"
      :class="selected.has(opt) ? 'bg-blue-600 text-white' : 'bg-white text-gray-800'"
      :aria-pressed="selected.has(opt)"
      @click="toggle(opt)"
    >
      {{ opt }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{ modelValue: string[]; options: string[]; readonly?: boolean; ariaLabel: string }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: string[]): void }>();

const selected = ref(new Set(props.modelValue));

watch(
  () => props.modelValue,
  (v) => {
    selected.value = new Set(v);
  }
);

function toggle(opt: string) {
  if (props.readonly) return;
  if (selected.value.has(opt)) {
    selected.value.delete(opt);
  } else {
    selected.value.add(opt);
  }
  emit('update:modelValue', Array.from(selected.value));
}
</script>
