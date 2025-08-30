<template>
    <div v-if="canAccess">
      <form class="grid grid-cols-2 gap-8" @submit.prevent="onSubmit">
      <div>
        <div v-if="auth.isSuperAdmin" class="mb-4">
          <span class="block font-medium mb-1">Tenant</span>
          <select
            id="tenant"
            v-model="tenantId"
            class="border rounded p-2 w-full"
            aria-label="Tenant"
          >
            <option value="">Global</option>
            <option v-for="t in tenantStore.tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
          <div v-if="errors.tenant_id" class="text-red-600 text-sm">{{ errors.tenant_id }}</div>
        </div>
        <div class="mb-4">
          <span class="block font-medium mb-1">Name<span class="text-red-600">*</span></span>
          <input
            id="name"
            v-model="name"
            class="border rounded p-2 w-full"
            aria-label="Name"
          />
          <div v-if="errors.name" class="text-red-600 text-sm">{{ errors.name }}</div>
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
                      <input
                        v-model="element.name"
                        class="border rounded p-1 w-32"
                        placeholder="name"
                        aria-label="Field name"
                      />
                      <input
                        v-model="element.label"
                        class="border rounded p-1 w-32"
                        placeholder="label"
                        aria-label="Field label"
                      />
                      <select
                        v-model="element.typeKey"
                        class="border rounded p-1"
                        aria-label="Field type"
                      >
                        <option v-for="t in fieldTypes" :key="t.key" :value="t.key">{{ t.label }}</option>
                      </select>
                      <div class="flex items-center gap-1 text-sm">
                        <input
                          v-model="element.required"
                          type="checkbox"
                          aria-label="required"
                        />
                        <span>required</span>
                      </div>
                      <select
                        v-model.number="element.cols"
                        class="border rounded p-1 w-24"
                        aria-label="Column span"
                      >
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

        <div class="mb-4">
          <h3 class="font-medium mb-2">Statuses</h3>
          <div class="flex gap-4">
            <div class="w-1/3">
              <h4 class="text-sm font-semibold mb-2">Available</h4>
              <ul>
                <li v-for="s in availableStatuses" :key="s.id">
                  <button
                    type="button"
                    class="w-full mb-2 px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded"
                    @click="addStatus(s)"
                  >
                    {{ s.name }}
                  </button>
                </li>
              </ul>
            </div>
            <div class="flex-1">
              <h4 class="text-sm font-semibold mb-2">Order</h4>
              <draggable v-model="selectedStatuses" item-key="id" class="flex flex-col gap-2" handle=".handle">
                <template #item="{ element, index }">
                  <div class="p-3 bg-white border rounded flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <span class="cursor-move handle text-gray-400">≡</span>
                      <span>{{ element.name }}</span>
                    </div>
                    <button type="button" class="text-red-500" @click="removeStatus(index)">✕</button>
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
          :task-id="0"
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { extractFormErrors } from '@/services/api';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';
import draggable from 'vuedraggable';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useForm } from 'vee-validate';

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
const auth = useAuthStore();
const tenantStore = useTenantStore();

const name = ref('');
const fields = ref<Field[]>([]);
const previewModel = ref<any>({});
const serverError = ref('');
const allStatuses = ref<any[]>([]);
const selectedStatuses = ref<any[]>([]);
const tenantId = ref<string | number | ''>('');
const { handleSubmit, setErrors, errors } = useForm();

const fieldTypes = [
  { key: 'text', label: 'Text', schema: { type: 'string' } },
  { key: 'number', label: 'Number', schema: { type: 'number' } },
  { key: 'date', label: 'Date', schema: { type: 'string', format: 'date' } },
  { key: 'time', label: 'Time', schema: { type: 'string', format: 'time' } },
  { key: 'boolean', label: 'Checkbox', schema: { type: 'boolean' } },
  { key: 'assignee', label: 'Assignee', schema: { type: 'object', kind: 'assignee' } },
];

const isEdit = computed(() => route.name === 'taskTypes.edit');
const canAccess = computed(() =>
  isEdit.value
    ? can('task_types.update') || can('task_types.manage')
    : can('task_types.create') || can('task_types.manage'),
);

const availableStatuses = computed(() =>
  allStatuses.value.filter(
    (s) => !selectedStatuses.value.find((sel) => sel.id === s.id),
  ),
);

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
  const fieldsArr = fields.value.map((f) => {
    const def = fieldTypes.find((ft) => ft.key === f.typeKey);
    properties[f.name] = {
      title: f.label,
      type: def?.schema?.type || 'string',
      ...def?.schema,
      'x-cols': f.cols,
    };
    if (f.required) required.push(f.name);
    return {
      key: f.name,
      label: f.label,
      type: f.typeKey,
      required: f.required,
      'x-cols': f.cols,
    };
  });
  const schema: any = {
    type: 'object',
    properties,
    sections: [
      { key: 'main', label: 'Main', fields: fieldsArr, photos: [] },
    ],
  };
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
  const { data: statusData } = await api.get('/task-statuses');
  allStatuses.value = statusData.data;
  if (auth.isSuperAdmin) {
    await tenantStore.loadTenants();
  }
  if (isEdit.value) {
    const { data } = await api.get(`/task-types/${route.params.id}`);
    name.value = data.name;
    tenantId.value = data.tenant_id || '';
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
    if (data.statuses) {
      const order = parseStatusOrder(data.statuses);
      selectedStatuses.value = order
        .map((n: string) => allStatuses.value.find((s: any) => s.name === n))
        .filter(Boolean);
    }
  }
});

const canSubmit = computed(() => {
  return !!name.value && fields.value.length > 0;
});

const onSubmit = handleSubmit(async () => {
  serverError.value = '';
  if (!canSubmit.value) return;
  const payload: any = {
    name: name.value,
    form_schema: JSON.stringify(formSchemaObj.value),
    fields_summary: JSON.stringify(fieldsSummaryObj.value),
  };
  if (selectedStatuses.value.length > 1) {
    payload.statuses = JSON.stringify(statusesObj.value);
  }
  if (auth.isSuperAdmin) {
    payload.tenant_id = tenantId.value || undefined;
  }
  try {
    if (isEdit.value) {
      await api.patch(`/task-types/${route.params.id}`, payload);
    } else {
      await api.post('/task-types', payload);
    }
    router.push({ name: 'taskTypes.list' });
  } catch (e: any) {
    const errs = extractFormErrors(e);
    if (Object.keys(errs).length) {
      setErrors(errs);
    } else {
      serverError.value = e.message || 'Failed to save';
    }
  }
});

function addStatus(s: any) {
  selectedStatuses.value.push(s);
}

function removeStatus(index: number) {
  selectedStatuses.value.splice(index, 1);
}

function parseStatusOrder(map: Record<string, string[]>): string[] {
  const nextSet = new Set<string>();
  Object.values(map).forEach((arr) => arr.forEach((v) => nextSet.add(v)));
  let current = Object.keys(map).find((k) => !nextSet.has(k));
  if (!current) return Object.keys(map);
  const order = [current];
  while (map[current] && map[current][0]) {
    current = map[current][0];
    order.push(current);
  }
  return order;
}

const statusesObj = computed(() => {
  const obj: Record<string, string[]> = {};
  const arr = selectedStatuses.value;
  for (let i = 0; i < arr.length - 1; i++) {
    obj[arr[i].name] = [arr[i + 1].name];
  }
  return obj;
});
</script>
