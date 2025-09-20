<template>
  <div v-if="canAccess" ref="builder" class="type-builder">
    <div v-if="loading" class="space-y-4 p-4">
      <Skeleton class="h-10 w-1/3 rounded-2xl shadow" />
      <Skeleton class="h-24 w-full rounded-2xl shadow" />
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <Skeleton class="h-48 rounded-2xl shadow" />
        <Skeleton class="hidden h-48 rounded-2xl shadow lg:block" />
        <Skeleton class="hidden h-48 rounded-2xl shadow lg:block" />
      </div>
    </div>
    <form v-else @submit.prevent="onSubmit">
      <header
        v-if="tenantId || !isCreate"
        class="builder-header flex items-center justify-between px-4 py-2 shadow bg-white"
      >
        <Breadcrumbs />
        <div class="flex items-center gap-3">
          
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
        v-model:tenant-id="tenantId"
        v-model:client-id="clientId"
        v-model:require-subtasks-complete="requireSubtasksComplete"
        :show-tenant-select="auth.isSuperAdmin"
        :client-options="clientOptions"
        :loading-clients="clientsLoading"
        :tenant-name="selectedTenantName"
        class="border-b mb-4"
      />
      <template v-if="tenantId || !isCreate">
        <StatusesEditor
          v-model="statuses"
          :tenant-id="tenantId"
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
        <PermissionsMatrix
          v-if="tenantId && canViewRoles"
          :key="`perm-${tenantId}`"
          v-model="permissions"
          :roles="tenantRoles"
          :can-manage="canManage"
          :status-count="statuses.length"
          :features="tenantFeatures"
          :feature-abilities="tenantFeatureAbilities"
          class="p-4 border-b"
        />
        <Card
          v-else-if="!tenantId"
          class="p-4 border-b flex flex-col items-center text-center gap-2"
        >
          <Icon
            icon="heroicons-outline:information-circle"
            class="w-6 h-6 text-slate-400"
            aria-hidden="true"
          />
          <p class="text-sm">{{ t('types.selectTenantToSetPermissions') }}</p>
        </Card>
        <Card
          v-else
          class="p-4 border-b flex flex-col items-center text-center gap-2"
        >
          <Icon
            icon="heroicons-outline:information-circle"
            class="w-6 h-6 text-slate-400"
            aria-hidden="true"
          />
          <p class="text-sm">{{ t('types.noRolesPermissions') }}</p>
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
                    @add-field="(tab) => openPalette(index, tab)"
                    @add-section="addSection(index)"
                    @remove-field="(payload) => removeField(index, payload)"
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
              <div class="bg-slate-900/50 p-4 flex items-center justify-center">
                <div
                  :class="[{ dark: previewTheme === 'dark' }, viewportClass]"
                  class="bg-white dark:bg-slate-800 rounded-md shadow p-4 overflow-auto"
                >
                  <JsonSchemaForm
                    ref="formRef"
                    v-model="previewData"
                    :schema="previewSchema"
                    :task-id="'0'"
                  />
                </div>
              </div>
              <div
                v-if="validationStatus === 'error' && Object.keys(validationErrors).length"
                class="mt-2 text-red-600"
                role="alert"
                aria-live="assertive"
              >
                <ul>
                  <li v-for="(msg, key) in validationErrors" :key="key">{{ key }}: {{ msg }}</li>
                </ul>
              </div>
              <div
                v-else-if="validationStatus === 'success'"
                class="mt-2 text-green-600"
                role="status"
                aria-live="polite"
              >
                {{ t('preview.validationSuccess') }}
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
                          @add-field="(tab) => openPalette(index, tab)"
                          @add-section="addSection(index)"
                          @remove-field="(payload) => removeField(index, payload)"
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
                    <JsonSchemaForm ref="formRef" v-model="previewData" :schema="previewSchema" :task-id="'0'" />
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
      </form>
    <Drawer :open="paletteOpen" :lock-target="builder" @close="paletteOpen = false">
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
import StatusesEditor from '@/components/types/StatusesEditor';
import TransitionsEditor from '@/components/types/TransitionsEditor.vue';
import SLAPolicyEditor from '@/components/types/SLAPolicyEditor.vue';
import AutomationsEditor from '@/components/types/AutomationsEditor.vue';
import PermissionsMatrix from '@/components/types/PermissionsMatrix.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Select from '@/components/ui/Select/index.vue';
import Card from '@/components/ui/Card/index.vue';
import UiTabs from '@/components/ui/Tabs/index.vue';
import Drawer from '@/components/ui/Drawer/index.vue';
import FieldPalette from '@/components/types/FieldPalette.vue';
import TypeMetaBar from '@/components/types/TypeMetaBar.vue';
import Dropdown from '@/components/ui/Dropdown/index.vue';
import Icon from '@/components/ui/Icon/index.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { Tab, TabPanel, MenuItem } from '@headlessui/vue';
import { can, useAuthStore } from '@/stores/auth';
import hasAbility from '@/utils/ability';
import api from '@/services/api';
import { useTenantStore } from '@/stores/tenant';
import { useFeaturesStore } from '@/stores/features';
import '@/styles/types-builder.css';
import { type I18nString } from '@/utils/i18n';
import Swal from 'sweetalert2';

