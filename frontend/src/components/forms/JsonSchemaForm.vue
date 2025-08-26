<template>
  <div v-if="schema && schema.properties">
    <div v-for="(prop, name) in schema.properties" :key="name" class="mb-4">
      <label :for="name" class="block font-medium mb-1">
        {{ name }}<span v-if="isRequired(name)" class="text-red-600">*</span>
      </label>
      <template v-if="prop.enum">
        <select
          :id="name"
          v-model="form[name]"
          class="border rounded p-2 w-full"
          :disabled="readonly"
          @change="validateField(name)"
        >
          <option value="" disabled>Select...</option>
          <option v-for="opt in prop.enum" :key="opt" :value="opt">{{ opt }}</option>
        </select>
      </template>
      <template v-else>
        <input
          v-if="fieldType(prop) === 'text'"
          :id="name"
          type="text"
          v-model="form[name]"
          class="border rounded p-2 w-full"
          :readonly="readonly"
          @input="validateField(name)"
        />
        <input
          v-else-if="fieldType(prop) === 'number'"
          :id="name"
          type="number"
          v-model.number="form[name]"
          class="border rounded p-2 w-full"
          :readonly="readonly"
          @input="validateField(name)"
        />
        <input
          v-else-if="fieldType(prop) === 'date'"
          :id="name"
          type="date"
          v-model="form[name]"
          class="border rounded p-2 w-full"
          :readonly="readonly"
          @input="validateField(name)"
        />
        <input
          v-else-if="fieldType(prop) === 'time'"
          :id="name"
          type="time"
          v-model="form[name]"
          class="border rounded p-2 w-full"
          :readonly="readonly"
          @input="validateField(name)"
        />
        <input
          v-else-if="fieldType(prop) === 'boolean'"
          :id="name"
          type="checkbox"
          v-model="form[name]"
          :disabled="readonly"
          @change="validateField(name)"
        />
      </template>
      <div v-if="errors[name]" class="text-red-600 text-sm mt-1">{{ errors[name] }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch, onMounted } from 'vue';

interface Schema {
  properties: Record<string, any>;
  required?: string[];
}

const props = defineProps<{ schema: Schema; modelValue: any; readonly?: boolean }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: any): void }>();

const form = reactive<any>({ ...props.modelValue });
const errors = reactive<Record<string, string>>({});

watch(
  () => props.modelValue,
  (val) => {
    Object.assign(form, val || {});
    if (props.schema?.required) {
      props.schema.required.forEach((f) => validateField(f));
    }
  },
  { deep: true },
);

watch(
  form,
  (val) => {
    emit('update:modelValue', { ...val });
  },
  { deep: true },
);

function isRequired(name: string) {
  return props.schema?.required?.includes(name);
}

function fieldType(prop: any) {
  if (prop.enum) return 'enum';
  if (prop.type === 'number' || prop.type === 'integer') return 'number';
  if (prop.type === 'boolean') return 'boolean';
  if (prop.type === 'string' && prop.format === 'date') return 'date';
  if (prop.type === 'string' && prop.format === 'time') return 'time';
  return 'text';
}

function validateField(name: string) {
  const val = form[name];
  if (isRequired(name) && (val === undefined || val === null || val === '')) {
    errors[name] = 'Required';
  } else {
    delete errors[name];
  }
}

onMounted(() => {
  if (props.schema?.required) {
    props.schema.required.forEach((f) => validateField(f));
  }
});
</script>
