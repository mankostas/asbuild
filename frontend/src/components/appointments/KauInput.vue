<template>
  <div>
    <label class="block font-medium mb-1">{{ label }}</label>
    <InputText
      v-model="value"
      type="text"
      class="w-full"
      :placeholder="placeholder"
      @change="onChange"
    />
    <Button class="mt-2" label="Scan" text @click="scan" />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

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
