<template>
  <Card v-if="appointment">
    <template #title>{{ appointment.title }}</template>
    <template #content>
      <ProgressBar :value="progress" class="mb-4" />
      <FormRenderer
        v-if="appointment.form_schema"
        v-model="formData"
        :schema="appointment.form_schema"
        class="mb-4"
      />
      <KauInput v-model="kau" class="mb-4" />
      <PhotoCapture @update="onPhotos" class="mb-4" />
      <div class="flex gap-2 mb-4">
        <Button label="Map" text @click="openMap" />
        <Button label="Call" text @click="call" />
      </div>
      <Button label="Complete Step" @click="completeStep" />
      <AppointmentComments
        v-if="appointment"
        :appointment-id="appointment.id"
        class="mt-6"
      />
    </template>
  </Card>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAppointmentsStore } from '@/stores/appointments';
import { useDraftsStore } from '@/stores/drafts';
import PhotoCapture from '@/components/appointments/PhotoCapture.vue';
import KauInput from '@/components/appointments/KauInput.vue';
import FormRenderer from '@/components/appointments/FormRenderer.vue';
import AppointmentComments from '@/components/appointments/AppointmentComments.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressBar from 'primevue/progressbar';

const route = useRoute();
const appointments = useAppointmentsStore();
const drafts = useDraftsStore();

const appointment = ref<any>(null);
const kau = ref('');
const photos = ref<File[]>([]);
const formData = ref<Record<string, any>>({});
const progress = computed(() =>
  appointment.value
    ? (appointment.value.completedSteps / appointment.value.totalSteps) * 100
    : 0,
);

onMounted(async () => {
  const id = route.params.id as string;
  appointment.value = await appointments.get(id);
  const draft = await drafts.load(id);
  if (draft) {
    kau.value = draft.kau || '';
    photos.value = draft.photos || [];
    formData.value = draft.formData || {};
  }
});

function onPhotos(files: File[]) {
  photos.value = files;
  saveDraft();
}

function saveDraft() {
  if (appointment.value) {
    drafts.save(appointment.value.id, {
      kau: kau.value,
      photos: photos.value,
      formData: formData.value,
    });
  }
}

function completeStep() {
  if (!appointment.value) return;
  appointment.value.completedSteps++;
  saveDraft();
}

function openMap() {
  if (appointment.value?.location) {
    window.open(`https://maps.google.com/?q=${appointment.value.location}`, '_blank');
  }
}

function call() {
  if (appointment.value?.phone) {
    window.open(`tel:${appointment.value.phone}`, '_self');
  }
}
</script>
