<template>
  <div>
    <textarea
      v-if="!readonly"
      :id="id"
      v-model="local"
      class="border rounded p-2 w-full"
      :aria-label="ariaLabel"
      @input="emitUpdate"
    />
    <pre v-else class="whitespace-pre-wrap">{{ modelValue }}</pre>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{ modelValue: string; readonly?: boolean; ariaLabel?: string; id?: string }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>();

const local = ref(props.modelValue || '');

watch(
  () => props.modelValue,
  (val) => {
    local.value = val || '';
  },
);

function emitUpdate() {
  emit('update:modelValue', local.value);
}
</script>
