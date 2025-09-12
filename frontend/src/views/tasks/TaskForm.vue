<template>
  <div v-if="canAccess">
    <Card class="max-w-lg p-6 space-y-4">
      <form class="space-y-4" @submit.prevent="submitForm">
        <FromGroup v-if="auth.isSuperAdmin && !isEdit" :label="t('tenant')">
          <Select
            v-model="tenantId"
            :options="tenantOptions"
            :aria-label="t('tenant')"
            class="w-full"
          />
        </FromGroup>
        <FromGroup :label="t('tasks.form.title')">
          <Textinput
            v-model="title"
            :aria-label="t('tasks.form.title')"
            class="w-full"
          />
        </FromGroup>

        <FromGroup :label="t('tasks.form.type')" :error="taskTypeError">
          <Select
            v-model="taskTypeId"
            :options="typeOptions"
            :placeholder="t('tasks.form.typePlaceholder')"
            :aria-label="t('tasks.form.type')"
            class="w-full"
            :disabled="isEdit"
            @change="onTypeChange"
          />
        </FromGroup>

        <FromGroup :label="t('tasks.form.dueAt')">
          <DateInput v-model="dueAt" :aria-label="t('tasks.form.dueAt')" />
        </FromGroup>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <InputGroup :disabled="!can('tasks.sla.override')" class="w-full">
            <template #default>
              <DateTimeInput
                v-model="slaStartAt"
                :readonly="!can('tasks.sla.override')"
                :label="t('tasks.form.slaStart')"
              />
            </template>
            <template #append>
              <Tooltip theme="light" trigger="mouseenter focus">
                <template #button>
                  <Icon
                    icon="heroicons-outline:question-mark-circle"
                    class="w-4 h-4 text-slate-500 cursor-help"
                    :aria-label="t('tasks.form.slaTooltip')"
                  />
                </template>
                {{ t('tasks.form.slaTooltip') }}
              </Tooltip>
            </template>
          </InputGroup>
          <InputGroup :disabled="!can('tasks.sla.override')" class="w-full">
            <template #default>
              <DateTimeInput
                v-model="slaEndAt"
                :readonly="!can('tasks.sla.override')"
                :label="t('tasks.form.slaEnd')"
              />
            </template>
            <template #append>
              <Tooltip theme="light" trigger="mouseenter focus">
                <template #button>
                  <Icon
                    icon="heroicons-outline:question-mark-circle"
                    class="w-4 h-4 text-slate-500 cursor-help"
                    :aria-label="t('tasks.form.slaTooltip')"
                  />
                </template>
                {{ t('tasks.form.slaTooltip') }}
              </Tooltip>
            </template>
          </InputGroup>
        </div>

        <StatusSelect
          v-model="status"
          :options="statusOptions"
          :label="t('tasks.form.status')"
          :error="errors.status"
          :disabled="isEdit && !can('tasks.status.update')"
        />

        <AssigneePicker
          v-if="can('tasks.assign')"
          v-model="assignee"
          :label="t('tasks.form.assignee')"
        />

        <PrioritySelect
          v-model="priority"
          :label="t('tasks.form.priority')"
          :options="priorityOptions"
        />

        <JsonSchemaForm
          v-if="currentSchemaNoDefaults"
          :key="taskTypeId"
          v-model="formData"
          :schema="currentSchemaNoDefaults"
          :task-id="0"
        />

        <div class="pt-2">
          <Button
            type="submit"
            :text="t('actions.save')"
            btnClass="btn-dark"
            :isDisabled="!meta.valid || !canSubmit"
          />
        </div>
      </form>

      <Modal :activeModal="showError" :title="t('common.error')" @close="showError = false">
        <p>{{ serverError }}</p>
        <template #footer>
          <Button :text="t('actions.close')" btnClass="btn-dark" @click="showError = false" />
        </template>
      </Modal>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';
