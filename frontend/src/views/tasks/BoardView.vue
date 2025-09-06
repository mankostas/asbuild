<template>
  <div class="p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">{{ t('routes.taskBoard') }}</h1>
      <div class="flex items-center gap-2">
        <InputGroup
          v-model="prefs.filters.assigneeId"
          :placeholder="t('board.assignee')"
          classInput="h-8"
          :aria-label="t('board.assignee')"
        />
        <Select
          v-model="prefs.filters.priority"
          :options="priorityOptions"
          classInput="h-8"
          :aria-label="t('board.priority')"
        />
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
        <Button
          btnClass="btn-sm btn-light"
          :aria-label="t('board.clearFilters')"
          @click="clearFilters"
          @keyup.enter="clearFilters"
          @keyup.space.prevent="clearFilters"
          >{{ t('board.clearFilters') }}</Button
        >
      </div>
    </div>
    <div class="flex gap-4 overflow-x-auto">
      <Card
        v-for="col in columns"
        :key="col.status.slug"
        class="w-72 flex-shrink-0"
        :title="col.status.name"
        bodyClass="p-2"
      >
        <draggable
          v-model="col.tasks"
          group="tasks"
          item-key="id"
          class="flex flex-col gap-2"
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
      </Card>
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
import Card from '@/components/ui/Card/index.vue';
import Select from '@/components/ui/Select/index.vue';
import InputGroup from '@/components/ui/InputGroup/index.vue';
import Dropdown from '@/components/ui/Dropdown/index.vue';
import Button from '@/components/ui/Button/index.vue';
import { MenuItem } from '@headlessui/vue';
import { loadBoardPrefs, saveBoardPrefs, BoardPrefs } from '@/services/boardPrefs';
import { useAuthStore } from '@/stores/auth';

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
  meta?: { total: number };
}

const columns = ref<Column[]>([]);
const dragSnapshots = new Map<number, Column[][]>();

const prefs = reactive<BoardPrefs>({
  filters: {
    statusIds: [],
    typeId: null,
    assigneeId: null,
    priority: null,
    hasPhotos: null,
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

const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
];

const densityOptions: BoardPrefs['cardDensity'][] = ['comfortable', 'compact'];
const densityLabel = computed(() => prefs.cardDensity);
function setDensity(d: BoardPrefs['cardDensity']) {
  prefs.cardDensity = d;
}
function clearFilters() {
  prefs.filters = {
    statusIds: [],
    typeId: null,
    assigneeId: null,
    priority: null,
    hasPhotos: null,
    dates: {},
  };
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

async function load() {
  const { data } = await api.get('/task-board');
  const cols = (data.data ?? data).map((col: any) => ({
    ...col,
    tasks: col.tasks?.data ?? col.tasks ?? [],
  }));
  columns.value = cols;
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