const { t, locale } = useI18n();
const builder = ref<HTMLElement | null>(null);
const route = useRoute();
const router = useRouter();
const tenantStore = useTenantStore();
const auth = useAuthStore();
const featuresStore = useFeaturesStore();
const taskTypeId = computed(() => {
  const candidate = route.params.id ?? route.query.id ?? '';
  if (Array.isArray(candidate)) {
    return candidate[0] ?? '';
  }
  return typeof candidate === 'string' ? candidate : '';
});
const loading = ref(true);

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
  styles: { fontSize: string; textColor: string; backgroundColor: string };
}

interface Section {
  id: number;
  key: string;
  label: I18nString;
  fields: Field[];
  photos: Field[];
  cols: number;
  tabs: SectionTab[];
}

interface SectionTab {
  id: number;
  key: string;
  label: I18nString;
  fields: Field[];
}

interface Permission {
  read: boolean;
  clientRead: boolean;
  edit: boolean;
  clientEdit: boolean;
  delete: boolean;
  export: boolean;
  assign: boolean;
  transition: boolean;
}

const PERMISSION_KEY_ALIASES: Record<string, keyof Permission> = {
  client_view: 'clientRead',
  client_read: 'clientRead',
  client_edit: 'clientEdit',
  client_update: 'clientEdit',
};

const emptyPermission = (): Permission => ({
  read: false,
  clientRead: false,
  edit: false,
  clientEdit: false,
  delete: false,
  export: false,
  assign: false,
  transition: false,
});

const normalizePermission = (value: Partial<Record<string, unknown>> | undefined): Permission => {
  const normalized = emptyPermission();
  Object.entries(value ?? {}).forEach(([key, raw]) => {
    const mappedKey = (PERMISSION_KEY_ALIASES[key] ?? key) as keyof Permission;
    if (mappedKey in normalized) {
      (normalized as any)[mappedKey] = !!raw;
    }
  });
  return normalized;
};

const name = ref('');
const tenantId = ref<string>('');
const clientId = ref<string>('');
const clientsLoading = ref(false);
const clientOptions = ref<{ value: string; label: string }[]>([]);
const requireSubtasksComplete = ref(false);
const tenantFeatures = computed(() => {
  const tenant = tenantStore.tenants.find(
    (t: any) => String(t.id) === String(tenantId.value),
  );
  return tenant?.features || [];
});
const tenantFeatureAbilities = computed(() => {
  const abilities = tenantStore.tenantAllowedAbilities(
    String(tenantId.value) || '',
  );
  return Object.keys(abilities).length ? abilities : undefined;
});
const selectedTenantName = computed(() => {
  if (!tenantId.value) {
    return t('types.form.global');
  }
  const tenant = tenantStore.tenants.find(
    (t: any) => String(t.id) === String(tenantId.value),
  );
  if (tenant?.name) {
    return tenant.name;
  }
  const userTenant = (auth.user as any)?.tenant?.name || (auth.user as any)?.tenant_name;
  return userTenant || '';
});
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
const validationStatus = ref<'idle' | 'success' | 'error'>('idle');
const formRef = ref<any>(null);
const currentVersion = ref<any | null>(null);
const statuses = ref<string[]>([]);
const statusFlow = ref<[string, string][]>([]);
const permissions = ref<Record<string, Permission>>({});
const tenantRoles = ref<{ id: string; slug: string }[]>([]);
const canViewRoles = computed(
  () => auth.isSuperAdmin || can('roles.view') || can('roles.manage'),
);
const canManage = computed(() => auth.isSuperAdmin || can('task_types.manage'));
const canManageSLA = computed(
  () => auth.isSuperAdmin || can('task_sla_policies.manage'),
);
const canManageAutomations = computed(
  () => auth.isSuperAdmin || (can('task_automations.manage') && can('teams.view')),
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
  { key: 'photo_single', label: t('fields.photo'), group: 'Photos' },
  { key: 'photo_repeater', label: t('fields.photoGallery'), group: 'Photos' },
];

