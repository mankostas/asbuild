<template>
    <div v-if="canAccess">
      <form class="max-w-lg space-y-4" @submit.prevent="submitForm">
      <VueSelect label="Type" :error="taskTypeError">
        <vSelect
          v-model="taskTypeId"
          :options="types"
          label="name"
          :reduce="(t: any) => t.id"
          placeholder="Select type"
          @option:selected="onTypeChange"
        />
      </VueSelect>

      <VueSelect v-if="isEdit" label="Status" :error="errors.status">
        <vSelect
          v-model="status"
          :options="statusOptions"
          placeholder="Select status"
        />
      </VueSelect>

      <AssigneePicker v-if="assigneeField && can('tasks.assign')" v-model="assignee" />

      <VueSelect :label="t('tasks.form.priority')">
        <vSelect
          v-model="priority"
          :options="priorityOptions"
          label="label"
          :reduce="(o: any) => o.value"
          :placeholder="t('tasks.form.priorityPlaceholder')"
        />
      </VueSelect>

      <div class="flex flex-col">
        <span id="due-at-label" class="mb-1">{{ t('tasks.form.dueAt') }}</span>
        <input
          id="due-at"
          v-model="dueAt"
          type="datetime-local"
          class="border rounded p-2"
          :aria-labelledby="'due-at-label'"
        />
      </div>

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
          text="Submit"
          btnClass="btn-dark"
          :isDisabled="!meta.valid || !canSubmit"
        />
      </div>
    </form>

    <Modal :activeModal="showError" title="Error" @close="showError = false">
      <p>{{ serverError }}</p>
      <template #footer>
        <Button text="Close" btnClass="btn-dark" @click="showError = false" />
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api, { extractFormErrors } from '@/services/api';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';
import Button from '@/components/ui/Button/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import Modal from '@/components/ui/Modal/index.vue';
import { useField, useForm } from 'vee-validate';
import * as yup from 'yup';
import vSelect from 'vue-select';
import { useNotify } from '@/plugins/notify';
import AssigneePicker from '@/components/tasks/AssigneePicker.vue';
import { toISO } from '@/utils/datetime';
import { can } from '@/stores/auth';

const notify = useNotify();
const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const types = ref<any[]>([]);
const formData = ref<any>({});
const scheduledAt = ref('');
const slaStartAt = ref('');
const slaEndAt = ref('');
const status = ref('');
const serverError = ref('');
const showError = ref(false);
const originalStatus = ref('');
const assignee = ref<{ kind: 'team' | 'employee'; id: number } | null>(null);
const priority = ref('');
const dueAt = ref('');

const statusOptions = ref<string[]>([]);
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
    api.get('/task-types'),
    api.get('/task-statuses'),
  ]);
  types.value = typesRes.data;
  statusOptions.value = statusesRes.data.map((s: any) => s.name);
  if (isEdit.value) {
    const res = await api.get(`/tasks/${route.params.id}`);
    const task = res.data;
    taskTypeId.value = task.type?.id || task.task_type_id;
    formData.value = task.form_data || {};
    scheduledAt.value = task.scheduled_at ? toISO(task.scheduled_at) : '';
    slaStartAt.value = task.sla_start_at ? toISO(task.sla_start_at) : '';
    slaEndAt.value = task.sla_end_at ? toISO(task.sla_end_at) : '';
    dueAt.value = task.due_at ? toISO(task.due_at) : '';
    priority.value = task.priority || '';
    status.value = task.status;
    originalStatus.value = task.status;
    if (task.assignee) {
      assignee.value = { kind: task.assignee.kind, id: task.assignee.id };
    }
    const map = task.type?.statuses || {};
    const allowed = Array.from(new Set([...Object.keys(map), ...Object.values(map).flat()]));
    if (allowed.length) {
      statusOptions.value = allowed;
    }
  }
});

function onTypeChange() {
  formData.value = {};
  const t = types.value.find((t) => t.id === taskTypeId.value);
  scheduledAt.value = t?.scheduled_at ? toISO(t.scheduled_at) : '';
  slaStartAt.value = t?.sla_start_at ? toISO(t.sla_start_at) : '';
  slaEndAt.value = t?.sla_end_at ? toISO(t.sla_end_at) : '';
  assignee.value = null;
  dueAt.value = '';
  priority.value = '';
}

const currentSchema = computed(() => {
  const t = types.value.find((t) => t.id === taskTypeId.value);
  return t ? t.form_schema : null;
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
  if (!taskTypeId.value) return false;
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
  if (scheduledAt.value) payload.scheduled_at = toISO(scheduledAt.value);
  if (slaStartAt.value) payload.sla_start_at = toISO(slaStartAt.value);
  if (slaEndAt.value) payload.sla_end_at = toISO(slaEndAt.value);
  if (dueAt.value) payload.due_at = toISO(dueAt.value);
  if (priority.value) payload.priority = priority.value;
  if (assignee.value) payload.assignee = assignee.value;
  try {
    if (isEdit.value) {
      if (status.value && status.value !== originalStatus.value) {
        payload.status = status.value;
      }
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
