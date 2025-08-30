<template>
  <select
    class="form-select"
    :aria-label="label"
    :value="modelValue"
    @change="onChange"
  >
    <option v-for="opt in options" :key="opt.value" :value="opt.value">
      {{ opt.label }}
    </option>
  </select>
</template>

<script setup lang="ts">
interface Option { label: string; value: string }
const props = defineProps<{
  label: string;
  modelValue: string | null;
  options: Option[];
}>();
const emit = defineEmits<{ 'update:modelValue': [string | null] }>();
function onChange(e: Event) {
  emit('update:modelValue', (e.target as HTMLSelectElement).value);
}
</script>
