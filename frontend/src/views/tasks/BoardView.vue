<template>
  <div class="p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">{{ t('routes.taskBoard') }}</h1>
    </div>
    <Card
      class="mb-6"
      :bodyClass="cardBodyClass"
    >
      <template #header>
        <div class="flex flex-wrap items-center justify-end gap-2">
          <Badge
            v-if="activeFilterCount"
            :label="t('board.activeFilters', { count: activeFilterCount })"
            badgeClass="pill bg-primary-500 text-white"
            icon="heroicons-outline:sparkles"
          />
          <span
            v-else
            class="text-xs font-medium text-slate-500 dark:text-slate-400"
          >
            {{ t('board.noActiveFilters') }}
          </span>
          <Button
            v-if="activeFilterCount"
            btnClass="btn-outline btn-sm"
            type="button"
            :aria-label="t('board.resetFilters')"
            @click="clearFilters"
            @keyup.enter="clearFilters"
            @keyup.space.prevent="clearFilters"
          >
            {{ t('board.resetFilters') }}
          </Button>
          <Button
            btnClass="btn-outline btn-sm"
            type="button"
            :aria-expanded="showFilters"
            aria-controls="board-filters-panel"
            @click="toggleFilters"
            @keyup.enter.prevent="toggleFilters"
            @keyup.space.prevent="toggleFilters"
          >
            <span class="flex items-center gap-1">
              <Icon
                :icon="showFilters ? 'heroicons-outline:chevron-up' : 'heroicons-outline:chevron-down'"
                class="h-4 w-4"
              />
              {{ showFilters ? t('board.hideFilters') : t('board.showFilters') }}
            </span>
          </Button>
        </div>
      </template>
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-if="showFilters" id="board-filters-panel" class="space-y-6">
          <BoardFilters v-model="prefs.filters" />
        </div>
      </Transition>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <QuickFilterChips
          v-model="prefs.filters"
          class="flex-1 flex-wrap gap-2"
        />
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
          {{ t('board.quickFiltersHint') }}
        </span>
      </div>
    </Card>
    <div class="flex items-center justify-between mt-6 mb-4">
      <Select
        v-model="sortSelection"
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
    <div v-if="columns.length" class="flex space-x-6 overflow-x-auto pb-4">
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
            :disabled="!canMoveTasks"
            class="px-2 pt-4 flex flex-col gap-2 min-h-[100px]"
            :data-status="col.status.slug"
            :move="onDragMove"
            @end="onDrop"
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
          <EmptyState
            v-if="!col.tasks.length"
            :illustration="columnEmpty"
            :message="t('board.emptyColumn')"
            wrapperClass="m-2"
          >
            <template #action>
              <Button
                btnClass="btn-link text-sm"
                :aria-label="t('board.clearFilters')"
                @click="clearFilters"
              >
                {{ t('board.clearFilters') }}
              </Button>
            </template>
          </EmptyState>
          <div v-if="col.meta?.has_more" class="p-2">
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
    <EmptyState
      v-else
      :illustration="boardEmpty"
      :message="t('board.noColumns')"
      wrapperClass="max-w-md mx-auto"
    >
      <template v-if="canTaskTypes" #action>
        <Button
          btnClass="btn-primary"
          :aria-label="t('routes.taskTypes')"
          @click="router.push({ name: 'taskTypes.list' })"
        >
          {{ t('routes.taskTypes') }}
        </Button>
      </template>
    </EmptyState>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import draggable from 'vuedraggable';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';
import TaskCard from './TaskCard.vue';
import Card from '@dc/components/Card';
import Select from '@dc/components/Select';
import Dropdown from '@dc/components/Dropdown';
import Button from '@dc/components/Button';
import Badge from '@dc/components/Badge';
import Icon from '@dc/components/Icon';
import { MenuItem } from '@headlessui/vue';
import { loadBoardPrefs, saveBoardPrefs, BoardPrefs } from '@/services/boardPrefs';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import BoardFilters from '@/components/Board/BoardFilters.vue';
import QuickFilterChips from '@/components/Board/QuickFilterChips.vue';
import EmptyState from '@/components/Board/EmptyState.vue';
import boardEmpty from '@/assets/illustrations/board-empty.svg';
import columnEmpty from '@/assets/illustrations/column-empty.svg';
import { computeAllowedTransitions } from './allowedTransitions';

const { t } = useI18n();
const notify = useNotify();
const auth = useAuthStore();
const router = useRouter();
const tenantStore = useTenantStore();

const canViewBoard = computed(() => auth.can('tasks.view'));
const canMoveTasks = computed(() => auth.hasAny(['tasks.update', 'tasks.manage']));
const canTaskTypes = computed(() => can('task_types.view'));

interface Task {
  id: number;
  title: string;
  status_slug: string;
  previous_status_slug?: string | null;
  priority?: number | null;
  due_at?: string | null;
  created_at?: string | null;
  board_position?: number | null;
  sla_chip?: string | null;
  assignee?: { id: number; name: string };
  counts?: {
    comments?: number;
    attachments?: number;
    watchers?: number;
    subtasks?: number;
  };
  type?: {
    statuses?: Record<string, string[]>;
    status_flow_json?: [string, string][] | Record<string, string[]>;
  };
}

