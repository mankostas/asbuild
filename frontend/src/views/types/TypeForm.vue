<template>
  <div v-if="canAccess" class="type-builder">
    <form @submit.prevent="onSubmit">
      <header class="builder-header flex items-center justify-between px-4 py-2 shadow">
        <h1 class="text-lg font-semibold">{{ isEdit ? t('routes.taskTypeEdit') : t('routes.taskTypeCreate') }}</h1>
        <div class="flex items-center gap-3">
          <div>
            <select
              v-if="versions.length"
              id="versionSelect"
              v-model="selectedVersionId"
              class="text-xs px-2 py-1 border rounded"
              :aria-label="t('Version')"
              @change="onVersionChange"
            >
              <option v-for="v in versions" :key="v.id" :value="v.id">v{{ v.semver }}</option>
            </select>
            <span v-else class="text-xs px-2 py-1 border rounded" :aria-label="t('Version')">v1</span>
          </div>
          <label class="flex items-center gap-1" for="previewToggle">
            <input
              id="previewToggle"
              v-model="showPreview"
              type="checkbox"
              :aria-label="t('preview.title')"
            />
            <span>{{ t('preview.title') }}</span>
          </label>
          <button type="submit" class="px-3 py-1 bg-indigo-600 text-white rounded" aria-label="Save">{{ t('Save') }}</button>
        </div>
      </header>
      <WorkflowDesigner
        v-model="statusFlow"
        v-model:statuses="statuses"
        class="p-4 border-b"
      />
      <SLAPolicyEditor
        v-if="isEdit"
        :task-type-id="Number(route.params.id)"
        class="p-4 border-b"
      />
      <AutomationsEditor
        v-if="isEdit"
        :task-type-id="Number(route.params.id)"
        class="p-4 border-b"
      />
      <TypeAbilitiesEditor v-model="abilities" class="p-4 border-b" />
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
          <p id="reorderHint" class="sr-only">{{ t('fields.reorderHint') }}</p>
          <div aria-describedby="reorderHint">
            <draggable v-model="sections" item-key="id" handle=".handle" class="space-y-4">
              <template #item="{ element, index }">
                <CanvasSection :section="element" @remove="removeSection(index)" @select="selectField" />
              </template>
            </draggable>
          </div>
        </main>
        <aside class="w-1/4 border-l overflow-y-auto p-4">
          <InspectorTabs :selected="selected" />
        </aside>
      </div>
      <div v-if="showPreview" class="builder-preview p-4 border-t">
          <div class="flex items-center gap-2 mb-2">
            <select
              id="previewLang"
              v-model="previewLang"
              class="text-xs px-2 py-1 border rounded"
              :aria-label="t('preview.language')"
            >
              <option value="el">EL</option>
              <option value="en">EN</option>
            </select>
            <select
              id="previewTheme"
              v-model="previewTheme"
              class="text-xs px-2 py-1 border rounded"
              :aria-label="t('preview.theme')"
            >
              <option value="light">{{ t('preview.light') }}</option>
              <option value="dark">{{ t('preview.dark') }}</option>
            </select>
            <select
              id="previewViewport"
              v-model="previewViewport"
              class="text-xs px-2 py-1 border rounded"
              :aria-label="t('preview.viewport')"
            >
              <option value="mobile">{{ t('preview.mobile') }}</option>
              <option value="tablet">{{ t('preview.tablet') }}</option>
              <option value="desktop">{{ t('preview.desktop') }}</option>
            </select>
            <button
              type="button"
              class="px-2 py-1 bg-indigo-600 text-white rounded"
              :aria-label="t('preview.runValidation')"
              @click="runValidation"
            >
              {{ t('preview.runValidation') }}
            </button>
          </div>
          <div :class="[{ dark: previewTheme === 'dark' }, viewportClass]" class="border p-2 overflow-auto">
            <JsonSchemaForm ref="formRef" v-model="previewData" :schema="previewSchema" :task-id="0" />
          </div>
          <div v-if="Object.keys(validationErrors).length" class="mt-2 text-red-600" role="alert" aria-live="assertive">
            <ul>
              <li v-for="(msg, key) in validationErrors" :key="key">{{ key }}: {{ msg }}</li>
            </ul>
          </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';
