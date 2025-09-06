<template>
  <div v-if="canAccess">
    <Card class="max-w-lg p-6 space-y-4">
      <form class="space-y-4" @submit.prevent="submitForm">
        <Textinput v-model="title" :label="t('tasks.form.title')" />

        <FromGroup :label="t('tasks.form.type')" :error="taskTypeError">
          <Select
            v-model="taskTypeId"
            :options="typeOptions"
            :placeholder="t('tasks.form.typePlaceholder')"
            @change="onTypeChange"
          />
        </FromGroup>

        <FromGroup v-if="versionOptions.length" :label="t('tasks.form.version')">
          <Select v-model="taskTypeVersionId" :options="versionOptions" />
        </FromGroup>

        <InputGroup v-model="dueAt" :label="t('tasks.form.dueAt')" type="date" />

        <StatusSelect
          v-if="isEdit"
          v-model="status"
          :options="statusOptions"
          :label="t('tasks.form.status')"
          :error="errors.status"
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
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api, { extractFormErrors } from '@/services/api';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';
import { useField, useForm } from 'vee-validate';
import * as yup from 'yup';
import { useNotify } from '@/plugins/notify';
import AssigneePicker from '@/components/tasks/AssigneePicker.vue';
import PrioritySelect from '@/components/fields/PrioritySelect.vue';
import StatusSelect from '@/components/fields/StatusSelect.vue';
import { toISO } from '@/utils/datetime';
import { can } from '@/stores/auth';
import Button from '@dc/components/Button';
import Card from '@dc/components/Card';
import Select from '@dc/components/Select';
import Textinput from '@dc/components/Textinput';
import InputGroup from '@dc/components/InputGroup';
import FromGroup from '@dc/components/FromGroup';
import Modal from '@dc/components/Modal';

const notify = useNotify();
const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const title = ref('');
const types = ref<any[]>([]);
const versions = ref<any[]>([]);
const taskTypeVersionId = ref<number | null>(null);
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
const versionOptions = computed(() => versions.value.map((v: any) => ({ value: v.id, label: v.semver })));

const statusOptions = ref<{ label: string; value: string }[]>([]);
const priorityOptions = computed(() => [
  { label: t('tasks.priority.low'), value: 'low' },
  { label: t('tasks.priority.normal'), value: 'normal' },
  { label: t('tasks.priority.high'), value: 'high' },
  { label: t('tasks.priority.urgent'), value: 'urgent' },
]);

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
  const [typesRes, statusesRes] = await Promise.all([
    api.get('/task-types/options'),
    api.get('/task-statuses'),
  ]);
  types.value = typesRes.data;
  const statusBySlug: Record<string, any> = {};
  statusOptions.value = statusesRes.data.map((s: any) => {
    statusBySlug[s.slug] = s;
    return { label: s.name, value: s.slug };
  });
  if (isEdit.value) {
    const res = await api.get(`/tasks/${route.params.id}`);
    const task = res.data;
    taskTypeId.value = task.type?.id || task.task_type_id;
    await onTypeChange();
    taskTypeVersionId.value = task.task_type_version_id || task.task_type_version?.id || taskTypeVersionId.value;
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
    const flow = task.type?.status_flow_json || [];
    let graph: Record<string, string[]> = {};
    if (Array.isArray(flow)) {
      flow.forEach((e: [string, string]) => {
        const [from, to] = e;
        if (!graph[from]) graph[from] = [];
        graph[from].push(to);
      });
    } else if (flow && typeof flow === 'object') {
      graph = flow;
    }
    const allowedSlugs = graph[task.status] || [];
    const allowed = [task.status, ...allowedSlugs]
      .map((slug) => statusBySlug[slug])
      .filter(Boolean)
      .map((s: any) => ({ label: s.name, value: s.slug }));
    if (allowed.length) statusOptions.value = allowed;
  }
});

async function onTypeChange() {
  formData.value = {};
  versions.value = [];
  taskTypeVersionId.value = null;
  const t = types.value.find((t) => t.id === taskTypeId.value);
  scheduledAt.value = t?.scheduled_at ? toISO(t.scheduled_at) : '';
  slaStartAt.value = t?.sla_start_at ? toISO(t.sla_start_at) : '';
  slaEndAt.value = t?.sla_end_at ? toISO(t.sla_end_at) : '';
  assignee.value = null;
  dueAt.value = null;
  priority.value = '';
  if (taskTypeId.value) {
    let list: any[] = [];
    const manageVersions = can('task_type_versions.manage');
    if (manageVersions) {
      const { data } = await api.get('/task-type-versions', {
        params: { task_type_id: taskTypeId.value },
      });
      list = data.data || [];
    } else if (t?.current_version) {
      list = [t.current_version];
    }
    versions.value = manageVersions ? list : list.filter((v: any) => v.published_at);
    taskTypeVersionId.value = versions.value[0]?.id ?? null;
  }
}

const currentSchema = computed(() => {
  const v = versions.value.find((vv) => vv.id === taskTypeVersionId.value);
  return v ? v.schema_json : null;
});

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
  if (!taskTypeId.value || !title.value.trim()) return false;
  const formValid = requiredFields.value.every((f: string) => {
    const val = formData.value[f];
    return !(val === undefined || val === null || val === '');
  });
  if (!formValid) return false;
  if (assigneeRequired.value && !assignee.value) return false;
  return true;
});

const submitForm = handleSubmit(async () => {
  serverError.value = '';
  const payload: any = {
    task_type_id: taskTypeId.value,
    form_data: formData.value,
  };
  if (title.value) payload.title = title.value;
  if (taskTypeVersionId.value) payload.task_type_version_id = taskTypeVersionId.value;
  if (scheduledAt.value) payload.scheduled_at = toISO(scheduledAt.value);
  if (slaStartAt.value) payload.sla_start_at = toISO(slaStartAt.value);
  if (slaEndAt.value) payload.sla_end_at = toISO(slaEndAt.value);
  if (dueAt.value) payload.due_at = toISO(dueAt.value);
  if (priority.value) payload.priority = priority.value;
  if (assignee.value) payload.assigned_user_id = assignee.value.id;
  if (status.value && (!isEdit.value || status.value !== originalStatus.value)) {
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
      const res = await api.post('/tasks', payload);
      notify.success('Task created');
      router.push({
        name: 'tasks.details',
        params: { id: res.data.id },
      });
    }
  } catch (e: any) {
    const formErrors = extractFormErrors(e);
    if (Object.keys(formErrors).length) {
      setErrors(formErrors);
    } else {
      serverError.value = e.message || 'Failed to save';
      notify.error(serverError.value);
      showError.value = true;
    }
  }
});
</script>
