<!-- eslint-disable vue/v-on-event-hyphenation -->
<template>
  <div class="mb-6">
    <h3 class="font-medium mb-2">{{ section.label }}</h3>
    <div class="grid grid-cols-2 gap-4">
      <template v-for="field in section.fields" :key="field.key">
        <div :class="colClass(field)">
          <span class="block font-medium mb-1">
            {{ field.label }}<span v-if="field.required" class="text-red-600">*</span>
          </span>
          <input
            v-if="isText(field.type)"
            :id="field.key"
            v-model="local[field.key]"
            :type="inputType(field.type)"
            class="border rounded p-2 w-full"
            :readonly="readonly"
            :aria-label="field.label"
            @input="emitUpdate(field)"
          />
          <DateInput
            v-else-if="field.type === 'date'"
            v-model="local[field.key]"
            :readonly="readonly"
            :aria-label="field.label"
            @update:modelValue="() => emitUpdate(field)"
          />
          <TimeInput
            v-else-if="field.type === 'time'"
            v-model="local[field.key]"
            :readonly="readonly"
            :aria-label="field.label"
            @update:modelValue="() => emitUpdate(field)"
          />
          <DateTimeInput
            v-else-if="field.type === 'datetime'"
            v-model="local[field.key]"
            :readonly="readonly"
            :aria-label="field.label"
            @update:modelValue="() => emitUpdate(field)"
          />
          <DurationInput
            v-else-if="field.type === 'duration'"
            v-model="local[field.key]"
            :readonly="readonly"
            :aria-label="field.label"
            @update:modelValue="() => emitUpdate(field)"
          />
          <textarea
            v-else-if="field.type === 'textarea'"
            :id="field.key"
            v-model="local[field.key]"
            class="border rounded p-2 w-full"
            :readonly="readonly"
            :aria-label="field.label"
            @input="emitUpdate(field)"
          />
          <select
            v-else-if="field.type === 'select'"
            :id="field.key"
            v-model="local[field.key]"
            class="border rounded p-2 w-full"
            :disabled="readonly"
            :aria-label="field.label"
            @change="emitUpdate(field)"
          >
            <option value="" disabled>Select...</option>
            <option v-for="opt in field.enum" :key="opt" :value="opt">{{ opt }}</option>
          </select>
          <select
            v-else-if="field.type === 'multiselect'"
            :id="field.key"
            v-model="local[field.key]"
            multiple
            class="border rounded p-2 w-full"
            :disabled="readonly"
            :aria-label="field.label"
            @change="emitUpdate(field)"
          >
            <option v-for="opt in field.enum" :key="opt" :value="opt">{{ opt }}</option>
          </select>
          <RadioGroup
            v-else-if="field.type === 'radio'"
            v-model="local[field.key]"
            :name="field.key"
            :options="field.enum"
            :readonly="readonly"
            :aria-label="field.label"
            @update:modelValue="() => emitUpdate(field)"
          />
          <CheckboxGroup
            v-else-if="field.type === 'checkbox'"
            v-model="local[field.key]"
            :name="field.key"
            :options="field.enum"
            :readonly="readonly"
            :aria-label="field.label"
            @update:modelValue="() => emitUpdate(field)"
          />
          <ChipsInput
            v-else-if="field.type === 'chips'"
            v-model="local[field.key]"
            :options="field.enum"
            :readonly="readonly"
            :aria-label="field.label"
            @update:modelValue="() => emitUpdate(field)"
          />
          <input
            v-else-if="field.type === 'boolean'"
            :id="field.key"
            v-model="local[field.key]"
            type="checkbox"
            :disabled="readonly"
            :aria-label="field.label"
            @change="emitUpdate(field)"
          />
          <AssigneePicker
            v-else-if="field.type === 'assignee'"
            v-model="local[field.key]"
            @change="emitUpdate(field)"
          />
          <input
            v-else-if="field.type === 'file'"
            :id="field.key"
            type="file"
            :aria-label="field.label"
            @change="onFile(field, $event)"
          />
          <div v-if="errors[field.key]" class="text-red-600 text-sm mt-1">{{ errors[field.key] }}</div>
        </div>
      </template>
      <template v-for="photo in section.photos" :key="photo.key">
        <PhotoField
          v-if="photo.type === 'photo_single'"
          :photo="photo"
          :section-key="section.key"
          :task-id="taskId"
          :model-value="local[photo.key]"
          @update:modelValue="(v) => updatePhoto(photo.key, v)"
        />
        <PhotoRepeater
          v-else
          :photo="photo"
          :section-key="section.key"
          :task-id="taskId"
          :model-value="local[photo.key]"
          @update:modelValue="(v) => updatePhoto(photo.key, v)"
        />
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import AssigneePicker from '@/components/tasks/AssigneePicker.vue';
import PhotoField from '@/components/tasks/PhotoField.vue';
import PhotoRepeater from '@/components/tasks/PhotoRepeater.vue';
import ChipsInput from '@/components/fields/ChipsInput.vue';
import RadioGroup from '@/components/fields/RadioGroup.vue';
import CheckboxGroup from '@/components/fields/CheckboxGroup.vue';
import DateInput from '@/components/fields/DateInput.vue';
import TimeInput from '@/components/fields/TimeInput.vue';
import DateTimeInput from '@/components/fields/DateTimeInput.vue';
import DurationInput from '@/components/fields/DurationInput.vue';

const props = defineProps<{ section: any; form: any; errors: Record<string, string>; taskId: number; readonly?: boolean }>();
const emit = defineEmits<{ (e: 'update', payload: { key: string; value: any }): void; (e: 'error', payload: { key: string; msg: string }): void }>();

const local = reactive<any>(props.form);

function colClass(field: any) {
  return field['x-cols'] === 1 ? 'col-span-1' : 'col-span-2';
}

function isText(type: string) {
  return ['text', 'number', 'email', 'phone', 'url'].includes(type);
}

function inputType(type: string) {
  if (type === 'number') return 'number';
  if (type === 'email') return 'email';
  if (type === 'phone') return 'tel';
  if (type === 'url') return 'url';
  return 'text';
}

function emitUpdate(field: any) {
  validate(field);
  emit('update', { key: field.key, value: local[field.key] });
}

function onFile(field: any, e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  emit('update', { key: field.key, value: file });
  validate(field);
}

function updatePhoto(key: string, value: any) {
  emit('update', { key, value });
}

function validate(field: any) {
  const val = local[field.key];
  if (field.required && (val === undefined || val === null || val === '' || (Array.isArray(val) && !val.length))) {
    emit('error', { key: field.key, msg: 'Required' });
  } else {
    emit('error', { key: field.key, msg: '' });
  }
}
</script>
