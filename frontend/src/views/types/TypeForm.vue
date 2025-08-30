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
          <h3 class="font-medium mb-2">Sections</h3>
          <div
            v-for="(section, sIdx) in sections"
            :key="section.id"
            class="mb-6 border rounded p-4"
          >
            <div class="flex items-center gap-2 mb-4">
              <input
                v-model="section.key"
                class="border rounded p-1 w-32"
                placeholder="key"
                aria-label="Section key"
              />
              <input
                v-model="section.label"
                class="border rounded p-1 w-32"
                placeholder="label"
                aria-label="Section label"
              />
              <button
                type="button"
                class="text-red-500"
                @click="removeSection(sIdx)"
              >
                ✕
              </button>
            </div>
            <div class="mb-4">
              <h4 class="text-sm font-semibold mb-2">Fields</h4>
              <div class="flex gap-4">
                <div class="w-1/3">
                  <h5 class="text-sm font-semibold mb-2">Add Field</h5>
                  <ul>
                    <li v-for="t in fieldTypes" :key="t.key">
                      <button
                        type="button"
                        class="w-full mb-2 px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded"
                        @click="addField(section, t)"
                      >
                        {{ t.label }}
                      </button>
                    </li>
                  </ul>
                </div>
                <div class="flex-1">
                  <h5 class="text-sm font-semibold mb-2">Layout</h5>
                  <draggable
                    v-model="section.fields"
                    item-key="id"
                    class="flex flex-col gap-2"
                    handle=".handle"
                  >
                    <template #item="{ element, index }">
                      <div
                        class="p-3 bg-white border rounded flex flex-col gap-2"
                      >
                        <div class="flex items-center gap-2 justify-between">
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
                              <option
                                v-for="t in fieldTypes"
                                :key="t.key"
                                :value="t.key"
                              >
                                {{ t.label }}
                              </option>
                            </select>
                            <div
                              v-if="element.typeKey !== 'repeater'"
                              class="flex items-center gap-1 text-sm"
                            >
                              <input
                                v-model="element.required"
                                type="checkbox"
                                aria-label="required"
                              />
                              <span>required</span>
                            </div>
                            <select
                              v-if="element.typeKey !== 'repeater'"
                              v-model.number="element.cols"
                              class="border rounded p-1 w-24"
                              aria-label="Column span"
                            >
                              <option :value="2">Full</option>
                              <option :value="1">Half</option>
                            </select>
                          </div>
                          <button
                            type="button"
                            class="text-red-500"
                            @click="removeField(section, index)"
                          >
                            ✕
                          </button>
                        </div>
                        <div
                          v-if="element.typeKey === 'repeater'"
                          class="ml-6 mt-2"
                        >
                          <div class="flex gap-4">
                            <div class="w-1/3">
                              <h5 class="text-sm font-semibold mb-2">Add Field</h5>
                              <ul>
                                <li
                                  v-for="t in fieldTypes.filter((ft) => ft.key !== 'repeater')"
                                  :key="t.key"
                                >
                                  <button
                                    type="button"
                                    class="w-full mb-2 px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded"
                                    @click="addSubField(element, t)"
                                  >
                                    {{ t.label }}
                                  </button>
                                </li>
                              </ul>
                            </div>
                            <div class="flex-1">
                              <h5 class="text-sm font-semibold mb-2">Fields</h5>
                              <draggable
                                v-model="element.fields"
                                item-key="id"
                                class="flex flex-col gap-2"
                                handle=".handle"
                              >
                                <template #item="{ element: sub, index: sindex }">
                                  <div
                                    class="p-3 bg-white border rounded flex items-center justify-between"
                                  >
                                    <div class="flex items-center gap-2">
                                      <span class="cursor-move handle text-gray-400">≡</span>
                                      <input
                                        v-model="sub.name"
                                        class="border rounded p-1 w-32"
                                        placeholder="name"
                                        aria-label="Subfield name"
                                      />
                                      <input
                                        v-model="sub.label"
                                        class="border rounded p-1 w-32"
                                        placeholder="label"
                                        aria-label="Subfield label"
                                      />
                                      <select
                                        v-model="sub.typeKey"
                                        class="border rounded p-1"
                                        aria-label="Subfield type"
                                      >
                                        <option
                                          v-for="t in fieldTypes.filter((ft) => ft.key !== 'repeater')"
                                          :key="t.key"
                                          :value="t.key"
                                        >
                                          {{ t.label }}
                                        </option>
                                      </select>
                                      <div class="flex items-center gap-1 text-sm">
                                        <input
                                          v-model="sub.required"
                                          type="checkbox"
                                          aria-label="required"
                                        />
                                        <span>required</span>
                                      </div>
                                      <select
                                        v-model.number="sub.cols"
                                        class="border rounded p-1 w-24"
                                        aria-label="Column span"
                                      >
                                        <option :value="2">Full</option>
                                        <option :value="1">Half</option>
                                      </select>
                                    </div>
                                    <button
                                      type="button"
                                      class="text-red-500"
                                      @click="removeSubField(element, sindex)"
                                    >
                                      ✕
                                    </button>
                                  </div>
                                </template>
                              </draggable>
                            </div>
                          </div>
                        </div>
                      </div>
                    </template>
                  </draggable>
                </div>
              </div>
            </div>
            <div class="mb-4">
              <h4 class="text-sm font-semibold mb-2">Photos</h4>
              <div class="flex gap-4">
                <div class="w-1/3">
                  <h5 class="text-sm font-semibold mb-2">Add Photo</h5>
                  <ul>
                    <li v-for="p in photoTypes" :key="p.key">
                      <button
                        type="button"
                        class="w-full mb-2 px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded"
                        @click="addPhoto(section, p)"
                      >
                        {{ p.label }}
                      </button>
                    </li>
                  </ul>
                </div>
                <div class="flex-1">
                  <h5 class="text-sm font-semibold mb-2">Layout</h5>
                  <draggable
                    v-model="section.photos"
                    item-key="id"
                    class="flex flex-col gap-2"
                    handle=".handle"
                  >
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
                            aria-label="Photo name"
                          />
                          <input
                            v-model="element.label"
                            class="border rounded p-1 w-32"
                            placeholder="label"
                            aria-label="Photo label"
                          />
                          <select
                            v-model="element.typeKey"
                            class="border rounded p-1"
                            aria-label="Photo type"
                          >
                            <option
                              v-for="p in photoTypes"
                              :key="p.key"
                              :value="p.key"
                            >
                              {{ p.label }}
                            </option>
                          </select>
                        </div>
                        <button
                          type="button"
                          class="text-red-500"
                          @click="removePhoto(section, index)"
                        >
                          ✕
                        </button>
                      </div>
                    </template>
                  </draggable>
                </div>
              </div>
            </div>
          </div>
          <button
            type="button"
            class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded"
            @click="addSection"
          >
            Add Section
          </button>
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
  fields?: Field[];
}