const paletteOpen = ref(false);
const paletteSectionIndex = ref<{ section: number; tab?: number } | null>(null);
const fieldTypeGroups = computed(() => {
  const groups: Record<string, { label: string; items: any[] }> = {};
  fieldTypes.forEach((ft) => {
    if (!groups[ft.group]) groups[ft.group] = { label: ft.group, items: [] };
    groups[ft.group].items.push(ft);
  });
  return Object.values(groups);
});

const visibleSections = computed(() => sections.value);

function openPalette(sectionIndex?: number, tabIndex?: number) {
  const section =
    typeof sectionIndex === 'number' ? sectionIndex : sections.value.length - 1;
  const scrollY = window.scrollY;
  paletteSectionIndex.value = { section, tab: tabIndex };
  paletteOpen.value = true;
  nextTick(() => window.scrollTo({ top: scrollY }));
}

const isEdit = computed(
  () => route.name === 'taskTypes.edit' || !!(route.query.id || route.params.id),
);
const isCreate = computed(
  () => route.name === 'taskTypes.create' && auth.isSuperAdmin,
);
const canAccess = computed(() => {
  if (auth.isSuperAdmin) {
    return true;
  }

  if (hasAbility('task_types.manage')) {
    return true;
  }

  if (isEdit.value) {
    return hasAbility('task_types.update');
  }

  return hasAbility('task_types.create');
});

const skipTenantWatch = ref(isEdit.value);

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

async function refreshClientsForTenant(
  id: string,
  { resetSelection = false }: { resetSelection?: boolean } = {},
) {
  if (!canAccess.value) {
    return;
  }
  if (!id) {
    clientOptions.value = [];
    if (resetSelection) {
      clientId.value = '';
    }
    return;
  }
  clientsLoading.value = true;
  try {
    const { data } = await api.get('/clients', {
      params: { tenant_id: id, per_page: 100, sort: 'name', dir: 'asc' },
    });
    const payload = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
    clientOptions.value = (payload as any[])
      .filter((item) => item && item.id !== undefined)
      .map((client: any) => ({
        value: String(client.id),
        label: client.name || `#${client.id}`,
      }));

    const selectedClientId = clientId.value || null;
    if (selectedClientId !== null) {
      const exists = clientOptions.value.some(
        (option) => option.value === selectedClientId,
      );
      if (!exists) {
        if (resetSelection) {
          clientId.value = '';
        } else {
          const fallbackName =
            (String(currentVersion.value?.client?.id) === selectedClientId
              ? currentVersion.value?.client?.name
              : undefined) ||
            (payload as any[]).find(
              (client: any) => String(client?.id) === selectedClientId,
            )?.name ||
            `#${selectedClientId}`;
          clientOptions.value.push({ value: selectedClientId, label: fallbackName });
        }
      }
    } else if (resetSelection) {
      clientId.value = '';
    }
  } catch (_error) {
    clientOptions.value = [];
    if (resetSelection) {
      clientId.value = '';
    }
  } finally {
    clientsLoading.value = false;
  }
}

