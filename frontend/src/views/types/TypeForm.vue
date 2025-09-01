<template>
  <div v-if="canAccess" class="type-builder">
    <form @submit.prevent="onSubmit">
      <header
        v-if="tenantId || !isCreate"
        class="builder-header flex items-center justify-between px-4 py-2 shadow bg-white"
      >
        <Breadcrumbs />
        <div class="flex items-center gap-3">
          <Select
            v-if="versions.length"
            id="versionSelect"
            v-model="selectedVersionId"
            :options="versions.map((v) => ({ value: v.id, label: `v${v.semver}` }))"
            :label="t('Version')"
            class="w-24"
            classLabel="sr-only"
            classInput="text-xs"
            @change="onVersionChange"
          />
          <span
            v-else
            class="text-xs px-2 py-1 border rounded"
            :aria-label="t('Version')"
          >
            {{ t('version.fallback', { n: 1 }) }}
          </span>
          <Badge
            v-if="currentVersion"
            :label="t(`versionStatus.${versionStatusLabel}`)"
            :badgeClass="versionStatusClass"
          />
          <Select
            id="localeSelect"
            v-model="previewLang"
            :options="[
              { value: 'el', label: 'EL' },
              { value: 'en', label: 'EN' },
            ]"
            :label="t('preview.language')"
            class="w-24"
            classLabel="sr-only"
            classInput="text-xs"
          />
          <Select
            id="previewTheme"
            v-model="previewTheme"
            :options="[
              { value: 'light', label: t('preview.light') },
              { value: 'dark', label: t('preview.dark') },
            ]"
            :label="t('preview.theme')"
            class="w-28"
            classLabel="sr-only"
            classInput="text-xs"
          />
          <Select
            id="previewViewport"
            v-model="previewViewport"
            :options="[
              { value: 'mobile', label: t('preview.mobile') },
              { value: 'tablet', label: t('preview.tablet') },
              { value: 'desktop', label: t('preview.desktop') },
            ]"
            :label="t('preview.viewport')"
            class="w-28"
            classLabel="sr-only"
            classInput="text-xs"
          />
          <Button
            v-if="
              isEdit &&
              (auth.isSuperAdmin || (can('task_types.manage') && can('task_type_versions.manage')))
            "
            type="button"
            :aria-label="t('actions.duplicate')"
            btnClass="btn-outline-primary text-xs px-3 py-1"
            @click="duplicateVersion"
          >
            {{ t('actions.duplicate') }}
          </Button>
          <Button
            v-if="
              isEdit &&
              (auth.isSuperAdmin || (can('task_types.manage') && can('task_type_versions.manage')))
            "
            type="button"
            :aria-label="t('actions.publish')"
            btnClass="btn-outline-primary text-xs px-3 py-1"
            @click="publishVersion"
          >
            {{ t('actions.publish') }}
          </Button>
          <Button
            v-if="
              isEdit &&
              (auth.isSuperAdmin || (can('task_types.manage') && can('task_type_versions.manage')))
            "
            type="button"
            :aria-label="t('actions.delete')"
            btnClass="btn-outline-danger text-xs px-3 py-1"
            @click="deleteVersion"
          >
            {{ t('actions.delete') }}
          </Button>
          <Button
            v-if="auth.isSuperAdmin || can('task_types.manage')"
            type="submit"
            :aria-label="t('actions.save')"
            btnClass="btn-primary text-xs px-3 py-1"
          >
            {{ t('actions.save') }}
          </Button>
        </div>
      </header>
      <TypeMetaBar
        v-model:name="name"
        v-model:search="search"
        v-model:tenant-id="tenantId"
        :tenant-options="tenantOptions"
        class="border-b mb-4"
      />
      <template v-if="tenantId || !isCreate">
        <StatusesEditor
          v-model="statuses"
          :tenant-id="tenantId"
          class="p-4 border-b"
        />
        <TransitionsEditor
          ref="transitionsEditor"
          v-model="statusFlow"
          :statuses="statuses"
          :tenant-id="tenantId"
          class="p-4 border-b"
        />
        <template v-if="canManageSLA">
          <SLAPolicyEditor
            ref="slaPolicyEditor"
            :task-type-id="taskTypeId"
            class="p-4 border-b"
          />
        </template>
        <template v-else>
          <Card class="p-4 border-b flex flex-col items-center text-center gap-2">
            <Icon
              icon="heroicons-outline:information-circle"
              class="w-6 h-6 text-slate-400"
              aria-hidden="true"
            />
            <p class="text-sm">{{ t('types.noSLAPermissions') }}</p>
          </Card>
        </template>
        <template v-if="canManageAutomations">
          <AutomationsEditor
            ref="automationsEditor"
            :task-type-id="taskTypeId"
            :tenant-id="tenantId"
            :statuses="statuses"
            class="p-4 border-b"
          />
        </template>
        <template v-else>
          <Card class="p-4 border-b flex flex-col items-center text-center gap-2">
            <Icon
              icon="heroicons-outline:information-circle"
              class="w-6 h-6 text-slate-400"
              aria-hidden="true"
            />
            <p class="text-sm">{{ t('types.noAutomationsPermissions') }}</p>
          </Card>
        </template>
        <Card
          v-if="!tenantId"
          class="p-4 border-b flex flex-col items-center text-center gap-2"
        >
          <Icon
            icon="heroicons-outline:information-circle"
            class="w-6 h-6 text-slate-400"
            aria-hidden="true"
          />
          <p class="text-sm">{{ t('types.selectTenantToSetPermissions') }}</p>
        </Card>
        <PermissionsMatrix
          v-else
          :key="`perm-${tenantId}`"
          v-model="permissions"
          :roles="tenantRoles"
          :can-manage="canManage"
          :status-count="statuses.length"
          class="p-4 border-b"
        />
      </template>
      <Card
        v-else
        class="p-4 border-b flex items-center gap-2 text-sm"
        role="alert"
        aria-live="polite"
      >
        <Icon
          icon="heroicons-outline:information-circle"
          class="w-5 h-5 text-slate-400"
          aria-hidden="true"
        />
        <p>
          {{
            locale === 'el'
              ? 'Επιλέξτε μισθωτή για να συνεχίσετε τη ρύθμιση ρόλων και δικαιωμάτων.'
              : 'Select a tenant to continue configuring roles and permissions.'
          }}
        </p>
      </Card>
      <div class="h-[calc(100vh-3rem)] p-4">
        <div class="hidden lg:grid grid-cols-3 gap-4 h-full">
          <Card class="overflow-y-auto">
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium">{{ t('builder.canvas') }}</h3>
                <Dropdown v-if="auth.isSuperAdmin || can('task_types.manage')">
                  <template #default>
                    <Button
                      type="button"
                      btnClass="btn-primary text-xs items-center px-2 py-1"
                      :aria-label="t('actions.add')"
                    >
                      <span class="inline-flex items-center gap-1">
                        {{ t('actions.add') }}
                        <Icon icon="heroicons-outline:chevron-down" />
                      </span>
                    </Button>
                  </template>
                  <template #menus>
                    <MenuItem #default="{ active }">
                      <button type="button" :class="menuItemClass(active)" @click="addSection()">
                        {{ t('actions.addSection') }}
                      </button>
                    </MenuItem>
                    <MenuItem #default="{ active }">
                      <button type="button" :class="menuItemClass(active)" @click="openPalette()">
                        {{ t('actions.addField') }}
                      </button>
                    </MenuItem>
                  </template>
                </Dropdown>
              </div>
            </template>
            <p id="reorderHint" class="sr-only">{{ t('fields.reorderHint') }}</p>
            <div aria-describedby="reorderHint" class="p-4">
              <draggable v-model="sections" item-key="id" handle=".handle" class="space-y-4">
                <template #item="{ element, index }">
                  <CanvasSection
                    v-if="visibleSections.includes(element)"
                    :section="element"
                    @remove="removeSection(index)"
                    @select="selectField"
                    @add-field="openPalette(index)"
                    @add-section="addSection(index)"
                    @remove-field="removeField(index, $event)"
                  />
                </template>
              </draggable>
            </div>
          </Card>
          <Card class="overflow-y-auto">
            <template #header>
              <h3 class="text-sm font-medium">{{ t('builder.preview') }}</h3>
            </template>
            <div class="p-4">
              <div class="flex items-center gap-2 mb-2">
                <Button
                  type="button"
                  :aria-label="t('preview.runValidation')"
                  btnClass="btn-primary text-xs px-2 py-1"
                  @click="runValidation"
                >
                  {{ t('preview.runValidation') }}
                </Button>
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
          </Card>
          <Card class="overflow-y-auto">
            <template #header>
              <h3 class="text-sm font-medium">{{ t('builder.inspector') }}</h3>
            </template>
            <div class="p-4">
              <InspectorTabs :key="`insp-${tenantId}`" :selected="selected" :role-options="tenantRoles" />
            </div>
          </Card>
        </div>
        <div class="lg:hidden">
          <UiTabs>
            <template #list>
              <Tab as="button" class="px-3 py-2 text-sm">{{ t('builder.canvas') }}</Tab>
              <Tab as="button" class="px-3 py-2 text-sm">{{ t('builder.preview') }}</Tab>
              <Tab as="button" class="px-3 py-2 text-sm">{{ t('builder.inspector') }}</Tab>
            </template>
            <template #panel>
              <TabPanel>
                <div class="mt-4">
                  <Dropdown
                    v-if="auth.isSuperAdmin || can('task_types.manage')"
                    class="mb-4"
                  >
                    <template #default>
                      <Button
                        type="button"
                        btnClass="btn-primary text-xs items-center px-2 py-1"
                        :aria-label="t('actions.add')"
                      >
                        <span class="inline-flex items-center gap-1">
                          {{ t('actions.add') }}
                          <Icon icon="heroicons-outline:chevron-down" />
                        </span>
                      </Button>
                    </template>
                    <template #menus>
                      <MenuItem #default="{ active }">
                        <button type="button" :class="menuItemClass(active)" @click="addSection()">
                          {{ t('actions.addSection') }}
                        </button>
                      </MenuItem>
                      <MenuItem #default="{ active }">
                        <button type="button" :class="menuItemClass(active)" @click="openPalette()">
                          {{ t('actions.addField') }}
                        </button>
                      </MenuItem>
                    </template>
                  </Dropdown>
                  <p id="reorderHintMobile" class="sr-only">{{ t('fields.reorderHint') }}</p>
                  <div aria-describedby="reorderHintMobile">
                    <draggable v-model="sections" item-key="id" handle=".handle" class="space-y-4">
                      <template #item="{ element, index }">
                        <CanvasSection
                          v-if="visibleSections.includes(element)"
                          :section="element"
                          @remove="removeSection(index)"
                          @select="selectField"
                          @add-field="openPalette(index)"
                          @add-section="addSection(index)"
                          @remove-field="removeField(index, $event)"
                        />
                      </template>
                    </draggable>
                  </div>
                </div>
              </TabPanel>
              <TabPanel>
                <div class="p-2">
                  <div class="flex items-center gap-2 mb-2">
                    <Button
                      type="button"
                      :aria-label="t('preview.runValidation')"
                      btnClass="btn-primary text-xs px-2 py-1"
                      @click="runValidation"
                    >
                      {{ t('preview.runValidation') }}
                    </Button>
                  </div>
                  <div :class="[{ dark: previewTheme === 'dark' }, viewportClass]" class="border p-2 overflow-auto">
                    <JsonSchemaForm ref="formRef" v-model="previewData" :schema="previewSchema" :task-id="0" />
                  </div>
                </div>
              </TabPanel>
              <TabPanel>
                <div class="p-2">
                  <InspectorTabs :key="`insp-${tenantId}`" :selected="selected" :role-options="tenantRoles" />
                </div>
              </TabPanel>
            </template>
          </UiTabs>
        </div>
      </div>
    </form>
    <Drawer :open="paletteOpen" @close="paletteOpen = false">
      <FieldPalette :groups="fieldTypeGroups" @select="onSelectType" />
    </Drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';
