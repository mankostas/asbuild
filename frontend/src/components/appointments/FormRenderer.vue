<template>
  <div>
    <div
      v-for="(config, key) in schema.properties"
      :key="key"
      class="mb-4"
    >
      <label :for="key" class="block mb-1">{{ key }}</label>
      <input
        v-model="model[key]"
        :id="key"
        class="border p-2 w-full"
        :required="schema.required && schema.required.includes(key)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';

interface Schema {
  properties: Record<string, any>;
  required?: string[];
}

const props = defineProps<{ schema: Schema; modelValue: Record<string, any> }>();
const emit = defineEmits(['update:modelValue']);

const model = reactive({ ...props.modelValue });

watch(
  () => props.modelValue,
  (val) => {
    Object.assign(model, val);
  },
  { deep: true }
);

watch(
  model,
  (val) => {
    emit('update:modelValue', { ...val });
  },
  { deep: true }
);
</script>