import { useField, useForm } from 'vee-validate';
import * as yup from 'yup';
import { useNotify } from '@/plugins/notify';
import AssigneePicker from '@/components/tasks/AssigneePicker.vue';
import PrioritySelect from '@/components/fields/PrioritySelect.vue';
import StatusSelect from '@/components/fields/StatusSelect.vue';
import DateInput from '@/components/fields/DateInput.vue';
import DateTimeInput from '@/components/fields/DateTimeInput.vue';
import { toISO } from '@/utils/datetime';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { TENANT_HEADER } from '@/config/app';
import Button from '@dc/components/Button';
import Card from '@dc/components/Card';
import Select from '@dc/components/Select';
import Textinput from '@dc/components/Textinput';
import InputGroup from '@dc/components/InputGroup';
import FromGroup from '@dc/components/FromGroup';
import Modal from '@dc/components/Modal';
import Tooltip from '@/components/ui/Tooltip/index.vue';
import Icon from '@dc/components/Icon';
import { computeStatusOptions } from './statusOptions';

const defaultKeys = new Set([
  'assignee',
  'priority',
  'status',
  'due_at',
  'due_date',
  'scheduled_at',
  'sla_start_at',
  'sla_end_at',
  'title',
]);
const defaultTypes = new Set(['assignee', 'priority', 'status']);

const notify = useNotify();
const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const auth = useAuthStore();
const tenantStore = useTenantStore();
const tenantId = ref<string | number | null>(tenantStore.currentTenantId || null);
const tenantOptions = computed(() =>
  tenantStore.tenants.map((t: any) => ({ value: String(t.id), label: t.name })),
);

const title = ref('');
const types = ref<any[]>([]);
const currentType = ref<any | null>(null);
const formData = ref<any>({});
const scheduledAt = ref('');
const slaStartAt = ref('');
const slaEndAt = ref('');
const status = ref<string | null>(null);
const serverError = ref('');
const showError = ref(false);
const originalStatus = ref<string | null>(null);
const assignee = ref<{ id: number } | null>(null);
const priority = ref('');
const dueAt = ref<string | null>(null);

const typeOptions = computed(() => types.value.map((t: any) => ({ value: t.id, label: t.name })));
const statusOptions = ref<{ label: string; value: string }[]>([]);
const statusBySlug: Record<string, any> = {};
const priorityOptions = ref<{ label: string; value: string }[]>([]);

async function loadTypes(tid: string | number | null): Promise<any[]> {
  const headers =
    auth.isSuperAdmin && tid ? { [TENANT_HEADER]: tid } : undefined;
  const { data } = await api.get('/task-types/options', { headers });
  return data;
}

async function loadStatuses(tid: string | number | null): Promise<any[]> {
  const headers =
    auth.isSuperAdmin && tid ? { [TENANT_HEADER]: tid } : undefined;
  const { data } = await api.get('/task-statuses', { headers });
  return data.data || data;
}

async function loadPriorities() {
  priorityOptions.value = [];
  if (!taskTypeId.value) return;
  const headers =
    auth.isSuperAdmin && tenantId.value
      ? { [TENANT_HEADER]: tenantId.value }
      : undefined;
  const { data } = await api.get(
    `/task-types/${taskTypeId.value}/sla-policies`,
    { headers },
  );
  const policies = data.data || data;
  const uniques = Array.from(new Set(policies.map((p: any) => p.priority)));
  priorityOptions.value = uniques.map((p: string) => ({
    value: p,
    label: t(`tasks.priority.${p}`),
  }));
  if (!priority.value && priorityOptions.value.length) {
    priority.value = priorityOptions.value[0].value;
  }
}
const schema = yup.object({
  task_type_id: yup.mixed().required('Type is required'),
});

const { handleSubmit, meta, setErrors, errors, setFieldValue } = useForm({
  validationSchema: schema,
});
const { value: taskTypeId, errorMessage: taskTypeError } = useField<string | number>(
  'task_type_id',
);

const isEdit = computed(() => route.name === 'tasks.edit');
const canAccess = computed(() =>
  isEdit.value
    ? can('tasks.update') || can('tasks.manage')
    : can('tasks.create') || can('tasks.manage'),
);

