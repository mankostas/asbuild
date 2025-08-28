<template>
    <div>
      <div class="flex items-center justify-end mb-4">
        <RouterLink
          class="bg-blue-600 text-white px-4 py-2 rounded flex items-center gap-2"
          :to="{ name: 'appointments.create' }"
        >
          <Icon icon="heroicons-outline:plus" class="w-5 h-5" />
          New
        </RouterLink>
      </div>
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
          <button class="text-blue-600" title="View" @click="view(row.id)">
            <Icon icon="heroicons-outline:eye" class="w-5 h-5" />
          </button>
          <button class="text-blue-600" title="Edit" @click="edit(row.id)">
            <Icon icon="heroicons-outline:pencil-square" class="w-5 h-5" />
          </button>
          <button class="text-red-600" title="Delete" @click="remove(row.id)">
            <Icon icon="heroicons-outline:trash" class="w-5 h-5" />
          </button>
          <button
            v-for="s in getChangeActions(row)"
            :key="s"
            class="text-blue-600"
            :title="`Mark ${s.replace(/_/g, ' ')}`"
            @click="updateStatus(row, s)"
          >
            <Icon :icon="statusIcons[s] || 'heroicons-outline:arrow-right'" class="w-5 h-5" />
          </button>
        </div>
      </template>
    </DashcodeServerTable>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import DashcodeServerTable from '@/components/datatable/DashcodeServerTable.vue';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';
import Icon from '@/components/ui/Icon';
import Swal from 'sweetalert2';

const router = useRouter();
const notify = useNotify();

const statusFilter = ref('');
const startDate = ref('');
const endDate = ref('');
const tableKey = ref(0);

const statusOptions = ref<string[]>([]);

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

const statusIcons: Record<string, string> = {
  in_progress: 'heroicons-outline:play',
  completed: 'heroicons-outline:check',
  rejected: 'heroicons-outline:x-mark',
  redo: 'heroicons-outline:arrow-path',
};

const all = ref<any[]>([]);

onMounted(async () => {
  const { data } = await api.get('/statuses');
  statusOptions.value = data.map((s: any) => s.name);
});

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

function edit(id: number) {
  router.push({ name: 'appointments.edit', params: { id } });
}

async function remove(id: number) {
  const res = await Swal.fire({
    title: 'Delete appointment?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    await api.delete(`/appointments/${id}`);
    all.value = all.value.filter((r) => r.id !== id);
    reload();
  }
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

function getChangeActions(row: any) {
  const target = all.value.find((r) => r.id === row.id);
  if (!target) return [];
  const map = target.type?.statuses || {};
  return map[target.status] || [];
}
</script>

