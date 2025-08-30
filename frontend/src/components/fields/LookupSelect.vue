<template>
  <label class="block">
    <span class="sr-only">{{ label }}</span>
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
  </label>
</template>

<script setup lang="ts">
interface Option {
  label: string;
  value: string | number;
}
const props = defineProps<{
  label: string;
  modelValue: string | number | null;
  options: Option[];
}>();
const emit = defineEmits<{'update:modelValue':[string | number | null]}>();
function onChange(e: Event) {
  const target = e.target as HTMLSelectElement;
  emit('update:modelValue', target.value);
}
</script>