onMounted(async () => {
  if (auth.isSuperAdmin && tenantStore.tenants.length === 0) {
    await tenantStore.loadTenants();
  }
  const initialTenant = tenantId.value;
  const [typesData, statusesData] = await Promise.all([
    loadTypes(initialTenant),
    loadStatuses(initialTenant),
  ]);
  if (initialTenant === tenantId.value) {
    types.value = typesData;
    await nextTick();
    Object.keys(statusBySlug).forEach((k) => delete statusBySlug[k]);
    if (Array.isArray(statusesData)) {
      statusesData.forEach((s: any) => {
        statusBySlug[s.slug] = s;
      });
    }

    if (isEdit.value) {
      const res = await api.get(`/tasks/${route.params.id}`);
      const task = res.data.data || res.data;
      taskTypeId.value = task.type?.id || task.task_type_id;
      if (!types.value.some((t: any) => t.id === taskTypeId.value)) {
        if (task.type) {
          types.value.push(task.type);
        } else {
          const { data } = await api.get(`/task-types/${taskTypeId.value}`);
          types.value.push(data.data ?? data);
        }
        await nextTick();
      }
      setFieldValue('task_type_id', taskTypeId.value, true);
      await onTypeChange();
      formData.value = task.form_data || {};
      scheduledAt.value = task.scheduled_at ? toISO(task.scheduled_at) : '';
      slaStartAt.value = task.sla_start_at ? toISO(task.sla_start_at) : '';
      slaEndAt.value = task.sla_end_at ? toISO(task.sla_end_at) : '';
      dueAt.value = task.due_at ? toISO(task.due_at) : null;
      priority.value = task.priority || '';
      status.value = task.status || null;
      originalStatus.value = status.value;
      if (task.assignee) {
        assignee.value = { id: task.assignee.id };
      }
      updateStatusOptions(task.status);
    }
  }
});

async function onTypeChange() {
  formData.value = {};
  currentType.value = null;
  assignee.value = null;
  dueAt.value = null;
  priority.value = '';
  if (taskTypeId.value) {
    const headers =
      auth.isSuperAdmin && tenantId.value
        ? { [TENANT_HEADER]: tenantId.value }
        : undefined;
    const { data } = await api.get(`/task-types/${taskTypeId.value}`, { headers });
    currentType.value = data.data ?? data;
    scheduledAt.value = currentType.value?.scheduled_at ? toISO(currentType.value.scheduled_at) : '';
    slaStartAt.value = currentType.value?.sla_start_at ? toISO(currentType.value.sla_start_at) : '';
    slaEndAt.value = currentType.value?.sla_end_at ? toISO(currentType.value.sla_end_at) : '';
    dueAt.value = currentType.value?.due_at
      ? toISO(currentType.value.due_at)
      : currentType.value?.due_date
      ? toISO(currentType.value.due_date)
      : null;
  } else {
    scheduledAt.value = '';
    slaStartAt.value = '';
    slaEndAt.value = '';
    dueAt.value = null;
  }
  await loadPriorities();
  updateStatusOptions();
}

function updateStatusOptions(current?: string | null) {
  const opts = computeStatusOptions(
    currentType.value,
    statusBySlug,
    isEdit.value,
    current,
  );

  if (current && !opts.some((o) => o.value === current)) {
    opts.unshift({ value: current, label: statusBySlug[current]?.name || current });
  }

  statusOptions.value = opts;

  if (current) {
    status.value = current;
  } else if (!status.value && opts.length) {
    status.value = opts[0].value;
  }
}

const currentSchema = computed(() => currentType.value?.schema_json || null);

const currentSchemaNoDefaults = computed(() => {
  if (!currentSchema.value) return null;
  const schema = JSON.parse(JSON.stringify(currentSchema.value));
  if (schema.properties) {
    Object.keys(schema.properties).forEach((key) => {
      const prop = schema.properties[key];
      if (defaultKeys.has(key) || defaultTypes.has(prop?.kind) || defaultTypes.has(prop?.type)) {
        delete schema.properties[key];
      }
    });
  }
  if (schema.required) {
    schema.required = schema.required.filter((r: string) => !defaultKeys.has(r));
  }
  if (Array.isArray(schema.sections)) {
    schema.sections.forEach((section: any) => {
      if (Array.isArray(section.fields)) {
        section.fields = section.fields.filter(
          (f: any) => !defaultKeys.has(f.key) && !defaultTypes.has(f.type),
        );
      }
      if (Array.isArray(section.tabs)) {
        section.tabs.forEach((tab: any) => {
          tab.fields = (tab.fields || []).filter(
            (f: any) => !defaultKeys.has(f.key) && !defaultTypes.has(f.type),
          );
        });
      }
    });
  }
  return schema;
});

