<template>
  <div>
    <h2 class="text-xl font-bold mb-4">
      {{ isEdit ? 'Edit' : 'Create' }} Appointment
    </h2>
    <form @submit.prevent="submitForm" class="max-w-lg space-y-4">
      <VueSelect label="Type" :error="typeError">
        <vSelect
          v-model="typeId"
          :options="types"
          label="name"
          :reduce="(t: any) => t.id"
          placeholder="Select type"
          @option:selected="onTypeChange"
        />
      </VueSelect>

      <VueSelect v-if="isEdit" label="Status">
        <vSelect
          v-model="status"
          :options="statusOptions"
          placeholder="Select status"
        />
      </VueSelect>

      <JsonSchemaForm
        v-if="currentSchema"
        :key="typeId"
        v-model="formData"
        :schema="currentSchema"
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
import api from '@/services/api';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';
import Button from '@/components/ui/Button/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import Modal from '@/components/ui/Modal/index.vue';
import { useField, useForm } from 'vee-validate';
import * as yup from 'yup';
import vSelect from 'vue-select';
import { useNotify } from '@/plugins/notify';

const notify = useNotify();
const router = useRouter();
const route = useRoute();

const types = ref<any[]>([]);
const formData = ref<any>({});
const scheduledAt = ref('');
const slaStartAt = ref('');
const slaEndAt = ref('');
const status = ref('');
const serverError = ref('');
const showError = ref(false);
const originalStatus = ref('');

const statusOptions = [
  'draft',
  'assigned',
  'in_progress',
  'completed',
  'rejected',
  'redo',
];

const schema = yup.object({
  typeId: yup.mixed().required('Type is required'),
});

const { handleSubmit, meta } = useForm({ validationSchema: schema });
const { value: typeId, errorMessage: typeError } = useField<string | number>(
  'typeId',
);

const isEdit = computed(() => route.name === 'appointments.edit');

onMounted(async () => {
  const { data } = await api.get('/appointment-types');
  types.value = data;
  if (isEdit.value) {
    const res = await api.get(`/appointments/${route.params.id}`);
    const appt = res.data;
    typeId.value = appt.type?.id || appt.appointment_type_id;
    formData.value = appt.form_data || {};
    scheduledAt.value = appt.scheduled_at || '';
    slaStartAt.value = appt.sla_start_at || '';
    slaEndAt.value = appt.sla_end_at || '';
    status.value = appt.status;
    originalStatus.value = appt.status;
  }
});

function onTypeChange() {
  formData.value = {};
  const t = types.value.find((t) => t.id === typeId.value);
  scheduledAt.value = t?.scheduled_at || '';
  slaStartAt.value = t?.sla_start_at || '';
  slaEndAt.value = t?.sla_end_at || '';
}

const currentSchema = computed(() => {
  const t = types.value.find((t) => t.id === typeId.value);
  return t ? t.form_schema : null;
});

const requiredFields = computed(() => currentSchema.value?.required || []);

const canSubmit = computed(() => {
  if (!typeId.value) return false;
  return requiredFields.value.every((f: string) => {
    const val = formData.value[f];
    return !(val === undefined || val === null || val === '');
  });
});

const submitForm = handleSubmit(async () => {
  serverError.value = '';
  const payload: any = {
    appointment_type_id: typeId.value,
    form_data: formData.value,
  };
  if (scheduledAt.value) payload.scheduled_at = scheduledAt.value;
  if (slaStartAt.value) payload.sla_start_at = slaStartAt.value;
  if (slaEndAt.value) payload.sla_end_at = slaEndAt.value;
  try {
    if (isEdit.value) {
      if (status.value && status.value !== originalStatus.value) {
        payload.status = status.value;
      }
      await api.patch(`/appointments/${route.params.id}`, payload);
      notify.success('Appointment updated');
      router.push({
        name: 'appointments.details',
        params: { id: route.params.id },
      });
    } else {
      const res = await api.post('/appointments', payload);
      notify.success('Appointment created');
      router.push({
        name: 'appointments.details',
        params: { id: res.data.id },
      });
    }
  } catch (e: any) {
    serverError.value = e.message || 'Failed to save';
    notify.error(serverError.value);
    showError.value = true;
  }
});
</script>
