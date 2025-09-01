<!-- eslint-disable vue/v-on-event-hyphenation -->
<template>
  <div class="mb-6">
    <h3 class="font-medium mb-2">{{ tr(section.label) }}</h3>
    <div class="grid grid-cols-2 gap-4">
      <template v-for="field in section.fields" :key="field.key">
        <div v-if="isVisible(field.key) && field.type === 'divider'" class="col-span-2">
          <hr />
        </div>
        <div v-else-if="isVisible(field.key) && field.type === 'headline'" class="col-span-2 font-bold">
          {{ tr(field.label) }}
        </div>
        <div v-else-if="isVisible(field.key)" :class="colClass(field)">
          <!-- eslint-disable-next-line vuejs-accessibility/label-has-for -->
          <label :for="field.key" class="block font-medium mb-1">
            {{ tr(field.label) }}<span v-if="isRequired(field)" class="text-red-600">*</span>
          </label>
          <input
            v-if="isText(field.type)"
            :id="field.key"
            v-model="local[field.key]"
            :type="inputType(field.type)"
            class="border rounded p-2 w-full"
            :readonly="readonly"
            :aria-label="tr(field.label)"
            :placeholder="tr(field.placeholder)"
            @input="emitUpdate(field)"
          />
          <DateInput
            v-else-if="field.type === 'date'"
            v-model="local[field.key]"
            :readonly="readonly"
            :aria-label="tr(field.label)"
            @update:modelValue="() => emitUpdate(field)"
          />
          <TimeInput
            v-else-if="field.type === 'time'"
            v-model="local[field.key]"
            :readonly="readonly"
            :aria-label="tr(field.label)"
            @update:modelValue="() => emitUpdate(field)"
          />
          <DateTimeInput
            v-else-if="field.type === 'datetime'"
            v-model="local[field.key]"
            :readonly="readonly"
            :aria-label="tr(field.label)"
            @update:modelValue="() => emitUpdate(field)"
          />
          <DurationInput
            v-else-if="field.type === 'duration'"
            v-model="local[field.key]"
            :readonly="readonly"
            :aria-label="tr(field.label)"
            @update:modelValue="() => emitUpdate(field)"
          />
          <textarea
            v-else-if="field.type === 'textarea'"
            :id="field.key"
            v-model="local[field.key]"
            class="border rounded p-2 w-full"
            :readonly="readonly"
            :aria-label="tr(field.label)"
            :placeholder="tr(field.placeholder)"
            @input="emitUpdate(field)"
          />
          <select
            v-else-if="field.type === 'select'"
            :id="field.key"
            v-model="local[field.key]"
            class="border rounded p-2 w-full"
            :disabled="readonly"
            :aria-label="tr(field.label)"
            @change="emitUpdate(field)"
          >
            <option value="" disabled>{{ t('common.select') }}</option>
            <option v-for="opt in field.enum" :key="opt" :value="opt">{{ opt }}</option>
          </select>
          <select
            v-else-if="field.type === 'multiselect'"
            :id="field.key"
            v-model="local[field.key]"
            multiple
            class="border rounded p-2 w-full"
            :disabled="readonly"
            :aria-label="tr(field.label)"
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
            :aria-label="tr(field.label)"
            @update:modelValue="() => emitUpdate(field)"
          />
          <CheckboxGroup
            v-else-if="field.type === 'checkbox'"
            v-model="local[field.key]"
            :name="field.key"
            :options="field.enum"
            :readonly="readonly"
            :aria-label="tr(field.label)"
            @update:modelValue="() => emitUpdate(field)"
          />
          <ChipsInput
            v-else-if="field.type === 'chips'"
            v-model="local[field.key]"
            :options="field.enum"
            :readonly="readonly"
            :aria-label="tr(field.label)"
            @update:modelValue="() => emitUpdate(field)"
          />
          <input
            v-else-if="field.type === 'boolean'"
            :id="field.key"
            v-model="local[field.key]"
            type="checkbox"
            :disabled="readonly"
            :aria-label="tr(field.label)"
            @change="emitUpdate(field)"
          />
          <AssigneePicker
            v-else-if="field.type === 'assignee'"
            v-model="local[field.key]"
            @change="emitUpdate(field)"
          />
          <ReviewerPicker
            v-else-if="field.type === 'reviewer'"
            v-model="local[field.key]"
            @change="emitUpdate(field)"
          />
          <RichText
            v-else-if="field.type === 'richtext'"
            v-model="local[field.key]"
            :readonly="readonly"
            :aria-label="tr(field.label)"
            @update:modelValue="() => emitUpdate(field)"
          />
          <MarkdownInput
            v-else-if="field.type === 'markdown'"
            v-model="local[field.key]"
            :readonly="readonly"
            :aria-label="tr(field.label)"
            @update:modelValue="() => emitUpdate(field)"
          />
          <div v-else-if="field.type === 'file'">
            <div v-if="files[field.key]" class="mb-2 relative inline-block">
              <img
                v-if="files[field.key].preview"
                :src="files[field.key].preview"
                class="w-32 h-32 object-cover"
                :alt="tr(field.label)"
              />
              <span v-else>{{ files[field.key].name }}</span>
              <button
                type="button"
                class="absolute top-0 right-0 bg-red-600 text-white px-1"
                :aria-label="t('actions.delete')"
                @click="removeFile(field)"
                @keyup.enter.prevent="removeFile(field)"
                @keyup.space.prevent="removeFile(field)"
              >
                ×
              </button>
            </div>
            <input
              v-if="!files[field.key]"
              :id="field.key"
              type="file"
              :aria-label="tr(field.label)"
              @change="onFile(field, $event)"
            />
          </div>
          <p v-if="field.help" class="text-xs text-gray-500">{{ tr(field.help) }}</p>
          <div v-if="errors[field.key]" class="text-red-600 text-sm mt-1" role="alert">{{ errors[field.key] }}</div>
        </div>
      </template>
      <template v-for="photo in section.photos" :key="photo.key">
        <PhotoField
          v-if="isVisible(photo.key) && photo.type === 'photo_single'"
          :photo="photo"
          :section-key="section.key"
          :task-id="taskId"
          :model-value="local[photo.key]"
          @update:modelValue="(v) => updatePhoto(photo.key, v)"
        />
        <PhotoRepeater
          v-else-if="isVisible(photo.key)"
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
import ReviewerPicker from '@/components/fields/ReviewerPicker.vue';
import RichText from '@/components/fields/RichText.vue';
import MarkdownInput from '@/components/fields/MarkdownInput.vue';
import PhotoField from '@/components/tasks/PhotoField.vue';
import PhotoRepeater from '@/components/tasks/PhotoRepeater.vue';
import ChipsInput from '@/components/fields/ChipsInput.vue';
import RadioGroup from '@/components/fields/RadioGroup.vue';
import CheckboxGroup from '@/components/fields/CheckboxGroup.vue';
import DateInput from '@/components/fields/DateInput.vue';
import TimeInput from '@/components/fields/TimeInput.vue';
import DateTimeInput from '@/components/fields/DateTimeInput.vue';
import DurationInput from '@/components/fields/DurationInput.vue';
import { uploadFile } from '@/services/uploader';
import { useI18n } from 'vue-i18n';
import { validate as runValidators } from '@/utils/validators';
import { resolveI18n } from '@/utils/i18n';

