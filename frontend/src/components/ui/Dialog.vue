<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    @keydown.escape.prevent.stop="close"
  >
    <div class="bg-background text-foreground rounded shadow p-6 w-full max-w-md">
      <slot />
      <div class="mt-4 text-right">
        <Button @click="close">Close</Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Button from './Button.vue';

const props = defineProps<{ modelValue: boolean }>();
const emit = defineEmits(['update:modelValue']);

function close() {
  emit('update:modelValue', false);
}
</script>
