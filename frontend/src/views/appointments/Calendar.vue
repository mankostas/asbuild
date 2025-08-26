<template>
  <div>
    <div class="card">
      <div class="dashcode-calender">
        <FullCalendar :options="calendarOptions" />
      </div>
    </div>
    <Modal :activeModal="showCreate" @close="showCreate = false" title="Create Appointment">
      <div class="flex justify-end space-x-3">
        <button class="btn btn-light" @click="showCreate = false">Cancel</button>
        <button class="btn btn-success" @click="create">Create</button>
      </div>
    </Modal>
    <Modal :activeModal="showEvent" @close="showEvent = false" title="Appointment">
      <div class="flex justify-end space-x-3">
        <button class="btn btn-light" @click="view">View</button>
        <button class="btn btn-success" @click="edit">Edit</button>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import '@fullcalendar/core/vdom';
import Modal from '@/components/ui/Modal';
import { useAppointmentsStore } from '@/stores/appointments';

const router = useRouter();
const store = useAppointmentsStore();

const showCreate = ref(false);
const showEvent = ref(false);
const selectedDate = ref('');
const selectedEvent = ref<any>(null);

onMounted(() => {
  if (!store.appointments.length) {
    store.fetch();
  }
});

const events = computed(() =>
  store.appointments.map((a: any) => ({
    id: a.id,
    title: a.title || a.type?.name || `Appointment ${a.id}`,
    start: a.scheduled_at,
    end: a.sla_end_at || a.scheduled_at,
    extendedProps: { appointment: a },
  })),
);

const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  events: events.value,
  dateClick(info: any) {
    selectedDate.value = info.dateStr;
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

