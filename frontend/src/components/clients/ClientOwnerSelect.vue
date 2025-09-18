<template>
  <Select
    v-bind="forwardedAttrs"
    v-model="model"
    :options="normalizedOptions"
    :placeholder="placeholder"
    :classInput="classInput"
    :disabled="disabled"
    :aria-label="resolvedAriaLabel"
  />
</template>

<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import Select from '@/components/ui/Select/index.vue';

type Option = {
  value: string | number;
  label: string;
  disabled?: boolean;
};

const props = withDefaults(
  defineProps<{
    modelValue?: string;
    options?: Option[];
    placeholder?: string;
    classInput?: string;
    disabled?: boolean;
    ariaLabel?: string;
  }>(),
  {
    modelValue: '',
    options: () => [],
    placeholder: '',
    classInput: 'text-xs !h-9',
    disabled: false,
    ariaLabel: '',
  },
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();

const normalizedOptions = computed(() =>
  (props.options || []).map((option) => ({
    ...option,
    value:
      typeof option.value === 'number'
        ? String(option.value)
        : option.value ?? '',
  })),
);

const model = computed({
  get: () => props.modelValue ?? '',
  set: (value: string | number) => {
    emit('update:modelValue', typeof value === 'number' ? String(value) : value);
  },
});

const attrs = useAttrs();

const forwardedAttrs = computed(() => {
  const result: Record<string, unknown> = {};
  Object.entries(attrs).forEach(([key, value]) => {
    if (key !== 'aria-label') {
      result[key] = value;
    }
  });
  return result;
});

const resolvedAriaLabel = computed(() => {
  const attrLabel = attrs['aria-label'] as string | undefined;
  return props.ariaLabel || attrLabel || props.placeholder || '';
});
</script>
