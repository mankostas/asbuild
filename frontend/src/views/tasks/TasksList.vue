<template>
    <div class="space-y-4">
      <div class="flex items-center justify-end mb-4">
        <Button
          v-if="hasAny(['tasks.create', 'tasks.manage'])"
          :link="{ name: 'tasks.create' }"
          btnClass="btn-primary flex items-center gap-2"
          icon="heroicons-outline:plus"
          :text="t('tasks.new')"
        />
      </div>

      <div class="flex items-center gap-2 mb-4">
        <Button
          btnClass="btn-outline-dark"
          :aria-expanded="showFilters.toString()"
          aria-controls="task-filters"
          @click="toggleFilters"
        >
          {{ t('tasks.filters.toggle', 'Filters') }}
        </Button>
        <Button
          btnClass="btn-outline-dark"
          :aria-label="t('tasks.filters.saveView')"
          @click="saveView"
        >
          {{ t('tasks.filters.saveView') }}
        </Button>
      </div>

      <div v-if="showFilters" id="task-filters" class="p-4 bg-white rounded-2xl shadow mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <Select
            id="filter-status"
            v-model="statusFilter"
            :label="t('tasks.filters.status')"
            :options="[{ value: '', label: t('tasks.filters.allStatuses') }, ...statusOptions.map((s) => ({ value: s, label: s }))]"
          />
          <Select
            id="filter-type"
            v-model="typeFilter"
            :label="t('tasks.filters.type')"
            :options="[{ value: '', label: t('tasks.filters.allTypes') }, ...typeOptions.map((type) => ({ value: type.id, label: type.name }))]"
          />
          <div class="flex flex-col sm:col-span-2 lg:col-span-1">
            <span id="filter-assignee-label" class="mb-1 text-sm">{{ t('tasks.filters.assignee') }}</span>
            <AssigneePicker v-model="assigneeFilter" :aria-labelledby="'filter-assignee-label'" />
          </div>
          <Select
            id="filter-priority"
            v-model="priorityFilter"
            :label="t('tasks.filters.priority')"
            :options="[{ value: '', label: t('tasks.filters.allPriorities') }, ...priorityOptions.map((p) => ({ value: p.value, label: t(`tasks.priority.${p.value}`) }))]"
          />
          <Textinput
            id="filter-due-start"
            v-model="dueStart"
            type="date"
            :label="t('tasks.filters.dueFrom')"
          />
          <Textinput
            id="filter-due-end"
            v-model="dueEnd"
            type="date"
            :label="t('tasks.filters.dueTo')"
          />
          <Checkbox
            id="filter-photos"
            v-model="hasPhotos"
            :label="t('tasks.filters.hasPhotos')"
          />
          <Checkbox
            id="filter-mine"
            v-model="mine"
            :label="t('tasks.filters.mine')"
          />
        </div>
      </div>

        <div v-if="selected.length" class="mb-4 flex items-center gap-2">
          <Select
            id="bulk-status"
            v-model="bulkStatus"
            :label="t('tasks.status.update')"
            :options="[{ value: '', label: t('tasks.filters.selectStatus', 'Select status') }, ...bulkStatusOptions.map((s) => ({ value: s, label: s }))]"
          />
          <Button
            btnClass="btn-dark"
            :disabled="!bulkStatus"
            :aria-label="t('tasks.status.update')"
            @click="applyBulkStatus"
          >
            {{ t('actions.save') }}
          </Button>
      </div>
    <DashcodeServerTable
      :key="tableKey"
      :columns="columns"
      :fetcher="fetchTasks"
      :initial-sort="prefs.sort"
      :initial-per-page="prefs.pageSize || 10"
    >
      <template #actions="{ row }">
        <div class="flex gap-2 items-center">
          <input
            v-model="selected"
            :value="row.id"
            type="checkbox"
            :aria-label="`Select task ${row.id}`"
            @keyup.enter="toggleSelect(row.id)"
          />
          <button
            class="text-blue-600"
            title="View"
            aria-label="View"
            @click="view(row.id)"
            @keyup.enter="view(row.id)"
          >
            <Icon icon="heroicons-outline:eye" class="w-5 h-5" />
          </button>
          <button
            v-if="hasAny(['tasks.update', 'tasks.manage'])"
            class="text-blue-600"
            title="Edit"
            aria-label="Edit"
            @click="edit(row.id)"
            @keyup.enter="edit(row.id)"
          >
            <Icon icon="heroicons-outline:pencil-square" class="w-5 h-5" />
          </button>
          <button
            v-if="hasAny(['tasks.delete', 'tasks.manage'])"
            class="text-red-600"
            title="Delete"
            aria-label="Delete"
            @click="remove(row.id)"
            @keyup.enter="remove(row.id)"
          >
            <Icon icon="heroicons-outline:trash" class="w-5 h-5" />
          </button>
          <template v-if="hasAny(['tasks.update', 'tasks.manage'])">
            <button
              v-for="s in getChangeActions(row)"
              :key="s"
              class="text-blue-600"
              :title="`Mark ${s.replace(/_/g, ' ')}`"
              :aria-label="`Mark ${s.replace(/_/g, ' ')}`"
              @click="updateStatus(row, s)"
              @keyup.enter="updateStatus(row, s)"
            >
              <Icon :icon="statusIcons[s] || 'heroicons-outline:arrow-right'" class="w-5 h-5" />
            </button>
          </template>
        </div>
      </template>
    </DashcodeServerTable>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import DashcodeServerTable from '@/components/datatable/DashcodeServerTable.vue';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';
