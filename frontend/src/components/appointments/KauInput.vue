<template>
  <div>
    <label class="block font-medium mb-1">{{ label }}</label>
    <input
      v-model="value"
      type="text"
      class="border rounded p-2 w-full"
      :placeholder="placeholder"
      @change="onChange"
    />
    <button class="mt-2 text-blue-600" @click="scan">Scan</button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: String,
  label: { type: String, default: 'KAU' },
  placeholder: { type: String, default: 'Enter or scan KAU' },
});
const emit = defineEmits(['update:modelValue']);
const value = ref(props.modelValue || '');

watch(
  () => props.modelValue,
  (v) => {
    value.value = v || '';
  }
);

function onChange() {
  emit('update:modelValue', value.value);
}

function scan() {
  const code = prompt('Scan code');
  if (code) {
    value.value = code;
    emit('update:modelValue', code);
  }
}
</script>