import FieldPalette from '@/components/types/FieldPalette.vue';
import CanvasSection from '@/components/types/CanvasSection.vue';
import InspectorTabs from '@/components/types/Inspector/InspectorTabs.vue';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';
import WorkflowDesigner from '@/components/types/WorkflowDesigner.vue';
import SLAPolicyEditor from '@/components/types/SLAPolicyEditor.vue';
import AutomationsEditor from '@/components/types/AutomationsEditor.vue';
import TypeAbilitiesEditor from '@/components/types/TypeAbilitiesEditor.vue';
import { can } from '@/stores/auth';
import api from '@/services/api';
import { useTaskTypeVersionsStore } from '@/stores/taskTypeVersions';
import '@/styles/types-builder.css';
import type { I18nString } from '@/utils/i18n';

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();
const versionsStore = useTaskTypeVersionsStore();

interface Field {
  id: number;
  name: string;
  label: I18nString;
  typeKey: string;
  cols: number;
  fields?: Field[];
  placeholder?: I18nString;
  help?: I18nString;
  validations: Record<string, any>;
  logic: any[];
  roles: { view: string[]; edit: string[] };
  data: { default: any; enum: string[] };
}

interface Section {
  id: number;
  key: string;
  label: I18nString;
  fields: Field[];
  photos: any[];
}

const name = ref('');
const tenantId = ref<number | ''>('');
const sections = ref<Section[]>([]);
const selected = ref<Field | null>(null);
const showPreview = ref(false);
const previewData = ref<Record<string, any>>({});
const previewLang = ref<'el' | 'en'>('el');
const previewTheme = ref<'light' | 'dark'>('light');
const previewViewport = ref<'mobile' | 'tablet' | 'desktop'>('desktop');
const validationErrors = ref<Record<string, string>>({});
const formRef = ref<any>(null);
const versions = ref<any[]>([]);
const selectedVersionId = ref<number | null>(null);
const statuses = ref<string[]>([]);
const statusFlow = ref<[string, string][]>([]);
const abilities = ref({
  read: true,
  edit: true,
  delete: true,
  export: true,
  assign: true,
  transition: true,
});

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

watch(previewLang, (lang) => {
  locale.value = lang;
});

const viewportClass = computed(() => {
  switch (previewViewport.value) {
    case 'mobile':
      return 'max-w-xs';
    case 'tablet':
      return 'max-w-md';
    default:
      return 'w-full';
  }
});

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
    label: { en: `Section ${sections.value.length + 1}`, el: `Section ${sections.value.length + 1}` },
    fields: [],
    photos: [],
  });
}

