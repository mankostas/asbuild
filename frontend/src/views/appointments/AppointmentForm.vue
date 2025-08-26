<template>
  <div>
    <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit' : 'Create' }} Appointment</h2>
    <form @submit.prevent="onSubmit" class="max-w-lg">
      <div class="mb-4">
        <label class="block font-medium mb-1" for="type">Type<span class="text-red-600">*</span></label>
        <select id="type" v-model="typeId" class="border rounded p-2 w-full" @change="onTypeChange">
          <option value="">Select type</option>
          <option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </div>
      <div class="mb-4">
        <label class="block font-medium mb-1" for="scheduled">Scheduled At</label>
        <input id="scheduled" type="datetime-local" v-model="scheduledAt" class="border rounded p-2 w-full" />
      </div>
      <div class="mb-4">
        <label class="block font-medium mb-1" for="sla_start">SLA Start</label>
        <input id="sla_start" type="datetime-local" v-model="slaStartAt" class="border rounded p-2 w-full" />
      </div>
      <div class="mb-4">
        <label class="block font-medium mb-1" for="sla_end">SLA End</label>
        <input id="sla_end" type="datetime-local" v-model="slaEndAt" class="border rounded p-2 w-full" />
      </div>
      <div class="mb-4" v-if="isEdit">
        <label class="block font-medium mb-1" for="status">Status</label>
        <select id="status" v-model="status" class="border rounded p-2 w-full">
          <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
        </select>
      </div>
      <JsonSchemaForm
        v-if="currentSchema"
        :key="typeId"
        v-model="formData"
        :schema="currentSchema"
      />
      <div v-if="serverError" class="text-red-600 text-sm mt-2">{{ serverError }}</div>
      <div class="mt-4">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded" :disabled="!canSubmit">Submit</button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import JsonSchemaForm from '@/components/forms/JsonSchemaForm.vue';

const router = useRouter();
const route = useRoute();

const types = ref<any[]>([]);
const typeId = ref<string | number>('');
const formData = ref<any>({});
const scheduledAt = ref('');
const slaStartAt = ref('');
const slaEndAt = ref('');
const status = ref('');
const serverError = ref('');
const originalStatus = ref('');

const statusOptions = ['draft', 'assigned', 'in_progress', 'completed', 'rejected', 'redo'];

const isEdit = computed(() => route.name === 'appointments.edit');

onMounted(async () => {
  const { data } = await api.get('/appointment-types');
  types.value = data;
  if (isEdit.value) {
    const res = await api.get(`/appointments/${route.params.id}`);
    const appt = res.data;
    typeId.value = appt.type?.id || appt.appointment_type_id;
    formData.value = appt.form_data || {};
    scheduledAt.value = toInput(appt.scheduled_at);
    slaStartAt.value = toInput(appt.sla_start_at);
    slaEndAt.value = toInput(appt.sla_end_at);
    status.value = appt.status;
    originalStatus.value = appt.status;
  }
});

function onTypeChange() {
  formData.value = {};
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

function toInput(v?: string) {
  return v ? v.substring(0, 16) : '';
}

function toIso(v: string) {
  return v ? new Date(v).toISOString() : undefined;
}

async function onSubmit() {
  serverError.value = '';
  const payload: any = {
    appointment_type_id: typeId.value,
    form_data: formData.value,
  };
  if (scheduledAt.value) payload.scheduled_at = toIso(scheduledAt.value);
  if (slaStartAt.value) payload.sla_start_at = toIso(slaStartAt.value);
  if (slaEndAt.value) payload.sla_end_at = toIso(slaEndAt.value);
  try {
    if (isEdit.value) {
      if (status.value && status.value !== originalStatus.value) {
        payload.status = status.value;
      }
      await api.patch(`/appointments/${route.params.id}`, payload);
      router.push({ name: 'appointments.details', params: { id: route.params.id } });
    } else {
      const res = await api.post('/appointments', payload);
      router.push({ name: 'appointments.details', params: { id: res.data.id } });
    }
  } catch (e: any) {
    serverError.value = e.message || 'Failed to save';
  }
}
</script>
