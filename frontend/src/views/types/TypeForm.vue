<template>
  <div v-if="canAccess" class="type-builder">
    <form @submit.prevent="onSubmit">
      <header class="builder-header flex items-center justify-between px-4 py-2 shadow">
        <h1 class="text-lg font-semibold">{{ isEdit ? t('routes.taskTypeEdit') : t('routes.taskTypeCreate') }}</h1>
        <div class="flex items-center gap-3">
          <div>
            <label for="versionSelect" class="sr-only">{{ t('Version') }}</label>
            <select
              v-if="versions.length"
              id="versionSelect"
              v-model="selectedVersionId"
              @change="onVersionChange"
              class="text-xs px-2 py-1 border rounded"
              :aria-label="t('Version')"
            >
              <option v-for="v in versions" :key="v.id" :value="v.id">v{{ v.semver }}</option>
            </select>
            <span v-else class="text-xs px-2 py-1 border rounded" :aria-label="t('Version')">v1</span>
          </div>
          <label class="flex items-center gap-1" for="previewToggle">
            <input id="previewToggle" v-model="showPreview" type="checkbox" aria-label="Toggle preview" />
            <span>{{ t('Preview') }}</span>
          </label>
          <button type="submit" class="px-3 py-1 bg-indigo-600 text-white rounded" aria-label="Save">{{ t('Save') }}</button>
        </div>
      </header>
      <WorkflowDesigner
        v-model="statusFlow"
        v-model:statuses="statuses"
        class="p-4 border-b"
      />
      <div class="flex h-[calc(100vh-3rem)]">
        <aside class="w-1/5 border-r overflow-y-auto">
          <FieldPalette :groups="paletteGroups" @select="onAddField" />
        </aside>
        <main class="flex-1 overflow-y-auto p-4">
            <button
              type="button"
              class="mb-4 px-2 py-1 border rounded"
              aria-label="Add section"
              @click="addSection"
            >+
              {{ t('Section') }}</button>
          <draggable v-model="sections" item-key="id" handle=".handle" class="space-y-4">
            <template #item="{ element, index }">
              <CanvasSection :section="element" @remove="removeSection(index)" @select="selectField" />
            </template>
          </draggable>
        </main>
        <aside class="w-1/4 border-l overflow-y-auto p-4">
          <InspectorTabs :selected="selected" />
        </aside>
      </div>
      <div v-if="showPreview" class="builder-preview p-4 border-t">
          <JsonSchemaForm v-model="previewData" :schema="previewSchema" :task-id="0" />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';
import FieldPalette from '@/components/types/FieldPalette.vue';
import CanvasSection from '@/components/types/CanvasSection.vue';
import InspectorTabs from '@/components/types/Inspector/InspectorTabs.vue';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';
import WorkflowDesigner from '@/components/types/WorkflowDesigner.vue';
import { can } from '@/stores/auth';
import api from '@/services/api';
import { useTaskTypeVersionsStore } from '@/stores/taskTypeVersions';
import '@/styles/types-builder.css';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const versionsStore = useTaskTypeVersionsStore();

interface Field {
  id: number;
  name: string;
  label: string;
  typeKey: string;
  cols: number;
  fields?: Field[];
  placeholder?: string;
  help?: string;
  validations: Record<string, any>;
}

interface Section {
  id: number;
  key: string;
  label: string;
  fields: Field[];
  photos: any[];
}

const name = ref('');
const tenantId = ref<number | ''>('');
const sections = ref<Section[]>([]);
const selected = ref<Field | null>(null);
const showPreview = ref(false);
const previewData = ref<Record<string, any>>({});
const versions = ref<any[]>([]);
const selectedVersionId = ref<number | null>(null);
const statuses = ref<string[]>([]);
const statusFlow = ref<[string, string][]>([]);

const fieldTypes = [
  { key: 'text', label: 'Text', group: 'Inputs' },
  { key: 'number', label: 'Number', group: 'Inputs' },
  { key: 'date', label: 'Date', group: 'Dates' },
  { key: 'time', label: 'Time', group: 'Dates' },
  { key: 'boolean', label: 'Checkbox', group: 'Choices' },
  { key: 'assignee', label: 'Assignee', group: 'People' },
  { key: 'repeater', label: 'Repeater', group: 'Content' },
];