import Icon from '@/components/ui/Icon';
import Swal from 'sweetalert2';
import { parseISO, formatDisplay } from '@/utils/datetime';
import { hasAny, useAuthStore } from '@/stores/auth';
import AssigneePicker from '@/components/tasks/AssigneePicker.vue';
import Button from '@/components/ui/Button/index.vue';
import Select from '@/components/ui/Select/index.vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import Checkbox from '@/components/ui/Checkbox/index.vue';
import { loadListPrefs, saveListPrefs, type ListPrefs } from '@/services/listPrefs';

const router = useRouter();
const notify = useNotify();
const { t } = useI18n();
const auth = useAuthStore();

const showFilters = ref(false);
const statusFilter = ref('');
const typeFilter = ref('');
const assigneeFilter = ref<{ id: number } | null>(null);
const priorityFilter = ref('');
const dueStart = ref('');
const dueEnd = ref('');
const hasPhotos = ref(false);
const mine = ref(false);

const prefs = ref<ListPrefs>(loadListPrefs(auth.user?.id || 0) || ({} as any));
const currentSort = ref<any>(prefs.value.sort || null);
const pageSize = ref<number>(prefs.value.pageSize || 10);

const tableKey = ref(0);

const statusOptions = ref<string[]>([]);
const typeOptions = ref<any[]>([]);
const priorityOptions = [
  { value: 'low' },
  { value: 'normal' },
  { value: 'high' },
  { value: 'urgent' },
];

