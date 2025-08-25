<template>
  <button
    :class="[
      'inline-flex items-center justify-center rounded-2xl font-medium focus-ring transition-colors shadow-sm',
      sizeClasses,
      variantClasses,
      full ? 'w-full' : '',
      disabled || loading ? 'opacity-50 pointer-events-none' : '',
    ]"
    :disabled="disabled || loading"
    :aria-busy="loading ? 'true' : undefined"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost' | 'destructive';
    size?: 'sm' | 'md' | 'lg';
    disabled?: boolean;
    loading?: boolean;
    full?: boolean;
  }>(),
  {
    variant: 'primary',
    size: 'md',
    disabled: false,
    loading: false,
    full: false,
  },
);

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'secondary':
      return 'bg-foreground/10 text-foreground hover:bg-foreground/20';
    case 'ghost':
      return 'bg-transparent hover:bg-foreground/10';
    case 'destructive':
      return 'bg-red-600 text-white hover:bg-red-700';
    default:
      return 'bg-primary text-primary-foreground hover:opacity-90';
  }
});

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'px-2 py-1 text-sm';
    case 'lg':
      return 'px-6 py-3 text-lg';
    default:
      return 'px-4 py-2';
  }
});
</script>
