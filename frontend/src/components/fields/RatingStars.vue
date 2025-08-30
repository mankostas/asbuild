<template>
  <div role="radiogroup" :aria-label="label" class="flex gap-1">
    <button
      v-for="i in 5"
      :key="i"
      type="button"
      role="radio"
      class="text-xl"
      :aria-checked="modelValue === i"
      @click="setRating(i)"
      @keyup.enter="setRating(i)"
    >
      {{ modelValue >= i ? '★' : '☆' }}
    </button>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ label: string; modelValue: number }>();
const emit = defineEmits<{ 'update:modelValue': [number] }>();
function setRating(val: number) {
  emit('update:modelValue', val);
}
</script>