function loadVersion(v: any) {
  const schema = v.schema_json || { sections: [] };
  sections.value = (schema.sections || []).map((s: any, idx: number) => ({
    id: idx + 1,
    key: s.key,
    label: typeof s.label === 'string' ? { en: s.label, el: s.label } : s.label,
    fields: (s.fields || []).map((f: any, fid: number) => ({
      id: fid + 1,
      name: f.key,
      label: typeof f.label === 'string' ? { en: f.label, el: f.label } : f.label,
      typeKey: f.type,
      cols: f['x-cols'] || 2,
      validations: f.validations || {},
      fields: f.fields || undefined,
      placeholder: typeof f.placeholder === 'string' ? { en: f.placeholder, el: f.placeholder } : (f.placeholder || { en: '', el: '' }),
      help: typeof f.help === 'string' ? { en: f.help, el: f.help } : (f.help || { en: '', el: '' }),
      logic: [],
      roles: f['x-roles'] || { view: [], edit: [] },
      data: { default: f.default ?? '', enum: f.enum || [] },
    })),
    photos: [],
  }));
  const fieldMap: Record<string, any> = {};
  sections.value.forEach((s) => s.fields.forEach((f) => (fieldMap[f.name] = f)));
  (schema.logic || []).forEach((rule: any) => {
    const fld = fieldMap[rule.if?.field];
    if (fld) {
      fld.logic.push({
        if: rule.if,
        then: (rule.then || []).map((a: any) => ({
          type: a.show ? 'show' : 'require',
          target: a.show ?? a.require,
        })),
      });
    }
  });
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
  abilities.value = {
    read: true,
    edit: true,
    delete: true,
    export: true,
    assign: true,
    transition: true,
    ...(v.abilities_json || {}),
  };
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
    label: { en: type.label, el: type.label },
    typeKey: type.key,
    cols: 2,
    validations: {},
    fields: type.key === 'repeater' ? [] : undefined,
    placeholder: { en: '', el: '' },
    help: { en: '', el: '' },
    logic: [],
    roles: { view: [], edit: [] },
    data: { default: '', enum: [] },
  });
}

function selectField(field: Field) {
  selected.value = field;
}

function onSubmit() {
  const logicRules = sections.value.flatMap((s) =>
    s.fields.flatMap((f) =>
      (f.logic || []).map((r) => ({
        if: r.if,
        then: r.then.map((a: any) => ({ [a.type]: a.target })),
      })),
    ),
  );
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
          placeholder: f.placeholder,
          help: f.help,
          fields: f.fields,
          default: f.data.default || undefined,
          enum: f.data.enum.length ? f.data.enum : undefined,
          'x-roles': f.roles,
        })),
      })),
      ...(logicRules.length ? { logic: logicRules } : {}),
    }),
    statuses: JSON.stringify(statuses.value.reduce((acc: any, s) => ({ ...acc, [s]: [] }), {})),
    status_flow_json: JSON.stringify(statusFlow.value),
    abilities_json: JSON.stringify(abilities.value),
  };
  if (isEdit.value) {
    api.patch(`/task-types/${route.params.id}`, payload).then(() => router.push({ name: 'taskTypes.list' }));
  } else {
    api.post('/task-types', payload).then(() => router.push({ name: 'taskTypes.list' }));
  }
}

function runValidation() {
  validationErrors.value = {};
  const feErrors = formRef.value?.errors || {};
  if (Object.keys(feErrors).length) {
    validationErrors.value = feErrors;
    return;
  }
  api
    .post(`/task-types/${route.params.id}/validate`, {
      schema_json: previewSchema.value,
      form_data: previewData.value,
    })
    .catch((err) => {
      validationErrors.value = err.response?.data?.errors || { error: 'validation failed' };
    });
}

const previewSchema = computed(() => ({
  sections: sections.value.map((s) => ({
    key: s.key,
    label: s.label,
    fields: s.fields.map((f) => ({
      key: f.name,
      label: f.label,
      type: f.typeKey,
      validations: f.validations,
      placeholder: f.placeholder,
      help: f.help,
      fields: f.fields,
      default: f.data.default || undefined,
      enum: f.data.enum.length ? f.data.enum : undefined,
      'x-roles': f.roles,
    })),
  })),
  ...(sections.value
    .flatMap((s) =>
      s.fields.flatMap((f) =>
        (f.logic || []).map((r) => ({
          if: r.if,
          then: r.then.map((a: any) => ({ [a.type]: a.target })),
        })),
      ),
    ).length
    ? {
        logic: sections.value.flatMap((s) =>
          s.fields.flatMap((f) =>
            (f.logic || []).map((r) => ({
              if: r.if,
              then: r.then.map((a: any) => ({ [a.type]: a.target })),
            })),
          ),
        ),
      }
    : {}),
}));
</script>