const props = defineProps<{ section: any; form: any; errors: Record<string, string>; taskId: number; readonly?: boolean; visible: Set<string>; required: Set<string>; showTargets: Set<string> }>();
const emit = defineEmits<{ (e: 'update', payload: { key: string; value: any }): void; (e: 'error', payload: { key: string; msg: string }): void }>();

const { t, locale } = useI18n();
const local = reactive<any>(props.form);
for (const field of props.section.fields) {
  if (field.type === 'time' && local[field.key] === undefined) {
    local[field.key] = null;
  }
}
const files = reactive<Record<string, { preview: string | null; name: string } | null>>({});

function tr(val: any) {
  return resolveI18n(val, locale.value);
}

function colClass(field: any) {
  return field['x-cols'] === 1 ? 'col-span-1' : 'col-span-2';
}

function isVisible(key: string) {
  return !props.showTargets.has(key) || props.visible.has(key);
}

function isRequired(field: any) {
  return (field.validations?.required ?? false) || props.required.has(field.key);
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
  validateField(field);
  emit('update', { key: field.key, value: local[field.key] });
}

async function onFile(field: any, e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  const uploaded = await uploadFile(file, {
    taskId: props.taskId,
    fieldKey: field.key,
    sectionKey: props.section.key,
  });
  if (file.type.startsWith('image/')) {
    files[field.key] = { preview: URL.createObjectURL(file), name: file.name };
    uploaded.preview = files[field.key]!.preview;
  } else {
    files[field.key] = { preview: null, name: file.name };
  }
  local[field.key] = uploaded;
  emit('update', { key: field.key, value: uploaded });
  validateField(field);
}

function removeFile(field: any) {
  files[field.key] = null;
  local[field.key] = null;
  emit('update', { key: field.key, value: null });
  validateField(field);
}

function updatePhoto(key: string, value: any) {
  emit('update', { key, value });
}

function validateField(field: any) {
  if (!isVisible(field.key)) {
    emit('error', { key: field.key, msg: '' });
    return;
  }
  const val = local[field.key];
  const rules = { ...(field.validations || {}) } as any;
  if (props.required.has(field.key)) {
    rules.required = true;
  }
  const msg = runValidators(val, rules);
  emit('error', { key: field.key, msg: msg || '' });
}
</script>