async function refreshTenant(id: string, oldId?: string) {
  if (!canAccess.value) return;
  const normalized = id ? String(id) : '';
  const prev = oldId ? String(oldId) : '';

  if (normalized === prev) return;

  if (tenantStore.tenantId !== normalized) {
    tenantStore.setTenant(normalized);
  }
  const tenantChanged = oldId !== undefined && id !== oldId;

  if (tenantChanged) {
    sections.value.forEach((s) =>
      sectionAllFields(s).forEach((f) => {
        f.roles.view = ['super_admin'];
        f.roles.edit = ['super_admin'];
      }),
    );
    if (id && !isEdit.value) {
      try {
        const { data } = await api.get('/task-statuses', {
          params: { scope: 'tenant', tenant_id: id, per_page: 100 },
        });
        const list = data.data ?? data;
        statuses.value = list.map((s: any) => s.slug);
      } catch {
        statuses.value = [];
      }
      statusFlow.value = [];
    } else {
      statuses.value = [];
      statusFlow.value = [];
    }
  }
  await refreshClientsForTenant(id, { resetSelection: tenantChanged });

  if (id) {
    if (canViewRoles.value) {
      try {
        const tenantParams: Record<string, any> = {
          tenant_id: id,
          per_page: 100,
        };
        const requests = [api.get('/roles', { params: tenantParams })];
        if (auth.isSuperAdmin) {
          requests.push(
            api.get('/roles', { params: { scope: 'global', per_page: 100 } }),
          );
        }
        const [tenantRes, globalRes] = await Promise.all(requests);
        const tenantData = tenantRes.data.data ?? tenantRes.data;
        tenantRoles.value = (tenantData as any[]).map((role: any) => ({
          ...role,
          id: String(role.id),
        }));
        if (globalRes) {
          const globalData = (globalRes as any).data.data ?? (globalRes as any).data;
          const merged = [
            ...tenantRoles.value,
            ...(globalData as any[]).map((role: any) => ({
              ...role,
              id: String(role.id),
            })),
          ];
          tenantRoles.value = merged.filter(
            (r, i, arr) => arr.findIndex((x) => x.slug === r.slug) === i,
          );
        }
        tenantRoles.value.forEach((r) => {
          if (!permissions.value[r.slug]) {
            permissions.value[r.slug] = emptyPermission();
          } else {
            permissions.value[r.slug] = normalizePermission(permissions.value[r.slug]);
          }
        });
        const validSlugs = tenantRoles.value.map((r) => r.slug);
        if (selected.value) {
          selected.value.roles.view = selected.value.roles.view.filter((s: string) =>
            validSlugs.includes(s)
          );
          selected.value.roles.edit = selected.value.roles.edit.filter((s: string) =>
            validSlugs.includes(s)
          );
        }
      } catch {
        tenantRoles.value = [];
        permissions.value = {};
      }
    } else {
      tenantRoles.value = [];
      permissions.value = {};
    }
  } else {
    tenantRoles.value = [];
    permissions.value = {};
    statuses.value = [];
    statusFlow.value = [];
  }
}

onMounted(async () => {
  loading.value = true;
  if (!canAccess.value) {
    loading.value = false;
    return;
  }
  try {
    const lookupsPromise = featuresStore.load();
    const typePromise = isEdit.value
      ? api.get(`/task-types/${taskTypeId.value}`)
      : Promise.resolve(null);

    let typeRes: any = null;
    if (auth.isSuperAdmin) {
      await tenantStore.loadTenants({ per_page: 100, scope: 'all' });
      typeRes = await typePromise;
    } else {
      skipTenantWatch.value = true;
      const userTenantId = (auth.user as any)?.tenant_id;
      tenantId.value = userTenantId != null ? String(userTenantId) : '';
      typeRes = await typePromise;
    }

    await lookupsPromise;

    if (isEdit.value && typeRes) {
      const typeData = typeRes.data.data ?? typeRes.data;
      currentVersion.value = typeData;
      name.value = typeData.name || '';
      tenantId.value =
        typeData.tenant_id !== null && typeData.tenant_id !== undefined
          ? String(typeData.tenant_id)
          : '';
      clientId.value =
        typeData.client_id !== null && typeData.client_id !== undefined
          ? String(typeData.client_id)
          : '';
      requireSubtasksComplete.value = !!typeData.require_subtasks_complete;
      await refreshTenant(tenantId.value);
      loadVersion(typeData);
    } else if (!auth.isSuperAdmin) {
      await refreshTenant(tenantId.value);
      tenantStore.setTenant(String(tenantId.value));
    } else {
      clientId.value = '';
      clientOptions.value = [];
      tenantStore.setTenant('');
    }
  } catch (err) {
    tenantStore.setTenant('');
  } finally {
    skipTenantWatch.value = false;
    loading.value = false;
  }
});

watch(tenantId, (id, oldId) => {
  if (skipTenantWatch.value) {
    skipTenantWatch.value = false;
    return;
  }
  refreshTenant(id, oldId);
});