interface Column {
  status: { slug: string; name: string };
  tasks: Task[];
  meta?: { total: number; has_more?: boolean; page?: number };
}

const columns = ref<Column[]>([]);
const dragSnapshots = new Map<number, Column[][]>();
const invalidMoveTasks = new Set<number>();

function makeDefaultFilters(): BoardPrefs['filters'] {
  return {
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
  };
}

const prefs = reactive<BoardPrefs>({
  filters: makeDefaultFilters(),
  sorting: { key: 'created_at', dir: 'asc' },
  cardDensity: 'comfortable',
  showFilters: true,
});

const showFilters = computed({
  get: () => (typeof prefs.showFilters === 'boolean' ? prefs.showFilters : true),
  set: (value: boolean) => {
    prefs.showFilters = value;
  },
});

const cardBodyClass = computed(() =>
  showFilters.value ? 'p-6 space-y-6' : 'p-4 space-y-4',
);

const activeFilterCount = computed(() => {
  const filters = prefs.filters;
  let count = 0;
  if (filters.statusIds?.length) count++;
  if (filters.q) count++;
  if (filters.assigneeId) count++;
  if (filters.priority) count++;
  if (filters.sla) count++;
  if (filters.typeIds?.length) count++;
  if (filters.hasPhotos) count++;
  if (filters.mine) count++;
  if (filters.dueToday) count++;
  if (filters.breachedOnly) count++;
  if (filters.dates && Object.values(filters.dates).some(Boolean)) count++;
  return count;
});

const sortOptions = computed(() => [
  { value: 'created_at:asc', label: t('board.sortCreatedFirst') },
  { value: 'created_at:desc', label: t('board.sortCreatedLast') },
  { value: 'due_at:asc', label: t('board.sortDueSoonest') },
  { value: 'due_at:desc', label: t('board.sortDueLatest') },
  { value: 'priority:desc', label: t('board.sortPriorityHigh') },
  { value: 'priority:asc', label: t('board.sortPriorityLow') },
  { value: 'board_position:asc', label: t('board.sortBoardStart') },
  { value: 'board_position:desc', label: t('board.sortBoardEnd') },
]);

const sortSelection = computed({
  get: () => `${prefs.sorting.key}:${prefs.sorting.dir}`,
  set: (value: string) => {
    const [key, dir] = value.split(':');
    if (key === 'created_at' || key === 'due_at' || key === 'priority' || key === 'board_position') {
      prefs.sorting.key = key;
    }
    prefs.sorting.dir = dir === 'desc' ? 'desc' : 'asc';
    sortColumns();
  },
});

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
  canViewBoard,
  (val) => {
    if (val) {
      load();
    } else {
      columns.value = [];
    }
  },
);

watch(
  [() => prefs.filters, () => prefs.sorting],
  load,
  { deep: true }
);

watch(
  () => tenantStore.currentTenantId,
  () => {
    columns.value = [];
    Object.assign(prefs.filters, makeDefaultFilters());
    load();
  },
);

function clearFilters() {
  Object.assign(prefs.filters, makeDefaultFilters());
}

function toggleFilters() {
  showFilters.value = !showFilters.value;
}

function updateTask(updated: Task) {
  const col = columns.value.find((c) => c.tasks.some((t) => t.id === updated.id));
  if (!col) return;
  const idx = col.tasks.findIndex((t) => t.id === updated.id);
  col.tasks[idx] = updated;
  sortColumn(col);
}

function buildQuery() {
  const f = prefs.filters;
  const params: any = {};
  if (f.assigneeId) params.assignee_id = f.assigneeId;
  if (f.priority) params.priority = f.priority;
  if (f.sla) params.sla = f.sla;
  if (f.q) params.q = f.q;
  if (f.typeIds?.length) params.type_ids = f.typeIds;
  if (f.statusIds?.length) params.status_ids = f.statusIds;
  if (f.hasPhotos) params.has_photos = 1;
  if (f.mine) params.mine = 1;
  if (f.dueToday) params.due_today = 1;
  if (f.breachedOnly) params.breached_only = 1;
  if (f.dates?.from) params.created_from = f.dates.from;
  if (f.dates?.to) params.created_to = f.dates.to;
  params.sort = prefs.sorting.key;
  params.sort_dir = prefs.sorting.dir;
  return params;
}

async function load() {
  if (!canViewBoard.value) {
    columns.value = [];
    return;
  }
  const { data } = await api.get('/task-board', { params: buildQuery() });
  const cols = (data.data ?? data).map((col: any) => ({
    ...col,
    tasks: col.tasks?.data ?? col.tasks ?? [],
  }));
  columns.value = cols;
  sortColumns();
}

async function loadMore(col: Column) {
  if (!canViewBoard.value) return;
  const page = (col.meta?.page || 1) + 1;
  const { data } = await api.get('/task-board/column', {
    params: { status: col.status.slug, page, ...buildQuery() },
  });
  col.tasks.push(...data.data);
  if (!col.meta) col.meta = { total: 0 };
  col.meta.page = page;
  col.meta.total = data.meta.total;
  col.meta.has_more = data.meta.has_more;
  sortColumn(col);
}