const paletteGroups = computed(() => {
  const groups = ['Inputs', 'Choices', 'Dates', 'People', 'Files', 'Content', 'Calculated'];
  return groups.map((g) => ({ label: g, items: fieldTypes.filter((f) => f.group === g) }));
});

const isEdit = computed(() => route.name === 'taskTypes.edit');
const canAccess = computed(() =>
  isEdit.value
    ? can('task_types.update') || can('task_types.manage')
    : can('task_types.create') || can('task_types.manage'),
);

onMounted(async () => {
  if (isEdit.value) {
    const list = await versionsStore.list(Number(route.params.id));
    versions.value = list;
    if (list.length) {
      selectedVersionId.value = list[0].id;
      loadVersion(list[0]);
    }
  }
});

function addSection() {
  sections.value.push({
    id: Date.now() + Math.random(),
    key: `section${sections.value.length + 1}`,
    label: `Section ${sections.value.length + 1}`,
    fields: [],
    photos: [],
  });
}

function loadVersion(v: any) {
  const schema = v.schema_json || { sections: [] };
  sections.value = (schema.sections || []).map((s: any, idx: number) => ({
    id: idx + 1,
    key: s.key,
    label: s.label,
    fields: (s.fields || []).map((f: any, fid: number) => ({
      id: fid + 1,
      name: f.key,
      label: f.label,
      typeKey: f.type,
      cols: f['x-cols'] || 2,
      validations: f.validations || {},
      fields: f.fields || undefined,
    })),
    photos: [],
  }));
  statuses.value = Object.keys(v.statuses || {});
  if (Array.isArray(v.status_flow_json)) {
    statusFlow.value = v.status_flow_json;
  } else if (v.status_flow_json) {
    statusFlow.value = Object.entries(v.status_flow_json).flatMap(([from, tos]: any) =>
      (tos as string[]).map((to) => [from, to])
    );
  } else {
    statusFlow.value = [];
  }
}

function onVersionChange() {
  const v = versions.value.find((vv) => vv.id === selectedVersionId.value);
  if (v) {
    loadVersion(v);
  }
}

function removeSection(index: number) {
  sections.value.splice(index, 1);
  selected.value = null;
}

function onAddField(type: any) {
  if (!sections.value.length) addSection();
  const section = sections.value[sections.value.length - 1];
  section.fields.push({
    id: Date.now() + Math.random(),
    name: `field${section.fields.length + 1}`,
    label: type.label,
    typeKey: type.key,
    cols: 2,
    validations: {},
    fields: type.key === 'repeater' ? [] : undefined,
  });
}

function selectField(field: Field) {
  selected.value = field;
}

function onSubmit() {
  const payload = {
    name: name.value,
    tenant_id: tenantId.value || undefined,
    schema_json: JSON.stringify({
      sections: sections.value.map((s) => ({
        key: s.key,
        label: s.label,
        fields: s.fields.map((f) => ({
          key: f.name,
          label: f.label,
          type: f.typeKey,
          validations: f.validations,
          'x-cols': f.cols,
        })),
      })),
    }),
    statuses: JSON.stringify(statuses.value.reduce((acc: any, s) => ({ ...acc, [s]: [] }), {})),
    status_flow_json: JSON.stringify(statusFlow.value),
  };
  if (isEdit.value) {
    api.patch(`/task-types/${route.params.id}`, payload).then(() => router.push({ name: 'taskTypes.list' }));
  } else {
    api.post('/task-types', payload).then(() => router.push({ name: 'taskTypes.list' }));
  }
}

const previewSchema = computed(() => ({
  sections: sections.value.map((s) => ({
    key: s.key,
    label: s.label,
    fields: s.fields.map((f) => ({ key: f.name, label: f.label, type: f.typeKey, validations: f.validations })),
  })),
}));
</script>
