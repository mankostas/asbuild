<template>
  <div>
    <div class="flex gap-4 mb-4">
      <div class="flex flex-col">
        <label for="calendar-status" class="mb-1 text-sm">Status</label>
        <select id="calendar-status" v-model="statusId" class="border rounded p-2">
          <option value="">All Statuses</option>
          <option v-for="s in statusOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>
      <div class="flex flex-col">
        <label for="calendar-type" class="mb-1 text-sm">Type</label>
        <select id="calendar-type" v-model="typeId" class="border rounded p-2">
          <option value="">All Types</option>
          <option v-for="t in typeOptions" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </div>
      <div class="flex flex-col">
        <label for="calendar-team" class="mb-1 text-sm">Team</label>
        <select id="calendar-team" v-model="teamId" class="border rounded p-2">
          <option value="">All Teams</option>
          <option v-for="t in teamOptions" :key="t.id" :value="t.id">{{ t.label || t.name }}</option>
        </select>
      </div>
      <div class="flex flex-col">
        <label for="calendar-employee" class="mb-1 text-sm">Employee</label>
        <select id="calendar-employee" v-model="employeeId" class="border rounded p-2">
          <option value="">All Employees</option>
          <option v-for="e in employeeOptions" :key="e.id" :value="e.id">{{ e.label || e.name }}</option>
        </select>
      </div>
    </div>
    <div class="card">
      <div class="dashcode-calender">
        <FullCalendar :options="calendarOptions" />
      </div>
    </div>
    <Modal :activeModal="showCreate" title="Create Appointment" @close="showCreate = false">
      <div class="flex justify-end space-x-3">
        <button class="btn btn-light" @click="showCreate = false">Cancel</button>
        <button class="btn btn-success" @click="create">Create</button>
      </div>
    </Modal>
    <Modal :activeModal="showEvent" title="Appointment" @close="showEvent = false">
      <div class="flex justify-end space-x-3">
        <button class="btn btn-light" @click="view">View</button>
        <button class="btn btn-success" @click="edit">Edit</button>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import Modal from '@/components/ui/Modal';
import { useCalendarStore } from '@/stores/calendar';
import { useStatusesStore } from '@/stores/statuses';
import { useTypesStore } from '@/stores/types';
import { useLookupsStore } from '@/stores/lookups';
import { toISO } from '@/utils/datetime';

const router = useRouter();
const store = useCalendarStore();
const statusStore = useStatusesStore();
const typesStore = useTypesStore();
const lookupsStore = useLookupsStore();

const showCreate = ref(false);
const showEvent = ref(false);
const selectedDate = ref('');
const selectedEvent = ref<any>(null);
const currentRange = ref<{ startStr: string; endStr: string } | null>(null);

const statusOptions = ref<any[]>([]);
const typeOptions = ref<any[]>([]);
const teamOptions = ref<any[]>([]);
const employeeOptions = ref<any[]>([]);

const statusId = ref('');
const typeId = ref('');
const teamId = ref('');
const employeeId = ref('');

onMounted(async () => {
  statusOptions.value = await statusStore.fetch('tenant');
  typeOptions.value = await typesStore.fetch('tenant');
  const assignees = await lookupsStore.fetchAssignees('all');
  teamOptions.value = assignees.filter((a: any) => a.kind === 'team');
  employeeOptions.value = assignees.filter((a: any) => a.kind === 'employee');
});

async function loadEvents(info?: { startStr: string; endStr: string }) {
  if (info) currentRange.value = info;
  if (!currentRange.value) return;
  store.filters = {
    team_id: teamId.value,
    employee_id: employeeId.value,
    type_id: typeId.value,
    status_id: statusId.value,
  } as any;
  const start = toISO(currentRange.value.startStr);
  const end = toISO(currentRange.value.endStr);
  await store.fetch(start, end);
}

watch([teamId, employeeId, typeId, statusId], () => {
  loadEvents();
});

const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  events: store.events,
  datesSet(info: any) {
    loadEvents({ startStr: info.startStr, endStr: info.endStr });
  },
  dateClick(info: any) {
    selectedDate.value = toISO(info.dateStr);
    showCreate.value = true;
  },
  eventClick(info: any) {
    selectedEvent.value = info.event;
    showEvent.value = true;
  },
}));

function create() {
  showCreate.value = false;
  router.push({ name: 'appointments.create', query: { date: selectedDate.value } });
}

function view() {
  if (!selectedEvent.value) return;
  showEvent.value = false;
  router.push({ name: 'appointments.details', params: { id: selectedEvent.value.id } });
}

function edit() {
  if (!selectedEvent.value) return;
  showEvent.value = false;
  router.push({ name: 'appointments.edit', params: { id: selectedEvent.value.id } });
}
</script>