const columns = [
  { label: 'ID', field: 'id', sortable: true },
  { label: 'Type', field: 'type', sortable: false },
  { label: 'Priority', field: 'priority', sortable: true, html: true },
  { label: 'Status', field: 'status', sortable: false, html: true },
  { label: 'Scheduled', field: 'scheduled_at', sortable: true },
  { label: 'SLA End', field: 'sla_end_at', sortable: true, html: true },
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

const priorityClasses: Record<string, string> = {
  low: 'bg-slate-100 text-slate-800',
  normal: 'bg-slate-100 text-slate-800',
  high: 'bg-orange-100 text-orange-800',
  urgent: 'bg-red-100 text-red-800',
};

function slaBadgeClass(date: string) {
  const due = new Date(date).getTime();
  const now = Date.now();
  if (due < now) return 'bg-red-100 text-red-800';
  if (due - now < 48 * 3600 * 1000) return 'bg-yellow-100 text-yellow-800';
  return 'bg-green-100 text-green-800';
}

const statusIcons: Record<string, string> = {
  in_progress: 'heroicons-outline:play',
  completed: 'heroicons-outline:check',
  rejected: 'heroicons-outline:x-mark',
  redo: 'heroicons-outline:arrow-path',
};

const selected = ref<number[]>([]);
const bulkStatus = ref('');
const all = ref<any[]>([]);

const bulkStatusOptions = computed(() => {
  if (!selected.value.length) return [] as string[];
  const first = all.value.find((r) => r.id === selected.value[0]);
  let allowed = first ? getChangeActions(first) : [];
  for (const id of selected.value.slice(1)) {
    const row = all.value.find((r) => r.id === id);
    const actions = row ? getChangeActions(row) : [];
    allowed = allowed.filter((a: string) => actions.includes(a));
  }
  return allowed;
});

onMounted(async () => {
  const [statusRes, typeRes] = await Promise.all([
    api.get('/task-statuses'),
    api.get('/task-types'),
  ]);
  const statusData = statusRes.data?.data ?? statusRes.data;
  statusOptions.value = statusData.map((s: any) => s.name);
  typeOptions.value = typeRes.data?.data ?? typeRes.data;

  if (prefs.value.filters) {
    statusFilter.value = prefs.value.filters.status || '';
    typeFilter.value = prefs.value.filters.type || '';
    assigneeFilter.value = prefs.value.filters.assignee || null;
    priorityFilter.value = prefs.value.filters.priority || '';
    dueStart.value = prefs.value.filters.dueStart || '';
    dueEnd.value = prefs.value.filters.dueEnd || '';
    hasPhotos.value = prefs.value.filters.hasPhotos || false;
    mine.value = prefs.value.filters.mine || false;
  }
});

function toggleFilters() {
  showFilters.value = !showFilters.value;
}

function saveView() {
  if (!auth.user) return;
  const newPrefs: ListPrefs = {
    filters: {
      status: statusFilter.value,
      type: typeFilter.value,
      assignee: assigneeFilter.value,
      priority: priorityFilter.value,
      dueStart: dueStart.value,
      dueEnd: dueEnd.value,
      hasPhotos: hasPhotos.value,
      mine: mine.value,
    },
    sort: currentSort.value,
    pageSize: pageSize.value,
  };
  saveListPrefs(auth.user.id, newPrefs);
  prefs.value = newPrefs;
}

function toggleSelect(id: number) {
  const i = selected.value.indexOf(id);
  if (i >= 0) selected.value.splice(i, 1);
  else selected.value.push(id);
}

async function applyBulkStatus() {
  for (const id of selected.value) {
    const row = all.value.find((r) => r.id === id);
    if (!row) continue;
    if (!hasAny(['tasks.update', 'tasks.manage'])) continue;
    if (!getChangeActions(row).includes(bulkStatus.value)) continue;
    await api.post(`/tasks/${id}/status`, { status: bulkStatus.value });
    row.status = bulkStatus.value;
  }
  selected.value = [];
  bulkStatus.value = '';
  reload();
}

async function fetchTasks({ page, perPage, sort, search }: any) {
  currentSort.value = sort || null;
  pageSize.value = perPage;
  if (!all.value.length) {
    const { data } = await api.get('/tasks');
    all.value = data?.data ?? data;
  }
  let rows = all.value.slice();

  if (statusFilter.value) {
    rows = rows.filter((r) => r.status === statusFilter.value);
  }
  if (typeFilter.value) {
    rows = rows.filter((r) => r.type && r.type.id === Number(typeFilter.value));
  }
  if (assigneeFilter.value) {
    rows = rows.filter(
      (r) => r.assignee && r.assignee.id === assigneeFilter.value.id,
    );
  }
  if (priorityFilter.value) {
    rows = rows.filter((r) => r.priority === priorityFilter.value);
  }
  if (dueStart.value) {
    const start = parseISO(dueStart.value);
    rows = rows.filter((r) => r.due_at && parseISO(r.due_at) >= start);
  }
  if (dueEnd.value) {
    const end = parseISO(dueEnd.value);
    rows = rows.filter((r) => r.due_at && parseISO(r.due_at) <= end);
  }
  if (hasPhotos.value) {
    rows = rows.filter((r) => r.photos && r.photos.length);
  }
  if (mine.value && auth.user) {
    rows = rows.filter((r) => r.assignee && r.assignee.id === auth.user.id);
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
    priority: r.priority
      ? `<span class="badge ${priorityClasses[r.priority] || ''}">${t(`tasks.priority.${r.priority}`)}</span>`
      : '',
    status: `<span class="px-2 py-1 rounded-full text-xs font-semibold ${statusClasses[r.status] ?? ''}">${r.status.replace(/_/g, ' ')}</span>`,
    scheduled_at: r.scheduled_at ? formatDisplay(r.scheduled_at) : '',
    sla_end_at: r.sla_end_at
      ? `<span class="badge ${slaBadgeClass(r.sla_end_at)}">${formatDisplay(r.sla_end_at)}</span>`
      : '',
    started_at: r.started_at ? formatDisplay(r.started_at) : '',
    completed_at: r.completed_at ? formatDisplay(r.completed_at) : '',
  }));
  return { rows: paged, total };
}

function reload() {
  tableKey.value++;
}

watch(
  [
    statusFilter,
    typeFilter,
    assigneeFilter,
    priorityFilter,
    dueStart,
    dueEnd,
    hasPhotos,
    mine,
  ],
  reload,
  { deep: true },
);

function view(id: number) {
  router.push({ name: 'tasks.details', params: { id } });
}

function edit(id: number) {
  router.push({ name: 'tasks.edit', params: { id } });
}

async function remove(id: number) {
  const res = await Swal.fire({
    title: 'Delete task?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    await api.delete(`/tasks/${id}`);
    all.value = all.value.filter((r) => r.id !== id);
    reload();
  }
}

async function updateStatus(row: any, status: string) {
  try {
    await api.post(`/tasks/${row.id}/status`, { status });
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

