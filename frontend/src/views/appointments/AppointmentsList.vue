<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Appointments</h2>
    <div class="flex gap-4 mb-4">
      <select v-model="statusFilter" class="border rounded p-2">
        <option value="">All Statuses</option>
        <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
      </select>
      <input type="date" v-model="startDate" class="border rounded p-2" />
      <input type="date" v-model="endDate" class="border rounded p-2" />
    </div>
    <DashcodeServerTable
      :key="tableKey"
      :columns="columns"
      :fetcher="fetchAppointments"
    >
      <template #actions="{ row }">
        <div class="flex gap-2">
          <button class="text-blue-600" @click="view(row.id)">View</button>
          <button
            v-for="a in changeActions"
            :key="a.value"
            class="text-blue-600"
            @click="updateStatus(row, a.value)"
          >
            Mark {{ a.label }}
          </button>
        </div>
      </template>
    </DashcodeServerTable>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import DashcodeServerTable from '@/components/datatable/DashcodeServerTable.vue';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';

const router = useRouter();
const notify = useNotify();

const statusFilter = ref('');
const startDate = ref('');
const endDate = ref('');
const tableKey = ref(0);

const statusOptions = ['draft', 'assigned', 'in_progress', 'completed', 'rejected', 'redo'];

const columns = [
  { label: 'ID', field: 'id', sortable: true },
  { label: 'Type', field: 'type', sortable: false },
  { label: 'Status', field: 'status', sortable: false, html: true },
  { label: 'Scheduled', field: 'scheduled_at', sortable: true },
  { label: 'SLA End', field: 'sla_end_at', sortable: true },
  { label: 'Started', field: 'started_at', sortable: true },
  { label: 'Completed', field: 'completed_at', sortable: true },
];

const statusClasses: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-800',
  assigned: 'bg-blue-100 text-blue-800',
  in_progress: 'bg-yellow-100 text-yellow-800',
  completed: 'bg-green-100 text-green-800',
  rejected: 'bg-red-100 text-red-800',
  redo: 'bg-purple-100 text-purple-800',
};

const all = ref<any[]>([]);

async function fetchAppointments({ page, perPage, sort, search }: any) {
  if (!all.value.length) {
    const { data } = await api.get('/appointments');
    all.value = data;
  }
  let rows = all.value.slice();

  if (statusFilter.value) {
    rows = rows.filter((r) => r.status === statusFilter.value);
  }
  if (startDate.value) {
    const start = new Date(startDate.value);
    rows = rows.filter(
      (r) => r.scheduled_at && new Date(r.scheduled_at) >= start,
    );
  }
  if (endDate.value) {
    const end = new Date(endDate.value);
    rows = rows.filter(
      (r) => r.scheduled_at && new Date(r.scheduled_at) <= end,
    );
  }
  if (search) {
    const q = String(search).toLowerCase();
    rows = rows.filter((r) =>
      Object.values(r).some((v) => String(v ?? '').toLowerCase().includes(q)),
    );
  }
  if (sort && sort.field) {
    rows.sort((a, b) => {
      const fa = a[sort.field] ?? '';
      const fb = b[sort.field] ?? '';
      if (fa < fb) return sort.type === 'asc' ? -1 : 1;
      if (fa > fb) return sort.type === 'asc' ? 1 : -1;
      return 0;
    });
  }
  const total = rows.length;
  const start = (page - 1) * perPage;
  const paged = rows.slice(start, start + perPage).map((r) => ({
    id: r.id,
    type: r.type?.name || '—',
    status: `<span class="px-2 py-1 rounded-full text-xs font-semibold ${statusClasses[r.status] ?? ''}">${r.status.replace(/_/g, ' ')}</span>`,
    scheduled_at: r.scheduled_at,
    sla_end_at: r.sla_end_at,
    started_at: r.started_at,
    completed_at: r.completed_at,
  }));
  return { rows: paged, total };
}

function reload() {
  tableKey.value++;
}

watch([statusFilter, startDate, endDate], reload);

function view(id: number) {
  router.push({ name: 'appointments.details', params: { id } });
}

async function updateStatus(row: any, status: string) {
  try {
    await api.patch(`/appointments/${row.id}`, { status });
    const target = all.value.find((r) => r.id === row.id);
    if (target) target.status = status;
    reload();
  } catch (e: any) {
    if (e.status === 422) {
      notify.error('Invalid status transition');
    }
  }
}

const changeActions = [
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Completed', value: 'completed' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Redo', value: 'redo' },
];
</script>