const requiredDefaultFields = computed(() => {
  if (!currentSchema.value) return [];
  const required = currentSchema.value.required || [];
  const props = currentSchema.value.properties || {};
  const set = new Set<string>();
  required.forEach((key: string) => {
    const prop = props[key];
    if (defaultKeys.has(key)) {
      set.add(key);
    } else if (defaultTypes.has(prop?.kind)) {
      set.add(prop.kind);
    } else if (defaultTypes.has(prop?.type)) {
      set.add(prop.type);
    }
  });
  return Array.from(set);
});

const requiredFields = computed(() => currentSchemaNoDefaults.value?.required || []);

const canSubmit = computed(() => {
  if (auth.isSuperAdmin && !isEdit.value && !tenantId.value) return false;
  if (!taskTypeId.value || !title.value.trim()) return false;
  const formValid = requiredFields.value.every((f: string) => {
    const val = formData.value[f];
    return !(val === undefined || val === null || val === '');
  });
  if (!formValid) return false;
  const defaultRefMap: Record<string, any> = {
    assignee,
    priority,
    status,
    due_at: dueAt,
    due_date: dueAt,
    scheduled_at: scheduledAt,
    sla_start_at: slaStartAt,
    sla_end_at: slaEndAt,
    title,
  };
  const defaultsValid = requiredDefaultFields.value.every((key) => {
    const ref = defaultRefMap[key];
    const val = ref?.value;
    return !(val === undefined || val === null || val === '');
  });
  if (!defaultsValid) return false;
  return true;
});

watch(
  () => tenantId.value,
  async () => {
    const currentTenant = tenantId.value; // snapshot tenant to avoid race conditions
    const [typesData, statusesData] = await Promise.all([
      loadTypes(currentTenant),
      loadStatuses(currentTenant),
    ]);
    if (currentTenant !== tenantId.value) {
      // tenant changed while requests were in-flight; ignore stale results
      return;
    }
    types.value = typesData;
    Object.keys(statusBySlug).forEach((k) => delete statusBySlug[k]);
    if (Array.isArray(statusesData)) {
      statusesData.forEach((s: any) => {
        statusBySlug[s.slug] = s;
      });
    }
    taskTypeId.value = '' as any;
    priorityOptions.value = [];

    await onTypeChange();
  },
);

const submitForm = handleSubmit(async () => {
  serverError.value = '';
  const payload: any = {
    task_type_id: Number(taskTypeId.value),
    form_data: formData.value,
  };
  if (title.value) payload.title = title.value;
  if (scheduledAt.value) payload.scheduled_at = toISO(scheduledAt.value);
  if (slaStartAt.value) payload.sla_start_at = toISO(slaStartAt.value);
  if (slaEndAt.value) payload.sla_end_at = toISO(slaEndAt.value);
  if (dueAt.value) payload.due_at = toISO(dueAt.value);
  if (priority.value) payload.priority = priority.value;
  if (assignee.value) payload.assigned_user_id = Number(assignee.value.id);
  if (isEdit.value) {
    if (status.value && status.value !== originalStatus.value) {
      payload.status = status.value;
    }
  } else {
    payload.status = status.value;
  }
  try {
    if (isEdit.value) {
      await api.patch(`/tasks/${route.params.id}`, payload);
      notify.success('Task updated');
      router.push({
        name: 'tasks.details',
        params: { id: route.params.id },
      });
    } else {
      const headers =
        auth.isSuperAdmin && tenantId.value
          ? { [TENANT_HEADER]: tenantId.value }
          : undefined;
      const res = await api.post('/tasks', payload, { headers });
      notify.success('Task created');
      const taskId = res.data?.data?.id ?? res.data?.id;
      router.push({
        name: 'tasks.details',
        params: { id: taskId },
      });
    }
  } catch (e: any) {
    if (e?.status === 422 || e?.errors) {
      setErrors(e.errors || {});
    } else {
      serverError.value = e.message || 'Failed to save';
      notify.error(serverError.value);
      showError.value = true;
    }
  }
});
</script>
