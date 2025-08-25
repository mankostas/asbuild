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
          <div class="flex gap-2">
            <Button
              icon="pi pi-pencil"
              text
              @click="openEdit(slotProps.data)"
            />
            <Button
              icon="pi pi-trash"
              severity="danger"
              text
              @click="remove(slotProps.data.id)"
            />
          </div>
        </template>
      </Column>
    </DataTable>
    <Dialog v-model:visible="showDialog" header="New Appointment" modal>
      <div class="flex flex-col gap-2">
        <InputText v-model="newTitle" placeholder="Title" />
        <Button label="Save" @click="create" />
      </div>
    </Dialog>
    <Dialog v-model:visible="showEdit" header="Edit Appointment" modal>
      <div class="flex flex-col gap-3">
        <InputText v-model="editTitle" placeholder="Title" />
        <div class="flex justify-end gap-2">
          <Button label="Cancel" text @click="showEdit = false" />
          <Button label="Update" @click="update" />
        </div>
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
const showEdit = ref(false);
const editTitle = ref('');
const editingId = ref<number | null>(null);

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

function openEdit(appointment: any) {
  editingId.value = appointment.id;
  editTitle.value = appointment.title;
  showEdit.value = true;
}

async function update() {
  if (!editingId.value || !editTitle.value) return;
  await api.put(`/appointments/${editingId.value}`, { title: editTitle.value });
  await store.fetch();
  showEdit.value = false;
  editingId.value = null;
  editTitle.value = '';
}
</script>