onMounted(() => {
  Object.assign(prefs, loadBoardPrefs(auth.userId || auth.user?.id || 0));
  if (typeof prefs.showFilters !== 'boolean') {
    prefs.showFilters = true;
  }
  if (
    !prefs.sorting ||
    !['created_at', 'due_at', 'priority', 'board_position'].includes(
      prefs.sorting.key,
    )
  ) {
    prefs.sorting = { key: 'created_at', dir: 'asc' };
  }
  if (!['asc', 'desc'].includes(prefs.sorting.dir)) {
    prefs.sorting.dir = 'asc';
  }
  if (canViewBoard.value) {
    load();
  }
});

async function performMove(task: Task, statusSlug: string, index: number) {
  if (!canMoveTasks.value) return;
  const snapshot = columns.value.map((c) => ({ ...c, tasks: [...c.tasks] }));
  const fromCol = columns.value.find((c) => c.tasks.some((t) => t.id === task.id));
  if (fromCol) {
    const taskIndex = fromCol.tasks.findIndex((t) => t.id === task.id);
    fromCol.tasks.splice(taskIndex, 1);
  }
  const toCol = columns.value.find((c) => c.status.slug === statusSlug);
  toCol?.tasks.splice(index, 0, task);
  try {
    const { data } = await api.patch('/task-board/move', {
      task_id: task.id,
      status_slug: statusSlug,
      index,
    });
    Object.assign(task, data.data);
    sortColumns();
  } catch {
    columns.value = snapshot;
    notify.error(t('board.errorMove'));
  }
}

async function onDrop(evt: any) {
  const task: Task = evt.item.__draggable_context.element;
  const snapshots = dragSnapshots.get(task.id);
  const snapshot = snapshots?.pop();
  if (!snapshots?.length) dragSnapshots.delete(task.id);
  if (!canMoveTasks.value) {
    if (snapshot) {
      columns.value = snapshot;
    }
    return;
  }
  try {
    const statusSlug = evt.to?.dataset.status;
    const { data } = await api.patch('/task-board/move', {
      task_id: task.id,
      status_slug: statusSlug,
      index: evt.newIndex,
    });
    Object.assign(task, data.data);
    sortColumns();
  } catch {
    if (snapshot) {
      columns.value = snapshot;
    }
    notify.error(t('board.errorMove'));
  }
}

function onDragStart(evt: any) {
  if (!canMoveTasks.value) return;
  const task: Task = evt.item.__draggable_context.element;
  invalidMoveTasks.delete(task.id);
  const snapshots = dragSnapshots.get(task.id) ?? [];
  snapshots.push(columns.value.map((c) => ({ ...c, tasks: [...c.tasks] })));
  dragSnapshots.set(task.id, snapshots);
}

function allowedTransitions(task: Task, from: string): string[] {
  return computeAllowedTransitions(
    task,
    from,
    auth.can('tasks.manage'),
    columns.value,
  );
}

function onDragMove(evt: any) {
  if (!canMoveTasks.value) return false;
  const task: Task = evt.draggedContext.element;
  const toStatus = evt.to?.dataset.status;
  if (!toStatus || toStatus === task.status_slug) return true;
  const allowed = allowedTransitions(task, task.status_slug);
  if (!allowed.includes(toStatus)) {
    if (!invalidMoveTasks.has(task.id)) {
      notify.error(t('board.errorMove'));
      invalidMoveTasks.add(task.id);
    }
    return false;
  }
  return true;
}

function sortColumn(col: Column) {
  const key = prefs.sorting.key;
  const dir = prefs.sorting.dir === 'desc' ? -1 : 1;
  col.tasks.sort((a, b) => {
    const dateKeys: Array<'created_at' | 'due_at'> = ['created_at', 'due_at'];
    if (dateKeys.includes(key as any)) {
      const aDate = a[key as 'created_at' | 'due_at']
        ? new Date(a[key as 'created_at' | 'due_at'] as string).getTime()
        : null;
      const bDate = b[key as 'created_at' | 'due_at']
        ? new Date(b[key as 'created_at' | 'due_at'] as string).getTime()
        : null;
      if (aDate === bDate) {
        return (a.board_position ?? 0) - (b.board_position ?? 0);
      }
      if (aDate === null) return 1;
      if (bDate === null) return -1;
      return (aDate - bDate) * dir;
    }

    if (key === 'priority') {
      const fallback = dir === -1 ? Number.NEGATIVE_INFINITY : Number.POSITIVE_INFINITY;
      const aPriority = a.priority ?? fallback;
      const bPriority = b.priority ?? fallback;
      if (aPriority === bPriority) {
        return (a.board_position ?? 0) - (b.board_position ?? 0);
      }
      return (aPriority - bPriority) * dir;
    }

    // board_position or fallback
    const aPos = a.board_position ?? 0;
    const bPos = b.board_position ?? 0;
    return (aPos - bPos) * dir;
  });
}

function sortColumns() {
  columns.value.forEach((col) => sortColumn(col));
}
</script>
