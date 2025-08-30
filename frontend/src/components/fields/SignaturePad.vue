<template>
  <div>
    <span class="sr-only">{{ label }}</span>
    <canvas
      ref="canvas"
      class="border"
      :aria-label="label"
      tabindex="0"
    />
    <button
      type="button"
      class="mt-2 px-2 py-1 border"
      aria-label="Clear signature"
      @click="clearPad"
    >
      Clear
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
const props = defineProps<{ label: string }>();
const emit = defineEmits<{ 'update:modelValue': [string] }>();
const canvas = ref<HTMLCanvasElement | null>(null);
function clearPad() {
  const ctx = canvas.value?.getContext('2d');
  if (ctx && canvas.value) {
    ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
  }
  emit('update:modelValue', '');
}
</script>
