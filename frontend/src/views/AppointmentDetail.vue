<template>
  <Card v-if="appointment">
    <template #title>{{ appointment.title }}</template>
    <template #content>
      <ProgressBar :value="progress" class="mb-4" />
      <Steps :model="stepItems" :activeStep="activeIndex" class="mb-4" />
      <TabView v-model:activeIndex="activeIndex">
        <TabPanel header="Form">
          <FormRenderer
            v-if="appointment.form_schema"
            v-model="formData"
            :schema="appointment.form_schema"
            class="mb-4"
          />
          <KauInput v-model="kau" class="mb-4" />
          <Message v-if="errorMessage" severity="error" class="mb-4">
            {{ errorMessage }}
          </Message>
          <Button label="Complete Step" @click="completeStep" />
        </TabPanel>
        <TabPanel header="Photos">
          <PhotoCapture @update="onPhotos" class="mb-4" />
          <Galleria v-if="photoUrls.length" :value="photoUrls" :numVisible="5">
            <template #item="slotProps">
              <img :src="slotProps.item" class="w-full" />
            </template>
            <template #thumbnail="slotProps">
              <img :src="slotProps.item" class="w-20 h-20 object-cover" />
            </template>
          </Galleria>
        </TabPanel>
        <TabPanel header="Comments">
          <AppointmentComments
            v-if="appointment"
            :appointment-id="appointment.id"
            class="mt-2"
          />
        </TabPanel>
      </TabView>
      <div class="flex gap-2 mt-4">
        <Button label="Map" text @click="openMap" />
        <Button label="Call" text @click="call" />
      </div>
    </template>
  </Card>
  <ConfirmDialog />
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, onBeforeRouteLeave } from 'vue-router';
import { useAppointmentsStore } from '@/stores/appointments';
import { useDraftsStore } from '@/stores/drafts';
import PhotoCapture from '@/components/appointments/PhotoCapture.vue';
import KauInput from '@/components/appointments/KauInput.vue';
import FormRenderer from '@/components/appointments/FormRenderer.vue';
import AppointmentComments from '@/components/appointments/AppointmentComments.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressBar from 'primevue/progressbar';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Steps from 'primevue/steps';
import Message from 'primevue/message';
import Galleria from 'primevue/galleria';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

const route = useRoute();
const appointments = useAppointmentsStore();
const drafts = useDraftsStore();
const confirm = useConfirm();
const toast = useToast();

const appointment = ref<any>(null);
const kau = ref('');
const photos = ref<File[]>([]);
const formData = ref<Record<string, any>>({});
const activeIndex = ref(0);
const errorMessage = ref('');
const dirty = ref(false);
const stepItems = [{ label: 'Form' }, { label: 'Photos' }, { label: 'Comments' }];
const photoUrls = computed(() => photos.value.map((p) => URL.createObjectURL(p)));
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

watch([kau, formData], () => {
  dirty.value = true;
  errorMessage.value = '';
  saveDraft();
}, { deep: true });

function onPhotos(files: File[]) {
  photos.value = files;
  dirty.value = true;
  errorMessage.value = '';
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
  if (!Object.keys(formData.value).length) {
    errorMessage.value = 'Please complete the form before proceeding.';
    return;
  }
  appointment.value.completedSteps++;
  dirty.value = false;
  saveDraft();
  toast.add({ severity: 'success', summary: 'Step completed', life: 3000 });
}

onBeforeRouteLeave((to, from, next) => {
  if (dirty.value) {
    confirm.require({
      message: 'You have unsaved changes. Leave anyway?',
      accept: () => next(),
      reject: () => next(false),
    });
  } else {
    next();
  }
});

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
