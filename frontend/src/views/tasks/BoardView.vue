<template>
  <div class="p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">{{ t('routes.taskBoard') }}</h1>
      <TenantSwitcher v-if="auth.isSuperAdmin" />
    </div>
    <BoardFilters v-model="prefs.filters" />
    <QuickFilterChips v-model="prefs.filters" class="mt-4" />
    <div class="flex items-center justify-between mt-4 mb-4">
      <Select
        v-model="prefs.sorting.key"
        :options="sortOptions"
        classInput="h-8"
        :aria-label="t('board.sort')"
      />
      <Dropdown :label="densityLabel" labelClass="btn btn-light btn-sm">
        <template #menus>
          <MenuItem
            v-for="d in densityOptions"
            :key="d"
            #default="{ active }"
          >
            <button
              class="block w-full text-left px-4 py-2 text-sm"
              :class="active ? 'bg-slate-100' : ''"
              @click="setDensity(d)"
            >
              {{ d }}
            </button>
          </MenuItem>
        </template>
      </Dropdown>
    </div>
    <div class="flex space-x-6 overflow-x-auto pb-4">
      <div
        v-for="col in columns"
        :key="col.status.slug"
        class="w-[320px] flex-none bg-slate-200 dark:bg-slate-700 rounded-2xl"
      >
        <Card class="rounded-2xl shadow-base" bodyClass="p-0">
          <div
            class="relative flex justify-between items-center bg-white dark:bg-slate-800 rounded-2xl px-6 py-5"
          >
            <div class="text-lg text-slate-900 dark:text-white font-medium capitalize">
              {{ col.status.name }}
            </div>
          </div>
          <draggable
            v-model="col.tasks"
            group="tasks"
            item-key="id"
            class="px-2 pt-4 flex flex-col gap-2"
            :data-status="col.status.slug"
            @end="(e) => onDrop(e, col)"
            @start="onDragStart"
          >
            <template #item="{ element }">
              <TaskCard
                :task="element"
                :columns="columns"
                :onMove="performMove"
                :density="prefs.cardDensity"
                @assigned="updateTask"
              />
            </template>
          </draggable>
          <div class="p-2" v-if="col.meta?.has_more">
            <Button
              btnClass="btn-outline btn-sm w-full"
              :aria-label="t('board.loadMore')"
              @click="() => loadMore(col)"
              @keyup.enter="() => loadMore(col)"
              @keyup.space.prevent="() => loadMore(col)"
            >
              {{ t('board.loadMore') }}
            </Button>
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';
import TaskCard from './TaskCard.vue';
import Card from '@dc/components/Card';
import Select from '@dc/components/Select';
import Dropdown from '@dc/components/Dropdown';
import Button from '@dc/components/Button';
import { MenuItem } from '@headlessui/vue';
import { loadBoardPrefs, saveBoardPrefs, BoardPrefs } from '@/services/boardPrefs';
import { useAuthStore } from '@/stores/auth';
import BoardFilters from '@/components/Board/BoardFilters.vue';
import QuickFilterChips from '@/components/Board/QuickFilterChips.vue';
import TenantSwitcher from '@/components/admin/TenantSwitcher.vue';

const { t } = useI18n();
const notify = useNotify();
const auth = useAuthStore();

interface Task {
  id: number;
  title: string;
  priority?: number | null;
  due_at?: string | null;
  sla_chip?: string | null;
  assignee?: { id: number; name: string };
}

interface Column {
  status: { slug: string; name: string };
  tasks: Task[];
  meta?: { total: number; has_more?: boolean; page?: number };
}

const columns = ref<Column[]>([]);
const dragSnapshots = new Map<number, Column[][]>();

const prefs = reactive<BoardPrefs>({
  filters: {
    statusIds: [],
    typeIds: [],
    assigneeId: null,
    priority: null,
    sla: null,
    q: null,
    hasPhotos: null,
    mine: false,
    dueToday: false,
    breachedOnly: false,
    dates: {},
  },
  sorting: { key: 'created_at', dir: 'asc' },
  cardDensity: 'comfortable',
});

