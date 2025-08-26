<template>
  <div>
    <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit' : 'Create' }} Type</h2>
    <form @submit.prevent="onSubmit" class="grid grid-cols-2 gap-8">
      <div>
        <div class="mb-4">
          <label class="block font-medium mb-1" for="name">Name<span class="text-red-600">*</span></label>
          <input id="name" v-model="name" class="border rounded p-2 w-full" />
        </div>
        <div class="mb-4">
          <label class="block font-medium mb-1" for="summary">Fields Summary (JSON)</label>
          <textarea
            id="summary"
            v-model="fieldsSummaryText"
            class="border rounded p-2 w-full h-32 font-mono"
          ></textarea>
          <div v-if="fieldsSummaryError" class="text-red-600 text-sm mt-1">{{ fieldsSummaryError }}</div>
        </div>
        <div class="mb-4">
          <label class="block font-medium mb-1" for="schema">Form Schema (JSON)</label>
          <textarea
            id="schema"
            v-model="formSchemaText"
            class="border rounded p-2 w-full h-64 font-mono"
          ></textarea>
          <div v-if="formSchemaError" class="text-red-600 text-sm mt-1">{{ formSchemaError }}</div>
        </div>
        <div v-if="serverError" class="text-red-600 text-sm mb-2">{{ serverError }}</div>
        <button
          type="submit"
          class="px-4 py-2 bg-blue-600 text-white rounded"
          :disabled="!canSubmit"
        >Save</button>
      </div>
      <div>
        <h3 class="text-lg font-bold mb-2">Preview</h3>
        <JsonSchemaForm
          v-if="formSchemaObj"
          v-model="previewModel"
          :schema="formSchemaObj"
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';

const route = useRoute();
const router = useRouter();

const name = ref('');
const formSchemaText = ref('');
const fieldsSummaryText = ref('');
const formSchemaObj = ref<any | null>(null);
const fieldsSummaryObj = ref<any | null>(null);
const formSchemaError = ref('');
const fieldsSummaryError = ref('');
const serverError = ref('');
const previewModel = ref<any>({});

const isEdit = computed(() => route.name === 'types.edit');

function parseSchema() {
  formSchemaError.value = '';
  try {
    if (!formSchemaText.value.trim()) {
      formSchemaObj.value = null;
      return;
    }
    const obj = JSON.parse(formSchemaText.value);
    if (obj.type !== 'object' || typeof obj.properties !== 'object') {
      formSchemaError.value = 'Schema must be an object with properties';
      formSchemaObj.value = null;
    } else {
      formSchemaObj.value = obj;
    }
  } catch (e) {
    formSchemaError.value = 'Invalid JSON';
    formSchemaObj.value = null;
  }
}

function parseSummary() {
  fieldsSummaryError.value = '';
  try {
    if (!fieldsSummaryText.value.trim()) {
      fieldsSummaryObj.value = null;
      return;
    }
    fieldsSummaryObj.value = JSON.parse(fieldsSummaryText.value);
  } catch (e) {
    fieldsSummaryError.value = 'Invalid JSON';
    fieldsSummaryObj.value = null;
  }
}

watch(formSchemaText, parseSchema);
watch(fieldsSummaryText, parseSummary);

onMounted(async () => {
  if (isEdit.value) {
    const { data } = await api.get(`/appointment-types/${route.params.id}`);
    name.value = data.name;
    formSchemaText.value = data.form_schema
      ? JSON.stringify(data.form_schema, null, 2)
      : '';
    fieldsSummaryText.value = data.fields_summary
      ? JSON.stringify(data.fields_summary, null, 2)
      : '';
    parseSchema();
    parseSummary();
  }
});

const canSubmit = computed(() => {
  return !!name.value && !formSchemaError.value && !fieldsSummaryError.value;
});

async function onSubmit() {
  serverError.value = '';
  parseSchema();
  parseSummary();
  if (!canSubmit.value) return;

  const payload: any = { name: name.value };
  if (formSchemaText.value.trim()) payload.form_schema = formSchemaText.value;
  if (fieldsSummaryText.value.trim()) payload.fields_summary = fieldsSummaryText.value;
  try {
    if (isEdit.value) {
      await api.patch(`/appointment-types/${route.params.id}`, payload);
    } else {
      await api.post('/appointment-types', payload);
    }
    router.push({ name: 'types.list' });
  } catch (e: any) {
    serverError.value = e.message || 'Failed to save';
  }
}
</script>
