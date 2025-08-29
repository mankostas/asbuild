<template>
  <div>
    <span class="block mb-2">Schema</span>
    <textarea
      id="formbuilder-schema"
      v-model="json"
      class="border p-2 w-full h-64"
      placeholder="Enter JSON schema"
      aria-label="Schema"
    ></textarea>
    <div class="mt-2">
      <button class="bg-blue-600 text-white px-4 py-2" @click="emitSchema">
        Save
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{ modelValue: any }>();
const emit = defineEmits(['update:modelValue', 'save']);

const json = ref(JSON.stringify(props.modelValue || {}, null, 2));

watch(
  () => props.modelValue,
  (val) => {
    json.value = JSON.stringify(val || {}, null, 2);
  },
  { deep: true }
);

function emitSchema() {
  try {
    const parsed = JSON.parse(json.value || '{}');
    emit('update:modelValue', parsed);
    emit('save', parsed);
  } catch (e) {
    // ignore parse errors
  }
}
</script>
