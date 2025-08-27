<template>
    <div>
      <form @submit.prevent="onSubmit" class="grid grid-cols-2 gap-8">
      <div>
        <div class="mb-4">
          <label class="block font-medium mb-1" for="name">Name<span class="text-red-600">*</span></label>
          <input id="name" v-model="name" class="border rounded p-2 w-full" />
        </div>

        <div class="mb-4">
          <h3 class="font-medium mb-2">Form Fields</h3>
          <div class="flex gap-4">
            <div class="w-1/3">
              <h4 class="text-sm font-semibold mb-2">Add Field</h4>
              <ul>
                <li v-for="t in fieldTypes" :key="t.key">
                  <button
                    type="button"
                    class="w-full mb-2 px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded"
                    @click="addField(t)"
                  >
                    {{ t.label }}
                  </button>
                </li>
              </ul>
            </div>
            <div class="flex-1">
              <h4 class="text-sm font-semibold mb-2">Layout</h4>
              <draggable v-model="fields" item-key="id" class="flex flex-col gap-2" handle=".handle">
                <template #item="{ element, index }">
                  <div
                    class="p-3 bg-white border rounded flex items-center justify-between"
                  >
                    <div class="flex items-center gap-2">
                      <span class="cursor-move handle text-gray-400">≡</span>
                      <input v-model="element.name" class="border rounded p-1 w-32" placeholder="name" />
                      <input v-model="element.label" class="border rounded p-1 w-32" placeholder="label" />
                      <select v-model="element.typeKey" class="border rounded p-1">
                        <option v-for="t in fieldTypes" :key="t.key" :value="t.key">{{ t.label }}</option>
                      </select>
                      <label class="flex items-center gap-1 text-sm">
                        <input type="checkbox" v-model="element.required" />
                        required
                      </label>
                      <select v-model.number="element.cols" class="border rounded p-1 w-24">
                        <option :value="2">Full</option>
                        <option :value="1">Half</option>
                      </select>
                    </div>
                    <button type="button" class="text-red-500" @click="removeField(index)">✕</button>
                  </div>
                </template>
              </draggable>
            </div>
          </div>
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
import draggable from 'vuedraggable';

interface Field {
  id: number;
  name: string;
  label: string;
  typeKey: string;
  required: boolean;
  cols: number;
}

const route = useRoute();
const router = useRouter();

const name = ref('');
const fields = ref<Field[]>([]);
const previewModel = ref<any>({});
const serverError = ref('');

const fieldTypes = [
  { key: 'text', label: 'Text', schema: { type: 'string' } },
  { key: 'number', label: 'Number', schema: { type: 'number' } },
  { key: 'date', label: 'Date', schema: { type: 'string', format: 'date' } },
  { key: 'time', label: 'Time', schema: { type: 'string', format: 'time' } },
  { key: 'boolean', label: 'Checkbox', schema: { type: 'boolean' } },
];

const isEdit = computed(() => route.name === 'types.edit');

function addField(t: any) {
  fields.value.push({
    id: Date.now() + Math.random(),
    name: `field${fields.value.length + 1}`,
    label: t.label,
    typeKey: t.key,
    required: false,
    cols: 2,
  });
}

function removeField(index: number) {
  fields.value.splice(index, 1);
}

const formSchemaObj = computed(() => {
  const properties: Record<string, any> = {};
  const required: string[] = [];
  fields.value.forEach((f) => {
    const def = fieldTypes.find((ft) => ft.key === f.typeKey);
    if (!def) return;
    properties[f.name] = { title: f.label, ...def.schema, 'x-cols': f.cols };
    if (f.required) required.push(f.name);
  });
  const schema: any = { type: 'object', properties };
  if (required.length) schema.required = required;
  return schema;
});

const fieldsSummaryObj = computed(() =>
  fields.value.map((f) => ({
    name: f.name,
    label: f.label,
    type: f.typeKey,
    required: f.required,
    cols: f.cols,
  })),
);

watch(
  fields,
  () => {
    previewModel.value = {};
  },
  { deep: true },
);

onMounted(async () => {
  if (isEdit.value) {
    const { data } = await api.get(`/appointment-types/${route.params.id}`);
    name.value = data.name;
    if (data.fields_summary) {
        if (Array.isArray(data.fields_summary)) {
          fields.value = data.fields_summary.map((f: any) => ({
            id: Date.now() + Math.random(),
            name: f.name || `field${fields.value.length + 1}`,
            label: f.label || f.name || 'Field',
            typeKey: f.type || 'text',
            required: !!f.required,
            cols: f.cols || 2,
          }));
        } else if (typeof data.fields_summary === 'object') {
          fields.value = Object.keys(data.fields_summary).map((key) => {
            const f = (data.fields_summary as any)[key];
            return {
              id: Date.now() + Math.random(),
              name: key,
              label: f.label || key,
              typeKey: f.type || 'text',
              required: !!f.required,
              cols: f.cols || 2,
            };
          });
        }
      }
    }
});

const canSubmit = computed(() => {
  return !!name.value && fields.value.length > 0;
});

async function onSubmit() {
  serverError.value = '';
  if (!canSubmit.value) return;
  const payload: any = {
    name: name.value,
    form_schema: JSON.stringify(formSchemaObj.value),
    fields_summary: JSON.stringify(fieldsSummaryObj.value),
  };
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