interface Photo {
  id: number;
  name: string;
  label: string;
  typeKey: string;
}

interface Section {
  id: number;
  key: string;
  label: string;
  fields: Field[];
  photos: Photo[];
}

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const tenantStore = useTenantStore();

const name = ref('');
const sections = ref<Section[]>([
  {
    id: Date.now(),
    key: 'main',
    label: 'Main',
    fields: [],
    photos: [],
  },
]);
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
  { key: 'repeater', label: 'Repeater', schema: { type: 'array' } },
];

const photoTypes = [
  { key: 'photo_single', label: 'Photo' },
  { key: 'photo_repeater', label: 'Photo Repeater' },
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

function addSection() {
  sections.value.push({
    id: Date.now() + Math.random(),
    key: `section${sections.value.length + 1}`,
    label: `Section ${sections.value.length + 1}`,
    fields: [],
    photos: [],
  });
}

function removeSection(index: number) {
  sections.value.splice(index, 1);
}

function addField(section: Section, t: any) {
  section.fields.push({
    id: Date.now() + Math.random(),
    name: `field${section.fields.length + 1}`,
    label: t.label,
    typeKey: t.key,
    required: false,
    cols: 2,
    fields: t.key === 'repeater' ? [] : undefined,
  });
}

function removeField(section: Section, index: number) {
  section.fields.splice(index, 1);
}

function addSubField(field: Field, t: any) {
  if (!field.fields) field.fields = [];
  field.fields.push({
    id: Date.now() + Math.random(),
    name: `sub${field.fields.length + 1}`,
    label: t.label,
    typeKey: t.key,
    required: false,
    cols: 2,
  });
}

function removeSubField(field: Field, index: number) {
  field.fields?.splice(index, 1);
}

function addPhoto(section: Section, p: any) {
  section.photos.push({
    id: Date.now() + Math.random(),
    name: `photo${section.photos.length + 1}`,
    label: p.label,
    typeKey: p.key,
  });
}

function removePhoto(section: Section, index: number) {
  section.photos.splice(index, 1);
}

function mapField(f: Field): any {
  const obj: any = {
    key: f.name,
    label: f.label,
    type: f.typeKey,
    required: f.required,
    'x-cols': f.cols,
  };
  if (f.typeKey === 'repeater') {
    obj.fields = (f.fields || []).map(mapField);
  }
  return obj;
}

const formSchemaObj = computed(() => {
  return {
    sections: sections.value.map((s) => ({
      key: s.key,
      label: s.label,
      fields: s.fields.map(mapField),
      photos: s.photos.map((p) => ({
        key: p.name,
        label: p.label,
        type: p.typeKey,
      })),
    })),
  };
});

const fieldsSummaryObj = computed(() => {
  const arr: any[] = [];
  sections.value.forEach((s) => {
    s.fields.forEach((f) => {
      arr.push({
        name: f.name,
        label: f.label,
        type: f.typeKey,
        required: f.required,
        cols: f.cols,
      });
    });
    s.photos.forEach((p) => {
      arr.push({ name: p.name, label: p.label, type: p.typeKey });
    });
  });
  return arr;
});

watch(
  sections,
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
    if (data.form_schema) {
      let schema = data.form_schema;
      if (typeof schema === 'string') {
        try {
          schema = JSON.parse(schema);
        } catch {
          schema = null;
        }
      }
      if (schema && Array.isArray(schema.sections)) {
        sections.value = schema.sections.map((s: any) => ({
          id: Date.now() + Math.random(),
          key: s.key || `section${sections.value.length + 1}`,
          label: s.label || s.key || 'Section',
          fields: (s.fields || []).map(mapFieldFromSchema),
          photos: (s.photos || []).map((p: any) => ({
            id: Date.now() + Math.random(),
            name: p.key,
            label: p.label || p.key,
            typeKey: p.type || 'photo_single',
          })),
        }));
      }
    }
    if (data.statuses) {
      let statuses = data.statuses;
      if (typeof statuses === 'string') {
        try {
          statuses = JSON.parse(statuses);
        } catch {
          statuses = null;
        }
      }
      const order = parseStatusOrder(statuses || {});
      selectedStatuses.value = order
        .map((n: string) => allStatuses.value.find((s: any) => s.name === n))
        .filter(Boolean);
    }
  }
});

function mapFieldFromSchema(f: any): Field {
  return {
    id: Date.now() + Math.random(),
    name: f.key,
    label: f.label || f.key,
    typeKey: f.type || 'text',
    required: !!f.required,
    cols: f['x-cols'] || 2,
    fields: (f.fields || []).map(mapFieldFromSchema),
  };
}

const canSubmit = computed(() => {
  return (
    !!name.value &&
    sections.value.length > 0 &&
    sections.value.some((s) => s.fields.length || s.photos.length)
  );
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
