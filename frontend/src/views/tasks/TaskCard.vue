<template>
  <div class="bg-white p-2 rounded shadow flex flex-col gap-1">
    <div class="font-medium">{{ task.title }}</div>
    <div class="text-xs flex flex-wrap gap-1 items-center">
      <span v-if="task.assignee" class="px-1 bg-gray-200 rounded">{{ task.assignee.name }}</span>
      <span v-if="task.priority" class="px-1" :class="priorityClass">
        {{ t(`tasks.priority.${priorityLabel}`) }}
      </span>
      <span v-if="task.sla_chip" class="px-1 bg-yellow-100 rounded">{{ task.sla_chip }}</span>
      <span v-if="task.due_at" class="px-1">{{ formatDate(task.due_at) }}</span>
    </div>
    <div class="flex flex-wrap gap-1 mt-1">
      <button
        class="btn btn-xs btn-light"
        :aria-label="t('board.assignMe')"
        @click="assignMe"
        @keyup.enter="assignMe"
      >{{ t('board.assignMe') }}</button>
      <button
        v-if="!showStatus"
        class="btn btn-xs btn-light"
        :aria-label="t('board.changeStatus')"
        @click="showStatus = true"
        @keyup.enter="showStatus = true"
      >{{ t('board.changeStatus') }}</button>
      <select
        v-else
        v-model="statusSlug"
        :aria-label="t('board.changeStatus')"
        class="border rounded text-xs"
        @change="applyStatus"
      >
        <option v-for="s in statusOptions" :key="s.slug" :value="s.slug">
          {{ s.name }}
        </option>
      </select>
      <button
        class="btn btn-xs btn-light"
        :aria-label="t('board.addComment')"
        @click="addComment"
        @keyup.enter="addComment"
      >{{ t('board.addComment') }}</button>
      <button
        class="btn btn-xs btn-light"
        :aria-label="t('board.moveLeft')"
        @click="() => move(-1)"
        @keyup.enter="() => move(-1)"
      >&larr;</button>
      <button
        class="btn btn-xs btn-light"
        :aria-label="t('board.moveRight')"
        @click="() => move(1)"
        @keyup.enter="() => move(1)"
      >&rarr;</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';
import { useAuthStore } from '@/stores/auth';

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

const props = defineProps<{
  task: Task;
  columns: Column[];
  onMove: (task: Task, statusSlug: string, index: number) => Promise<void>;
}>();
const emit = defineEmits<{ (e: 'assigned', task: Task): void }>();

const { t } = useI18n();
const notify = useNotify();
const auth = useAuthStore();

const showStatus = ref(false);
const statusSlug = ref('');

const statusOptions = computed(() => props.columns.map((c) => c.status));

const priorityLabel = computed(() => {
  switch (props.task.priority) {
    case 1:
      return 'low';
    case 2:
      return 'normal';
    case 3:
      return 'high';
    case 4:
      return 'urgent';
    default:
      return 'normal';
  }
});

const priorityClass = computed(() => {
  switch (props.task.priority) {
    case 3:
      return 'text-orange-600';
    case 4:
      return 'text-red-600';
    default:
      return 'text-gray-600';
  }
});

function formatDate(d: string) {
  return new Date(d).toLocaleDateString();
}

async function assignMe() {
  try {
    await api.patch(`/tasks/${props.task.id}`, { assignee_id: auth.user.id });
    emit('assigned', { ...props.task, assignee: { id: auth.user.id, name: auth.user.name } });
  } catch {
    notify.error(t('tasks.messages.error'));
  }
}

async function applyStatus() {
  showStatus.value = false;
  const target = statusSlug.value;
  const column = props.columns.find((c) => c.status.slug === target);
  const index = column ? column.tasks.length : 0;
  await props.onMove(props.task, target, index);
}

async function addComment() {
  const body = window.prompt(t('board.addCommentPrompt'));
  if (!body) return;
  try {
    await api.post(`/tasks/${props.task.id}/comments`, { body });
  } catch {
    notify.error(t('tasks.messages.error'));
  }
}

function move(dir: number) {
  const colIndex = props.columns.findIndex((c) =>
    c.tasks.some((t) => t.id === props.task.id),
  );
  const targetIndex = colIndex + dir;
  if (targetIndex < 0 || targetIndex >= props.columns.length) return;
  const targetSlug = props.columns[targetIndex].status.slug;
  const index = props.columns[targetIndex].tasks.length;
  props.onMove(props.task, targetSlug, index);
}
</script>
