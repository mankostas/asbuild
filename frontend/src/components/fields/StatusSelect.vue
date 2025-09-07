<template>
  <InputGroup :label="label" :error="error" :disabled="disabled">
    <template #default="{ id }">
      <Select
        :id="id"
        :modelValue="modelValue ?? ''"
        :options="options"
        :placeholder="placeholder"
        :disabled="disabled"
        :aria-label="label"
        @update:model-value="(val) => emit('update:modelValue', val || null)"
      />
    </template>
  </InputGroup>
</template>

<script setup lang="ts">
import InputGroup from '@dc/components/InputGroup';
import Select from '@dc/components/Select';

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