const sortOptions = [
  { value: 'created_at', label: 'Created' },
  { value: 'due_at', label: 'Due' },
  { value: 'priority', label: 'Priority' },
  { value: 'board_position', label: 'Board Position' },
];

const densityOptions: BoardPrefs['cardDensity'][] = ['comfortable', 'compact'];
const densityLabel = computed(() => prefs.cardDensity);
function setDensity(d: BoardPrefs['cardDensity']) {
  prefs.cardDensity = d;
}

watch(
  () => prefs,
  (val) => saveBoardPrefs(auth.userId || auth.user?.id || 0, val),
  { deep: true }
);

watch(
  [() => prefs.filters, () => prefs.sorting],
  load,
  { deep: true }
);

function updateTask(updated: Task) {
  const col = columns.value.find((c) => c.tasks.some((t) => t.id === updated.id));
  if (!col) return;
  const idx = col.tasks.findIndex((t) => t.id === updated.id);
  col.tasks[idx] = updated;
}

function buildQuery() {
  const f = prefs.filters;
  const params: any = {};
  if (f.assigneeId) params.assignee_id = f.assigneeId;
  if (f.priority) params.priority = f.priority;
  if (f.sla) params.sla = f.sla;
  if (f.q) params.q = f.q;
  if (f.typeIds?.length) params.type_ids = f.typeIds;
  if (f.hasPhotos) params.has_photos = 1;
  if (f.mine) params.mine = 1;
  if (f.dueToday) params.due_today = 1;
  if (f.breachedOnly) params.breached_only = 1;
  return params;
}

async function load() {
  const { data } = await api.get('/task-board', { params: buildQuery() });
  const cols = (data.data ?? data).map((col: any) => ({
    ...col,
    tasks: col.tasks?.data ?? col.tasks ?? [],
  }));
  columns.value = cols;
}

async function loadMore(col: Column) {
  const page = (col.meta?.page || 1) + 1;
  const { data } = await api.get('/task-board/column', {
    params: { status: col.status.slug, page, ...buildQuery() },
  });
  col.tasks.push(...data.data);
  if (!col.meta) col.meta = { total: 0 };
  col.meta.page = page;
  col.meta.total = data.meta.total;
  col.meta.has_more = data.meta.has_more;
}

onMounted(() => {
  Object.assign(prefs, loadBoardPrefs(auth.userId || auth.user?.id || 0));
  load();
});

async function performMove(task: Task, statusSlug: string, index: number) {
  const snapshot = columns.value.map((c) => ({ ...c, tasks: [...c.tasks] }));
  const fromCol = columns.value.find((c) => c.tasks.some((t) => t.id === task.id));
  if (fromCol) {
    const taskIndex = fromCol.tasks.findIndex((t) => t.id === task.id);
    fromCol.tasks.splice(taskIndex, 1);
  }
  const toCol = columns.value.find((c) => c.status.slug === statusSlug);
  toCol?.tasks.splice(index, 0, task);
  try {
    await api.patch('/task-board/move', {
      task_id: task.id,
      status_slug: statusSlug,
      index,
    });
  } catch {
    columns.value = snapshot;
    notify.error(t('board.errorMove'));
  }
}

async function onDrop(evt: any, column: Column) {
  const task: Task = evt.item.__draggable_context.element;
  const snapshots = dragSnapshots.get(task.id);
  const snapshot = snapshots?.pop();
  if (!snapshots?.length) dragSnapshots.delete(task.id);
  try {
    await api.patch('/task-board/move', {
      task_id: task.id,
      status_slug: column.status.slug,
      index: evt.newIndex,
    });
  } catch {
    if (snapshot) {
      columns.value = snapshot;
    }
    notify.error(t('board.errorMove'));
  }
}

function onDragStart(evt: any) {
  const task: Task = evt.item.__draggable_context.element;
  const snapshots = dragSnapshots.get(task.id) ?? [];
  snapshots.push(columns.value.map((c) => ({ ...c, tasks: [...c.tasks] })));
  dragSnapshots.set(task.id, snapshots);
}
</script>
