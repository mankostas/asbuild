<template>
  <div>
    <Toolbar class="mb-4">
      <template #start>
        <h2 class="text-xl font-bold">{{ t('routes.appointments') }}</h2>
      </template>
      <template #end>
        <InputText
          v-model="filters.global"
          :placeholder="t('appointments.filters.global')"
          class="mr-2"
        />
        <Button
          v-if="isAdmin"
          :label="t('appointments.new')"
          icon="pi pi-plus"
          @click="openNew"
        />
      </template>
    </Toolbar>
    <DataTable
      :value="filteredAppointments"
      :loading="loading"
      paginator
      :rows="10"
      :rowsPerPageOptions="[10, 25, 50]"
      responsiveLayout="scroll"
    >
      <template #empty>
        {{ t('appointments.messages.empty') }}
      </template>
      <template #loading>
        <div class="flex flex-col gap-2">
          <Skeleton v-for="i in 3" :key="i" height="2rem" />
        </div>
      </template>
      <Column field="title" :header="t('appointments.form.title')" sortable>
        <template #body="slotProps">
          <router-link
            :to="`/appointments/${slotProps.data.id}`"
            class="text-blue-600"
          >
            {{ slotProps.data.title }}
          </router-link>
        </template>
        <template #filter>
          <InputText
            v-model="filters.title"
            :placeholder="t('appointments.form.title')"
            class="p-column-filter"
          />
        </template>
      </Column>
      <Column field="status" :header="t('appointments.form.status')" sortable>
        <template #body="slotProps">
          {{ statusLabel(slotProps.data.status) }}
        </template>
        <template #filter>
          <MultiSelect
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('appointments.filters.statusPlaceholder')"
            class="p-column-filter"
          />
        </template>
      </Column>
      <Column field="scheduled_at" :header="t('appointments.form.date')" sortable />
      <Column v-if="isAdmin" :header="t('actions.actions')">
        <template #body="slotProps">
          <div class="flex gap-2">
            <Button
              icon="pi pi-pencil"
              class="p-button-rounded p-button-text"
              @click="openEdit(slotProps.data)"
            />
            <Button
              icon="pi pi-trash"
              class="p-button-rounded p-button-text"
              @click="confirmDelete(slotProps.data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showCreate" :header="t('appointments.new')" modal>
      <div class="flex flex-col gap-3">
        <InputText v-model="form.title" :placeholder="t('appointments.form.title')" />
        <Calendar v-model="form.scheduled_at" show-icon date-format="yy-mm-dd" />
        <Dropdown
          v-model="form.status"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
        />
        <Message v-if="formErrors.title" severity="error">
          {{ formErrors.title }}
        </Message>
        <div class="flex justify-end gap-2">
          <Button :label="t('actions.cancel')" text @click="showCreate = false" />
          <Button :label="t('actions.save')" @click="create" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="showEdit" :header="t('appointments.edit')" modal>
      <div class="flex flex-col gap-3">
        <InputText v-model="form.title" :placeholder="t('appointments.form.title')" />
        <Calendar v-model="form.scheduled_at" show-icon date-format="yy-mm-dd" />
        <Dropdown
          v-model="form.status"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
        />
        <Message v-if="formErrors.title" severity="error">
          {{ formErrors.title }}
        </Message>
        <div class="flex justify-end gap-2">
          <Button :label="t('actions.cancel')" text @click="showEdit = false" />
          <Button :label="t('actions.save')" @click="update" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useAppointmentsStore } from '@/stores/appointments';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import Toolbar from 'primevue/toolbar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import MultiSelect from 'primevue/multiselect';
import Skeleton from 'primevue/skeleton';
import Message from 'primevue/message';
import { useNotify } from '@/plugins/notify';
import Swal from 'sweetalert2';
import { useI18n } from 'vue-i18n';
import { filterAppointments, Appointment } from '@/utils/appointmentFilters';

const { t } = useI18n();
const notify = useNotify();

const store = useAppointmentsStore();
const { appointments } = storeToRefs(store);
const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.roles?.some((r: any) => r.name === 'ClientAdmin'));

const loading = ref(true);
const filters = ref({ global: '', title: '', status: [] as string[] });

const statusOptions = computed(() => [
  { label: t('appointments.status.draft'), value: 'draft' },
  { label: t('appointments.status.scheduled'), value: 'scheduled' },
  { label: t('appointments.status.completed'), value: 'completed' },
]);

const filteredAppointments = computed(() =>
  filterAppointments(appointments.value as Appointment[], filters.value),
);

const showCreate = ref(false);
const showEdit = ref(false);
const form = ref<{ title: string; scheduled_at: any; status: string }>({
  title: '',
  scheduled_at: null,
  status: 'draft',
});
const formErrors = ref<{ title?: string }>({});
const editingId = ref<number | null>(null);

async function load() {
  loading.value = true;
  await store.fetch();
  loading.value = false;
}

onMounted(load);

function openNew() {
  form.value = { title: '', scheduled_at: null, status: 'draft' };
  formErrors.value = {};
  showCreate.value = true;
}

function openEdit(a: any) {
  editingId.value = a.id;
  form.value = { title: a.title, scheduled_at: a.scheduled_at, status: a.status };
  formErrors.value = {};
  showEdit.value = true;
}

function statusLabel(value: string) {
  const opt = statusOptions.value.find((o) => o.value === value);
  return opt ? opt.label : value;
}

async function create() {
  if (!form.value.title) {
    formErrors.value.title = t('appointments.messages.titleRequired');
    return;
  }
  try {
    await api.post('/appointments', {
      title: form.value.title,
      scheduled_at: form.value.scheduled_at,
      status: form.value.status,
    });
    await store.fetch();
    notify.success(t('appointments.messages.created'));
    showCreate.value = false;
  } catch (e) {
    notify.error(t('appointments.messages.error'));
  }
}

async function update() {
  if (!editingId.value) return;
  if (!form.value.title) {
    formErrors.value.title = t('appointments.messages.titleRequired');
    return;
  }
  try {
    await api.put(`/appointments/${editingId.value}`, {
      title: form.value.title,
      scheduled_at: form.value.scheduled_at,
      status: form.value.status,
    });
    await store.fetch();
    notify.success(t('appointments.messages.updated'));
    showEdit.value = false;
  } catch (e) {
    notify.error(t('appointments.messages.error'));
  }
}

async function confirmDelete(a: any) {
  const res = await Swal.fire({
    title: t('appointments.messages.deleteConfirm'),
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    try {
      await api.delete(`/appointments/${a.id}`);
      await store.fetch();
      notify.success(t('appointments.messages.deleted'));
    } catch (e) {
      notify.error(t('appointments.messages.error'));
    }
  }
}
</script>
