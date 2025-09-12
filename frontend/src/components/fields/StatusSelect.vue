<template>
  <Select
    :modelValue="modelValue ?? ''"
    :label="label"
    :options="options"
    :error="error"
    :disabled="disabled"
    :placeholder="placeholder"
    :aria-label="label"
    @update:model-value="(val) => emit('update:modelValue', val || null)"
  />
</template>

<script setup lang="ts">
import Select from '@/components/ui/Select/index.vue';

interface Option { label: string; value: string }

const props = defineProps<{
  label: string;
  modelValue: string | null;
  options: Option[];
  error?: any;
  disabled?: boolean;
  placeholder?: string;
}>();

const emit = defineEmits<{ 'update:modelValue': [string | null] }>();
</script>
