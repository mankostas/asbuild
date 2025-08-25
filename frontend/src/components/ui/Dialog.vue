<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    role="dialog"
    aria-modal="true"
    @keydown.escape.prevent.stop="close"
  >
    <div
      ref="panel"
      class="bg-background text-foreground rounded-2xl shadow-lg p-6 w-full max-w-md"
    >
      <slot />
      <div class="mt-4 text-right">
        <Button @click="close">Close</Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import Button from './Button.vue';

const props = defineProps<{ modelValue: boolean }>();
const emit = defineEmits(['update:modelValue']);
const panel = ref<HTMLDivElement>();

function close() {
  emit('update:modelValue', false);
}

function trap(e: FocusEvent) {
  if (
    props.modelValue &&
    panel.value &&
    !panel.value.contains(e.target as Node)
  ) {
    e.stopPropagation();
    panel.value.focus();
  }
}

onMounted(() => document.addEventListener('focusin', trap));
onUnmounted(() => document.removeEventListener('focusin', trap));
</script>
