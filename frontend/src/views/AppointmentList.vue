<template>
  <div>
    <Toolbar class="mb-4">
      <template #start>
        <h2 class="text-xl font-bold">Appointments</h2>
      </template>
      <template #end>
        <Button
          v-if="isAdmin"
          label="New Appointment"
          icon="pi pi-plus"
          @click="showDialog = true"
        />
      </template>
    </Toolbar>
    <DataTable :value="appointments" paginator :rows="10">
      <Column field="title" header="Title">
        <template #body="slotProps">
          <router-link
            :to="`/appointments/${slotProps.data.id}`"
            class="text-blue-600"
          >
            {{
              slotProps.data.title
            }} - {{ slotProps.data.completedSteps }}/{{ slotProps.data.totalSteps }}
          </router-link>
        </template>
      </Column>
      <Column v-if="isAdmin" header="Actions">
        <template #body="slotProps">
          <Button
            icon="pi pi-trash"
            severity="danger"
            text
            @click="remove(slotProps.data.id)"
          />
        </template>
      </Column>
    </DataTable>
    <Dialog v-model:visible="showDialog" header="New Appointment" modal>
      <div class="flex flex-col gap-2">
        <InputText v-model="newTitle" placeholder="Title" />
        <Button label="Save" @click="create" />
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useAppointmentsStore } from '@/stores/appointments';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Toolbar from 'primevue/toolbar';

const store = useAppointmentsStore();
const { appointments } = storeToRefs(store);
const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.roles?.some((r: any) => r.name === 'ClientAdmin'));
const showDialog = ref(false);
const newTitle = ref('');

onMounted(() => {
  store.fetch();
});

async function create() {
  if (!newTitle.value) return;
  await api.post('/appointments', { title: newTitle.value });
  await store.fetch();
  newTitle.value = '';
  showDialog.value = false;
}

async function remove(id: number) {
  if (confirm('Delete appointment?')) {
    await api.delete(`/appointments/${id}`);
    await store.fetch();
  }
}
</script>