import CanvasSection from '@/components/types/CanvasSection.vue';
import InspectorTabs from '@/components/types/Inspector/InspectorTabs.vue';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';
import StatusesEditor from '@/components/types/StatusesEditor.vue';
import TransitionsEditor from '@/components/types/TransitionsEditor.vue';
import SLAPolicyEditor from '@/components/types/SLAPolicyEditor.vue';
import AutomationsEditor from '@/components/types/AutomationsEditor.vue';
import PermissionsMatrix from '@/components/types/PermissionsMatrix.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Select from '@/components/ui/Select/index.vue';
import Badge from '@/components/ui/Badge/index.vue';
import Card from '@/components/ui/Card/index.vue';
import UiTabs from '@/components/ui/Tabs/index.vue';
import Drawer from '@/components/ui/Drawer/index.vue';
import FieldPalette from '@/components/types/FieldPalette.vue';
import TypeMetaBar from '@/components/types/TypeMetaBar.vue';
import Dropdown from '@/components/ui/Dropdown/index.vue';
import Icon from '@/components/ui/Icon/index.vue';
import { Tab, TabPanel, MenuItem } from '@headlessui/vue';
import { can, useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { useTaskTypeVersionsStore } from '@/stores/taskTypeVersions';
import { useTenantStore } from '@/stores/tenant';
import '@/styles/types-builder.css';
import { type I18nString } from '@/utils/i18n';
import Swal from 'sweetalert2';

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();
const versionsStore = useTaskTypeVersionsStore();
const tenantStore = useTenantStore();
const auth = useAuthStore();
const taskTypeId = computed(() => Number(route.params.id ?? 0));

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

interface Permission {
  read: boolean;
  edit: boolean;
  delete: boolean;
  export: boolean;
  assign: boolean;
  transition: boolean;
}

const name = ref('');
const search = ref('');
const tenantId = ref<number | ''>('');
const transitionsEditor = ref<any>(null);
const automationsEditor = ref<any>(null);
const slaPolicyEditor = ref<any>(null);
const sections = ref<Section[]>([]);
const selected = ref<Field | null>(null);
const previewData = ref<Record<string, any>>({});
const previewLang = ref<'el' | 'en'>('el');
const previewTheme = ref<'light' | 'dark'>((localStorage.getItem('builderPreviewTheme') as 'light' | 'dark') || 'light');
const previewViewport = ref<'mobile' | 'tablet' | 'desktop'>((localStorage.getItem('builderPreviewViewport') as 'mobile' | 'tablet' | 'desktop') || 'desktop');
const validationErrors = ref<Record<string, string>>({});
const formRef = ref<any>(null);
const versions = ref<any[]>([]);
const selectedVersionId = ref<number | null>(null);
const currentVersion = computed(() =>
  versions.value.find((v) => v.id === selectedVersionId.value) || null,
);
const versionStatusLabel = computed(() => {
  if (!currentVersion.value) return '';
  if (currentVersion.value.deprecated_at) return 'deprecated';
  if (currentVersion.value.published_at) return 'published';
  return 'draft';
});
const versionStatusClass = computed(() => {
  switch (versionStatusLabel.value) {
    case 'published':
      return 'bg-success-500 text-white';
    case 'deprecated':
      return 'bg-danger-500 text-white';
    default:
      return 'bg-warning-500 text-white';
  }
});
const statuses = ref<string[]>([]);
const statusFlow = ref<[string, string][]>([]);
const permissions = ref<Record<string, Permission>>({});
const tenantRoles = ref<any[]>([]);
const canManage = computed(() => auth.isSuperAdmin || can('task_types.manage'));
const canManageSLA = computed(
  () => auth.isSuperAdmin || can('task_sla_policies.manage'),
);
const canManageAutomations = computed(
  () => auth.isSuperAdmin || can('task_automations.manage'),
);
const isFormValid = computed(() => name.value.trim().length > 0 && tenantId.value !== '');

const fieldTypes = [
  { key: 'text', label: 'Text', group: 'Inputs' },
  { key: 'number', label: 'Number', group: 'Inputs' },
  { key: 'date', label: 'Date', group: 'Dates' },
  { key: 'time', label: 'Time', group: 'Dates' },
  { key: 'boolean', label: 'Checkbox', group: 'Choices' },
  { key: 'assignee', label: 'Assignee', group: 'People' },
  { key: 'repeater', label: 'Repeater', group: 'Content' },
];

const paletteOpen = ref(false);
const paletteSectionIndex = ref<number | null>(null);
const fieldTypeGroups = computed(() => {
  const groups: Record<string, { label: string; items: any[] }> = {};
  fieldTypes.forEach((ft) => {
    if (!groups[ft.group]) groups[ft.group] = { label: ft.group, items: [] };
    groups[ft.group].items.push(ft);
  });
  return Object.values(groups);
});

const tenants = computed(() => tenantStore.tenants);
const tenantOptions = computed(() =>
  tenants.value.map((t) => ({ value: t.id, label: t.name }))
);
const visibleSections = computed(() => sections.value);

function openPalette(index?: number) {
  paletteSectionIndex.value =
    typeof index === 'number' ? index : sections.value.length - 1;
  paletteOpen.value = true;
}

watch(search, async (q) => {
  if (q.length >= 3) {
    await tenantStore.loadTenants({ per_page: 100, search: q });
  } else if (q.length === 0) {
    await tenantStore.loadTenants({ per_page: 100 });
  }
});

const isEdit = computed(() => route.name === 'taskTypes.edit');
const isCreate = computed(
  () => route.name === 'taskTypes.create' && auth.isSuperAdmin,
);
const canAccess = computed(
  () =>
    auth.isSuperAdmin ||
    (can('task_types.manage') &&
      (isEdit.value ? can('task_types.view') : can('task_types.create'))),
);

watch(previewLang, (lang) => {
  locale.value = lang;
});

watch(previewTheme, (theme) => {
  localStorage.setItem('builderPreviewTheme', theme);
});

watch(previewViewport, (viewport) => {
  localStorage.setItem('builderPreviewViewport', viewport);
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
  await tenantStore.loadTenants({ per_page: 100 });
  if (isEdit.value) {
    const { data: typeData } = await api.get(`/task-types/${route.params.id}`);
    name.value = typeData.name || '';
    tenantId.value =
      typeData.tenant_id !== null && typeData.tenant_id !== undefined
        ? Number(typeData.tenant_id)
        : '';
    const list = await versionsStore.list(Number(route.params.id));
    versions.value = list;
    if (list.length) {
      selectedVersionId.value = list[0].id;
      loadVersion(list[0]);
    }
  } else if (tenantStore.tenantId) {
    tenantId.value = Number(tenantStore.tenantId);
  }
});

  watch(
    tenantId,
    async (id, oldId) => {
      const normalized = id ? String(id) : '';
      const prev = oldId ? String(oldId) : '';

      // Avoid triggering the watcher again when the value hasn't actually changed
      if (normalized === prev) return;

      if (tenantStore.tenantId !== normalized) {
        tenantStore.setTenant(normalized);
      }
      if (oldId !== undefined && id !== oldId) {
        sections.value.forEach((s) =>
          s.fields.forEach((f) => {
            f.roles.view = ['super_admin'];
            f.roles.edit = ['super_admin'];
          }),
        );
        statuses.value = [];
        statusFlow.value = [];
      }
      if (id) {
        try {
          const params: Record<string, any> = auth.isSuperAdmin
            ? { scope: 'all' }
            : { tenant_id: Number(id) };
          const { data } = await api.get('/roles', { params });
          let roles = data.data ?? data;
          if (auth.isSuperAdmin) {
            const tid = Number(id);
            roles = roles.filter((r: any) => r.tenant_id === null || r.tenant_id === tid);
          }
          tenantRoles.value = roles;
          tenantRoles.value.forEach((r: any) => {
            if (!permissions.value[r.slug]) {
              permissions.value[r.slug] = {
                read: false,
                edit: false,
                delete: false,
                export: false,
                assign: false,
                transition: false,
              };
            } else if (permissions.value[r.slug].transition === undefined) {
              permissions.value[r.slug].transition = false;
            }
          });
          const validSlugs = tenantRoles.value.map((r: any) => r.slug);
          if (selected.value) {
            selected.value.roles.view = selected.value.roles.view.filter((s: string) => validSlugs.includes(s));
            selected.value.roles.edit = selected.value.roles.edit.filter((s: string) => validSlugs.includes(s));
          }
        } catch {
          tenantRoles.value = [];
          permissions.value = {};
        }
      } else {
        tenantRoles.value = [];
        permissions.value = {};
        statuses.value = [];
        statusFlow.value = [];
      }
    },
    { immediate: true },
  );

function addSection(afterIndex?: number) {
  const newSection = {
    id: Date.now() + Math.random(),
    key: `section${sections.value.length + 1}`,
    label: { en: `Section ${sections.value.length + 1}`, el: `Section ${sections.value.length + 1}` },
    fields: [],
    photos: [],
  };
  if (
    afterIndex === undefined ||
    afterIndex < 0 ||
    afterIndex >= sections.value.length
  ) {
    sections.value.push(newSection);
  } else {
    sections.value.splice(afterIndex + 1, 0, newSection);
  }
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
      roles: f['x-roles'] || { view: ['super_admin'], edit: ['super_admin'] },
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
  permissions.value = { ...(v.abilities_json || {}) };
  tenantRoles.value.forEach((r) => {
    if (!permissions.value[r.slug]) {
      permissions.value[r.slug] = {
        read: false,
        edit: false,
        delete: false,
        export: false,
        assign: false,
        transition: false,
      };
    } else if (permissions.value[r.slug].transition === undefined) {
      permissions.value[r.slug].transition = false;
    }
  });
}

function onVersionChange() {
  const v = versions.value.find((vv) => vv.id === selectedVersionId.value);
  if (v) {
    loadVersion(v);
  }
}

async function duplicateVersion() {
  if (!route.params.id) return;
  await versionsStore.create(Number(route.params.id));
  versions.value = await versionsStore.list(Number(route.params.id));
  selectedVersionId.value = versions.value[0]?.id ?? null;
}

async function publishVersion() {
  if (!selectedVersionId.value) return;
  const result = await Swal.fire({
    title: 'Publish this version?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, publish',
  });
  if (!result.isConfirmed) return;
  await versionsStore.publish(selectedVersionId.value);
  versions.value = await versionsStore.list(Number(route.params.id));
}

async function deleteVersion() {
  if (!selectedVersionId.value) return;
  const result = await Swal.fire({
    title: 'Deprecate this version?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, deprecate',
  });
  if (!result.isConfirmed) return;
  await versionsStore.deprecate(selectedVersionId.value);
  versions.value = await versionsStore.list(Number(route.params.id));
  selectedVersionId.value = versions.value[0]?.id ?? null;
}

function removeSection(index: number) {
  sections.value.splice(index, 1);
  selected.value = null;
}

function removeField(sectionIndex: number, field: Field) {
  const fields = sections.value[sectionIndex].fields;
  const idx = fields.indexOf(field);
  if (idx !== -1) fields.splice(idx, 1);
  if (selected.value === field) selected.value = null;
}

function onAddField(type: any) {
  if (!sections.value.length) addSection();
  let target = paletteSectionIndex.value;
  if (
    target === null ||
    target < 0 ||
    target >= sections.value.length
  ) {
    target = sections.value.length - 1;
  }
  const section = sections.value[target];
  const field = {
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
    roles: { view: ['super_admin'], edit: ['super_admin'] },
    data: { default: '', enum: [] },
  };
  section.fields.push(field);
  selected.value = field;
  paletteSectionIndex.value = null;
}

function onSelectType(type: any) {
  onAddField(type);
  // Delay closing to prevent the click from propagating to the underlying
  // "Add Field" button which would immediately reopen the palette.
  setTimeout(() => {
    paletteOpen.value = false;
  }, 0);
}

function selectField(field: Field) {
  selected.value = field;
  paletteOpen.value = false;
}

async function onSubmit() {
  transitionsEditor.value?.commitPending?.();
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
    abilities_json: JSON.stringify(permissions.value),
  };
  if (isEdit.value) {
    await api.patch(`/task-types/${route.params.id}`, payload);
  } else {
    const res = await api.post('/task-types', payload);
    const newId = res.data?.data?.id ?? res.data?.id;
    const pendingAutomations =
      automationsEditor.value?.getAutomations?.().filter((a: any) => !a.id && a._saved) || [];
    const pendingPolicies =
      slaPolicyEditor.value?.getPolicies?.().filter((p: any) => !p.id && p._saved) || [];
    if (newId) {
      if (pendingAutomations.length) {
        await Promise.all(
          pendingAutomations.map((a: any) =>
            api.post(`/task-types/${newId}/automations`, {
              event: a.event,
              conditions_json: a.conditions_json,
              actions_json: a.actions_json,
              enabled: a.enabled,
            })
          )
        );
      }
      if (pendingPolicies.length) {
        await Promise.all(
          pendingPolicies.map((p: any) =>
            api.post(`/task-types/${newId}/sla-policies`, {
              priority: p.priority,
              response_within_mins: p.response_within_mins,
              resolve_within_mins: p.resolve_within_mins,
              calendar_json:
                p.useCalendar && p.calendar_json
                  ? JSON.parse(p.calendar_json)
                  : null,
            })
          )
        );
      }
    }
  }
  router.push({ name: 'taskTypes.list' });
}

function runValidation() {
  validationErrors.value = {};
  const feErrors = formRef.value?.errors || {};
  if (Object.keys(feErrors).length) {
    validationErrors.value = feErrors;
    const first = Object.keys(validationErrors.value)[0];
    if (first) {
      nextTick(() => document.getElementById(first)?.focus());
    }
    return;
  }
  const url = isEdit.value
    ? `/task-types/${route.params.id}/validate`
    : '/task-types/validate';
  api
    .post(url, {
      schema_json: previewSchema.value,
      form_data: previewData.value,
    })
    .catch((err) => {
      validationErrors.value = err.response?.data?.errors || { error: 'validation failed' };
      const first = Object.keys(validationErrors.value)[0];
      if (first) {
        nextTick(() => document.getElementById(first)?.focus());
      }
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

function menuItemClass(active: boolean) {
  return (
    (active
      ? 'bg-slate-100 dark:bg-slate-600 dark:bg-opacity-50'
      : 'text-slate-600 dark:text-slate-300') +
    ' block w-full text-left px-4 py-2'
  );
}
</script>
