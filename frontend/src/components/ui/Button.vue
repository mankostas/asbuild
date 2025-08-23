<template>
  <button
    :class="[
      'inline-flex items-center justify-center rounded font-medium focus:outline-none focus:ring-2 focus:ring-offset-2',
      sizeClasses,
      variantClasses,
    ]"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost';
    size?: 'sm' | 'md' | 'lg';
  }>(),
  { variant: 'primary', size: 'md' }
);

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'secondary':
      return 'bg-foreground/10 text-foreground hover:bg-foreground/20';
    case 'ghost':
      return 'bg-transparent hover:bg-foreground/10';
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