function addSection(afterIndex?: number) {
  const newSection = {
    id: Date.now() + Math.random(),
    key: `section${sections.value.length + 1}`,
    label: { en: `Section ${sections.value.length + 1}`, el: `Section ${sections.value.length + 1}` },
    fields: [],
    photos: [],
    cols: 2,
    tabs: [],
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

function sectionFields(s: any) {
  return s.tabs && s.tabs.length
    ? s.tabs.flatMap((t: any) => t.fields)
    : s.fields;
}

function sectionAllFields(s: any) {
  return [...sectionFields(s), ...(s.photos || [])];
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
      placeholder:
        typeof f.placeholder === 'string'
          ? { en: f.placeholder, el: f.placeholder }
          : f.placeholder || { en: '', el: '' },
      help:
        typeof f.help === 'string'
          ? { en: f.help, el: f.help }
          : f.help || { en: '', el: '' },
      logic: [],
      roles: f['x-roles'] || { view: ['super_admin'], edit: ['super_admin'] },
      styles: f['x-styles'] || { fontSize: 'text-base', textColor: '#000000', backgroundColor: '#ffffff' },
      data: { default: f.default ?? '', enum: f.enum || [] },
    })),
    tabs: (s.tabs || []).map((t: any, tid: number) => ({
      id: tid + 1,
      key: t.key || `tab${tid + 1}`,
      label: typeof t.label === 'string' ? { en: t.label, el: t.label } : t.label,
      fields: (t.fields || []).map((f: any, fid: number) => ({
        id: fid + 1,
        name: f.key,
        label: typeof f.label === 'string' ? { en: f.label, el: f.label } : f.label,
        typeKey: f.type,
        cols: f['x-cols'] || 2,
        validations: f.validations || {},
        fields: f.fields || undefined,
        placeholder:
          typeof f.placeholder === 'string'
            ? { en: f.placeholder, el: f.placeholder }
            : f.placeholder || { en: '', el: '' },
        help:
          typeof f.help === 'string'
            ? { en: f.help, el: f.help }
            : f.help || { en: '', el: '' },
        logic: [],
        roles: f['x-roles'] || { view: ['super_admin'], edit: ['super_admin'] },
        styles: f['x-styles'] || { fontSize: 'text-base', textColor: '#000000', backgroundColor: '#ffffff' },
        data: { default: f.default ?? '', enum: f.enum || [] },
      })),
    })),
    cols: s['x-cols'] || 2,
    photos: (s.photos || []).map((p: any, pid: number) => ({
      id: pid + 1,
      name: p.key,
      label: typeof p.label === 'string' ? { en: p.label, el: p.label } : p.label,
      typeKey: p.type || 'photo_single',
      cols: 2,
      validations: p.validations || {},
      help:
        typeof p.help === 'string'
          ? { en: p.help, el: p.help }
          : p.help || { en: '', el: '' },
      data: { default: '', enum: [] },
      maxCount: p.maxCount,
      roles: p['x-roles'] || { view: ['super_admin'], edit: ['super_admin'] },
      styles: p['x-styles'] || { fontSize: 'text-base', textColor: '#000000', backgroundColor: '#ffffff' },
    })),
  }));
  const fieldMap: Record<string, any> = {};
  sections.value.forEach((s) =>
    sectionAllFields(s).forEach((f) => (fieldMap[f.name] = f)),
  );
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
  let flow = v.status_flow_json;
  if (typeof flow === 'string') {
    try {
      flow = JSON.parse(flow);
    } catch {
      flow = [];
    }
  }
  if (Array.isArray(flow)) {
    statusFlow.value = flow.map((e: any) =>
      Array.isArray(e) ? [e[0], e[1]] : [e.from, e.to],
    ) as [string, string][];
  } else if (flow && typeof flow === 'object') {
    statusFlow.value = Object.entries(flow).flatMap(([from, tos]: any) =>
      (tos as string[]).map((to) => [from, to] as [string, string]),
    );
  } else {
    statusFlow.value = [];
  }
  let abilities: Record<string, Permission> = {};
  try {
    abilities =
      typeof v.abilities_json === 'string'
        ? JSON.parse(v.abilities_json)
        : v.abilities_json || {};
  } catch {
    abilities = {};
  }
  // ensure all ability flags are boolean values
  const roleSlugMap = Object.fromEntries(
    tenantRoles.value.map((r: any) => [String(r.id), r.slug]),
  );
  permissions.value = Object.fromEntries(
    Object.entries(abilities).map(([role, perms]) => [
      roleSlugMap[role] || role,
      normalizePermission(perms as Record<string, unknown>),
    ]),
  );
  tenantRoles.value.forEach((r) => {
    if (!permissions.value[r.slug]) {
      permissions.value[r.slug] = emptyPermission();
    } else {
      permissions.value[r.slug] = normalizePermission(permissions.value[r.slug]);
    }
  });
  nextTick(() => {
    automationsEditor.value?.reload?.(tenantId.value || undefined);
    slaPolicyEditor.value?.reload?.();
  });
}


