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
            @change="onTypeChange"
          />
        </FromGroup>

        <FromGroup #default="{ inputId, labelId }" :label="t('tasks.form.dueAt')">
          <InputGroup class="w-full">
            <template #default>
              <Textinput
                :id="inputId"
                v-model="dueAt"
                type="date"
                :aria-labelledby="labelId"
                class="w-full"
              />
            </template>
          </InputGroup>
        </FromGroup>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <FromGroup #default="{ inputId, labelId }" :label="t('tasks.form.slaStart')">
            <InputGroup :disabled="!can('tasks.sla.override')" class="w-full">
              <template #default>
                <Textinput
                  :id="inputId"
                  v-model="slaStartAt"
                  type="datetime-local"
                  :disabled="!can('tasks.sla.override')"
                  :aria-labelledby="labelId"
                  class="w-full"
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
          </FromGroup>
          <FromGroup #default="{ inputId, labelId }" :label="t('tasks.form.slaEnd')">
            <InputGroup :disabled="!can('tasks.sla.override')" class="w-full">
              <template #default>
                <Textinput
                  :id="inputId"
                  v-model="slaEndAt"
                  type="datetime-local"
                  :disabled="!can('tasks.sla.override')"
                  :aria-labelledby="labelId"
                  class="w-full"
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
          </FromGroup>
        </div>

        <StatusSelect
          v-model="status"
          :options="statusOptions"
          :label="t('tasks.form.status')"
          :error="errors.status"
          :disabled="isEdit && !can('tasks.status.update')"
        />

        <AssigneePicker v-if="assigneeField && can('tasks.assign')" v-model="assignee" />

        <PrioritySelect
          v-model="priority"
          :label="t('tasks.form.priority')"
          :options="priorityOptions"
        />

        <JsonSchemaForm
          v-if="currentSchemaNoAssignee"
          :key="taskTypeId"
          v-model="formData"
          :schema="currentSchemaNoAssignee"
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
import { ref, computed, onMounted, watch } from 'vue';
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

const { handleSubmit, meta, setErrors, errors } = useForm({ validationSchema: schema });
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
    Object.keys(statusBySlug).forEach((k) => delete statusBySlug[k]);
    if (Array.isArray(statusesData)) {
      statusesData.forEach((s: any) => {
        statusBySlug[s.slug] = s;
      });
    }

    if (isEdit.value) {
      const res = await api.get(`/tasks/${route.params.id}`);
      const task = res.data;
      taskTypeId.value = task.type?.id || task.task_type_id;
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
  } else {
    scheduledAt.value = '';
    slaStartAt.value = '';
    slaEndAt.value = '';
  }
  await loadPriorities();
  updateStatusOptions();
}

function updateStatusOptions(current?: string | null) {
  const type = currentType.value;
  const raw = type?.statuses;
  const typeStatuses = Array.isArray(raw)
    ? raw
    : Object.keys(raw || {}).map((slug) => ({ slug }));
  let opts = typeStatuses.map((s: any) => ({
    value: s.slug,
    label: statusBySlug[s.slug]?.name || s.slug,
  }));
  if (isEdit.value && current) {
    const flow = type?.status_flow_json || [];
    let graph: Record<string, string[]> = {};
    if (Array.isArray(flow)) {
      flow.forEach((e: [string, string]) => {
        const [from, to] = e;
        if (!graph[from]) graph[from] = [];
        graph[from].push(to);
      });
    } else if (flow && typeof flow === 'object') {
      graph = flow as Record<string, string[]>;
    }
    const allowed = [current, ...(graph[current] || [])];
    opts = opts.filter((o) => allowed.includes(o.value));
  }
  statusOptions.value = opts;
  if (!status.value && opts.length) {
    status.value = opts[0].value;
  }
}

const currentSchema = computed(() => currentType.value?.schema_json || null);

const assigneeField = computed(() => {
  const props = currentSchema.value?.properties || {};
  return Object.entries(props).find(([, prop]: any) => prop.kind === 'assignee')?.[0] || null;
});

const currentSchemaNoAssignee = computed(() => {
  if (!currentSchema.value) return null;
  const schema = JSON.parse(JSON.stringify(currentSchema.value));
  const field = assigneeField.value;
  if (field && schema.properties) {
    delete schema.properties[field];
    if (schema.required) {
      schema.required = schema.required.filter((r: string) => r !== field);
    }
  }
  return schema;
});

const assigneeRequired = computed(() => {
  const field = assigneeField.value;
  return field ? currentSchema.value?.required?.includes(field) : false;
});

const requiredFields = computed(() => currentSchemaNoAssignee.value?.required || []);

const canSubmit = computed(() => {
  if (auth.isSuperAdmin && !isEdit.value && !tenantId.value) return false;
  if (!taskTypeId.value || !title.value.trim()) return false;
  const formValid = requiredFields.value.every((f: string) => {
    const val = formData.value[f];
    return !(val === undefined || val === null || val === '');
  });
  if (!formValid) return false;
  if (assigneeRequired.value && !assignee.value) return false;
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
