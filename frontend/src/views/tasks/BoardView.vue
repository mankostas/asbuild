<template>
  <div class="p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">{{ t('routes.taskBoard') }}</h1>
      <div class="flex items-center gap-2">
        <span id="swimlane-label">{{ t('board.swimlane') }}</span>
        <select v-model="swimlane" :aria-labelledby="'swimlane-label'" class="border rounded p-1">
          <option value="none">{{ t('board.none') }}</option>
          <option value="assignee">{{ t('board.assignee') }}</option>
          <option value="team">{{ t('board.team') }}</option>
        </select>
      </div>
    </div>
    <div class="flex gap-4 overflow-x-auto">
      <div
        v-for="col in columns"
        :key="col.status.slug"
        class="w-72 flex-shrink-0"
      >
        <h2 class="font-semibold mb-2">{{ col.status.name }}</h2>
        <draggable
          v-if="swimlane === 'none'"
          v-model="col.tasks"
          group="tasks"
          item-key="id"
          class="flex flex-col gap-2"
          :data-status="col.status.slug"
          @end="(e) => onDrop(e, col)"
        >
          <template #item="{ element }">
            <TaskCard :task="element" :columns="columns" :onMove="performMove" @assigned="updateTask" />
          </template>
        </draggable>
        <div v-else class="flex flex-col gap-4">
          <div v-for="(tasks, lane) in grouped(col.tasks)" :key="lane">
            <h3 class="text-sm font-medium mb-1">{{ lane }}</h3>
            <div class="flex flex-col gap-2">
              <TaskCard
                v-for="taskItem in tasks"
                :key="taskItem.id"
                :task="taskItem"
                :columns="columns"
                :onMove="performMove" @assigned="updateTask"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';
import TaskCard from './TaskCard.vue';

const { t } = useI18n();
const notify = useNotify();

interface Task {
  id: number;
  title: string;
  priority?: number | null;
  due_at?: string | null;
  sla_chip?: string | null;
  assignee?: { id: number; name: string; team?: { name: string } };
}

interface Column {
  status: { slug: string; name: string };
  tasks: Task[];
}

const columns = ref<Column[]>([]);
const swimlane = ref<'none' | 'assignee' | 'team'>('none');

function updateTask(updated: Task) {
  const col = columns.value.find((c) => c.tasks.some((t) => t.id === updated.id));
  if (!col) return;
  const idx = col.tasks.findIndex((t) => t.id === updated.id);
  col.tasks[idx] = updated;
}
function grouped(tasks: Task[]) {
  const groups: Record<string, Task[]> = {};
  tasks.forEach((task) => {
    const key =
      swimlane.value === 'assignee'
        ? task.assignee?.name || t('tasks.unassigned')
        : task.assignee?.team?.name || t('tasks.noTeam');
    (groups[key] ||= []).push(task);
  });
  return groups;
}

async function load() {
  const { data } = await api.get('/task-board');
  columns.value = data;
}

onMounted(load);

async function performMove(task: Task, statusSlug: string, index: number) {
  try {
    await api.patch('/task-board/move', {
      task_id: task.id,
      status_slug: statusSlug,
      index,
    });
    await load();
  } catch {
    notify.error(t('board.errorMove'));
    await load();
  }
}

async function onDrop(evt: any, column: Column) {
  const task: Task = evt.item.__draggable_context.element;
  await performMove(task, column.status.slug, evt.newIndex);
}
</script>
