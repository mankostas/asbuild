<template>
  <Select
    :modelValue="modelValue ?? ''"
    :label="label"
    :options="options"
    :aria-label="label"
    @update:modelValue="(val) => emit('update:modelValue', val ? Number(val) : null)"
  />
</template>

<script setup lang="ts">
import Select from '@/components/ui/Select/index.vue';

interface Option { label: string; value: number }

const props = defineProps<{
  label: string;
  modelValue: number | null;
  options: Option[];
}>();

const emit = defineEmits<{ 'update:modelValue': [number | null] }>();
</script>