function removeSection(index: number) {
  sections.value.splice(index, 1);
  selected.value = null;
}

function removeField(
  sectionIndex: number,
  payload: { field: Field; tabIndex?: number; collection?: string },
) {
  const section = sections.value[sectionIndex];
  if (payload.collection === 'photos') {
    const idx = section.photos.indexOf(payload.field);
    if (idx !== -1) section.photos.splice(idx, 1);
  } else if (payload.tabIndex !== undefined && section.tabs[payload.tabIndex]) {
    const fields = section.tabs[payload.tabIndex].fields;
    const idx = fields.indexOf(payload.field);
    if (idx !== -1) fields.splice(idx, 1);
  } else {
    const fields = section.fields;
    const idx = fields.indexOf(payload.field);
    if (idx !== -1) fields.splice(idx, 1);
  }
  if (selected.value === payload.field) selected.value = null;
}

function onAddField(type: any) {
  if (!sections.value.length) addSection();
  let target = paletteSectionIndex.value?.section;
  if (
    target === undefined ||
    target < 0 ||
    target >= sections.value.length
  ) {
    target = sections.value.length - 1;
  }
  const section = sections.value[target];
  if (type.key.startsWith('photo')) {
    const photo = {
      id: Date.now() + Math.random(),
      name: `photo${sectionAllFields(section).length + 1}`,
      label: { en: type.label, el: type.label },
      typeKey: type.key,
      cols: 2,
      validations: {},
      help: { en: '', el: '' },
      logic: [],
      roles: { view: ['super_admin'], edit: ['super_admin'] },
      data: { default: '', enum: [] },
      styles: { fontSize: 'text-base', textColor: '#000000', backgroundColor: '#ffffff' },
      maxCount: type.key === 'photo_repeater' ? 5 : undefined,
    };
    section.photos.push(photo);
    selected.value = photo;
  } else {
    const field = {
      id: Date.now() + Math.random(),
      name: `field${sectionAllFields(section).length + 1}`,
      label: { en: type.label, el: type.label },
      typeKey: type.key,
      cols: 1,
      validations: {},
      fields: type.key === 'repeater' ? [] : undefined,
      placeholder: { en: '', el: '' },
      help: { en: '', el: '' },
      logic: [],
      roles: { view: ['super_admin'], edit: ['super_admin'] },
      data: { default: '', enum: [] },
      styles: { fontSize: 'text-base', textColor: '#000000', backgroundColor: '#ffffff' },
    };
    const tab = paletteSectionIndex.value?.tab;
    if (tab !== undefined && section.tabs[tab]) {
      section.tabs[tab].fields.push(field);
    } else {
      section.fields.push(field);
    }
    selected.value = field;
  }
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
  if (!canAccess.value) return;
  transitionsEditor.value?.commitPending?.();
  const logicRules = sections.value.flatMap((s) =>
    sectionAllFields(s).flatMap((f) =>
      (f.logic || []).map((r) => ({
        if: r.if,
        then: r.then.map((a: any) => ({ [a.type]: a.target })),
      })),
    ),
  );
  const payload = {
    name: name.value,
    tenant_id: tenantId.value || undefined,
    client_id: clientId.value || undefined,
    require_subtasks_complete: requireSubtasksComplete.value,
    schema_json: JSON.stringify({
      sections: sections.value.map((s) => {
        const section: any = {
          key: s.key,
          label: s.label,
          'x-cols': s.cols,
        };

        if (s.tabs.length) {
          section.tabs = s.tabs.map((t) => ({
            key: t.key,
            label: t.label,
            fields: t.fields.map((f) => ({
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
              'x-styles': f.styles,
            })),
          }));
        } else {
          section.fields = s.fields.map((f) => ({
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
            'x-styles': f.styles,
          }));
        }

        section.photos = s.photos.map((p) => ({
          key: p.name,
          label: p.label,
          type: p.typeKey,
          validations: p.validations,
          maxCount: p.maxCount,
          help: p.help,
          'x-roles': p.roles,
          'x-styles': p.styles,
        }));

        return section;
      }),

      ...(logicRules.length ? { logic: logicRules } : {}),
    }),
    statuses: JSON.stringify(statuses.value.reduce((acc: any, s) => ({ ...acc, [s]: [] }), {})),
    status_flow_json: JSON.stringify(statusFlow.value),
    abilities_json: JSON.stringify(permissions.value),
  };
  if (isEdit.value) {
    await api.patch(`/task-types/${taskTypeId.value}`, payload);
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
  validationStatus.value = 'idle';
  validationErrors.value = {};
  const forms = Array.isArray(formRef.value)
    ? formRef.value
    : formRef.value
      ? [formRef.value]
      : [];
  const feErrors = forms.reduce(
    (acc, f) => Object.assign(acc, f?.errors || {}),
    {} as Record<string, string>,
  );
  forms.forEach((f) => {
    Object.keys(f.errors).forEach((k) => delete f.errors[k]);
  });
  if (Object.keys(feErrors).length) {
    validationErrors.value = feErrors;
    validationStatus.value = 'error';
    forms.forEach((f) => Object.assign(f.errors, feErrors));
    const first = Object.keys(validationErrors.value)[0];
    if (first) {
      nextTick(() => document.getElementById(first)?.focus());
    }
    return;
  }
  const url = isEdit.value
    ? `/task-types/${taskTypeId.value}/validate`
    : '/task-types/validate';
  api
    .post(url, {
      schema_json: previewSchema.value,
      form_data: previewData.value,
    })
    .then(() => {
      validationStatus.value = 'success';
    })
    .catch((err: any) => {
      const rawErrors = err.response?.data?.errors || { error: 'validation failed' };
      const mappedErrors: Record<string, string> = {};
      Object.entries(rawErrors).forEach(([k, v]) => {
        const key = k.replace(/^form_data\./, '');
        mappedErrors[key] = Array.isArray(v) ? (v[0] as string) : (v as string);
      });
      validationErrors.value = mappedErrors;
      validationStatus.value = 'error';
      forms.forEach((f) => Object.assign(f.errors, mappedErrors));

      const first = Object.keys(mappedErrors)[0];
      if (first) {
        nextTick(() => document.getElementById(first)?.focus());
      }
    });
}

const previewSchema = computed(() => ({
  sections: sections.value.map((s) => {
    const section: any = {
      key: s.key,
      label: s.label,
      'x-cols': s.cols,
    };

    if (s.tabs.length) {
      section.tabs = s.tabs.map((t) => ({
        key: t.key,
        label: t.label,
        fields: t.fields.map((f) => ({
          key: f.name,
          label: f.label,
          type: f.typeKey,
          validations: f.validations,
          placeholder: f.placeholder,
          help: f.help,
          fields: f.fields,
          default:
            f.data.default === '' || f.data.default === undefined || f.data.default === null
              ? undefined
              : f.data.default,
          enum: f.data.enum.length ? f.data.enum : undefined,
          'x-roles': f.roles,
          'x-styles': f.styles,
          'x-cols': f.cols,
        })),
      }));
    } else {
      section.fields = s.fields.map((f) => ({
        key: f.name,
        label: f.label,
        type: f.typeKey,
        validations: f.validations,
        placeholder: f.placeholder,
        help: f.help,
        fields: f.fields,
        default:
          f.data.default === '' || f.data.default === undefined || f.data.default === null
            ? undefined
            : f.data.default,
        enum: f.data.enum.length ? f.data.enum : undefined,
        'x-roles': f.roles,
        'x-styles': f.styles,
        'x-cols': f.cols,
      }));
    }

    section.photos = s.photos.map((p) => ({
      key: p.name,
      label: p.label,
      type: p.typeKey,
      validations: p.validations,
      maxCount: p.maxCount,
      help: p.help,
      'x-roles': p.roles,
      'x-styles': p.styles,
    }));

    return section;
  }),

  ...(sections.value
    .flatMap((s) =>
      sectionAllFields(s).flatMap((f) =>
        (f.logic || []).map((r) => ({
          if: r.if,
          then: r.then.map((a: any) => ({ [a.type]: a.target })),
        })),
      ),
    ).length
    ? {
        logic: sections.value.flatMap((s) =>
          sectionAllFields(s).flatMap((f) =>
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
