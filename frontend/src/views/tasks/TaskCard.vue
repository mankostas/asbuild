<template>
  <Card class="cursor-move">
    <header class="flex justify-between items-center">
      <div class="flex space-x-3 items-center rtl:space-x-reverse">
        <div class="flex-none">
          <div
            class="h-10 w-10 rounded-md text-lg bg-slate-100 text-slate-900 dark:bg-slate-600 dark:text-slate-200 flex flex-col items-center justify-center font-normal capitalize"
          >
            {{ titleInitials }}
          </div>
        </div>
        <div class="flex-1 font-medium text-base leading-6">
          <div class="dark:text-slate-200 text-slate-900 max-w-[160px] truncate">
            {{ task.title }}
          </div>
        </div>
      </div>
      <div>
        <Dropdown classMenuItems="w-[180px]">
          <span
            class="text-lg inline-flex flex-col items-center justify-center h-8 w-8 rounded-full bg-gray-500/10 dark:bg-slate-900 dark:text-slate-400"
            ><Icon icon="heroicons-outline:dots-vertical" /></span
          >
          <template #menus>
            <MenuItem v-if="auth.can('tasks.assign')" #default="{ active }">
              <button :class="menuClass(active)" @click="assignMe" @keyup.enter="assignMe" @keyup.space.prevent="assignMe">
                <Icon icon="heroicons-outline:user-add" />
                <span>{{ t('board.assignMe') }}</span>
              </button>
            </MenuItem>
            <MenuItem v-if="auth.hasAny(['tasks.update', 'tasks.manage'])" #default="{ active }">
              <button :class="menuClass(active)" @click="editTask" @keyup.enter="editTask" @keyup.space.prevent="editTask">
                <Icon icon="heroicons-outline:pencil-square" />
                <span>{{ t('actions.edit') }}</span>
              </button>
            </MenuItem>
            <template v-if="auth.can('tasks.status.update')">
              <MenuItem
                v-for="s in statusOptions"
                :key="s.slug"
                #default="{ active }"
              >
                <button
                  :class="menuClass(active)"
                  @click="changeStatus(s.slug)"
                  @keyup.enter="changeStatus(s.slug)"
                  @keyup.space.prevent="changeStatus(s.slug)"
                >
                  <Icon icon="heroicons-outline:arrow-right" />
                  <span>{{ s.name }}</span>
                </button>
              </MenuItem>
            </template>
            <MenuItem v-if="canMoveTasks && canMoveLeft" #default="{ active }">
              <button
                :class="menuClass(active)"
                @click="move(-1)"
                @keyup.enter="move(-1)"
                @keyup.space.prevent="move(-1)"
              >
                <Icon icon="heroicons-outline:chevron-left" />
                <span>{{ t('board.moveLeft') }}</span>
              </button>
            </MenuItem>
            <MenuItem v-if="canMoveTasks && canMoveRight" #default="{ active }">
              <button
                :class="menuClass(active)"
                @click="move(1)"
                @keyup.enter="move(1)"
                @keyup.space.prevent="move(1)"
              >
                <Icon icon="heroicons-outline:chevron-right" />
                <span>{{ t('board.moveRight') }}</span>
              </button>
            </MenuItem>
          </template>
        </Dropdown>
      </div>
    </header>
    <div
      v-if="task.description"
      class="text-slate-600 dark:text-slate-400 text-sm pt-4 pb-4"
    >
      {{ task.description }}
    </div>
    <div v-if="task.priority || task.sla_chip" class="flex flex-wrap gap-2 mt-4">
      <span
        v-if="task.priority"
        class="px-2 py-1 rounded-full text-xs font-medium"
        :class="priorityClasses[task.priority]"
      >
        {{ t(`tasks.priority.${task.priority}`) }}
      </span>
      <span
        v-if="task.sla_chip"
        class="px-2 py-1 rounded-full text-xs font-medium"
        :class="slaClasses[task.sla_chip]"
      >
        {{ t(`tasks.chips.sla.${task.sla_chip}`) }}
      </span>
    </div>
    <div v-if="task.due_at" class="flex space-x-4 rtl:space-x-reverse">
      <div>
        <span class="block date-label">{{ t('tasks.due') }}</span>
        <span class="block date-text">{{ formatDate(task.due_at) }}</span>
      </div>
    </div>
    <div v-if="task.assignee || task.due_at" class="grid grid-cols-2 gap-4 mt-6">
      <div v-if="task.assignee">
        <div class="text-slate-400 dark:text-slate-400 text-sm font-normal mb-3">
          {{ t('board.assignedTo') }}
        </div>
        <div class="flex justify-start -space-x-1.5">
          <div class="h-6 w-6 rounded-full ring-1 ring-slate-100">
            <span
              class="w-full h-full rounded-full bg-slate-200 flex flex-col items-center justify-center text-xs"
              >{{ assigneeInitials }}</span
            >
          </div>
        </div>
      </div>
      <div v-if="task.due_at" class="ltr:text-right rtl:text-left">
        <span
          class="inline-flex items-center space-x-1 bg-danger-500 bg-opacity-[0.16] text-danger-500 text-xs font-normal px-2 py-1 rounded-full rtl:space-x-reverse"
        >
          <span><Icon icon="heroicons-outline:clock" /></span>
          <span>{{ daysLeft }}</span>
          <span>{{ t('board.daysLeft') }}</span>
        </span>
      </div>
    </div>
    <div
      v-if="hasCounts"
      class="flex flex-wrap gap-4 mt-6 text-slate-500 dark:text-slate-400 text-xs"
    >
      <div
        v-if="task.counts?.comments"
        class="flex items-center gap-1 rtl:space-x-reverse"
      >
        <Icon icon="heroicons-outline:chat-alt-2" class="w-4 h-4" />
        <span>{{ task.counts.comments }}</span>
      </div>
      <div
        v-if="task.counts?.attachments"
        class="flex items-center gap-1 rtl:space-x-reverse"
      >
        <Icon icon="heroicons-outline:paper-clip" class="w-4 h-4" />
        <span>{{ task.counts.attachments }}</span>
      </div>
      <div
        v-if="task.counts?.watchers"
        class="flex items-center gap-1 rtl:space-x-reverse"
      >
        <Icon icon="heroicons-outline:eye" class="w-4 h-4" />
        <span>{{ task.counts.watchers }}</span>
      </div>
      <div
        v-if="task.counts?.subtasks"
        class="flex items-center gap-1 rtl:space-x-reverse"
      >
        <Icon icon="heroicons-outline:collection" class="w-4 h-4" />
        <span>{{ task.counts.subtasks }}</span>
      </div>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';
import { useAuthStore } from '@/stores/auth';
import Card from '@/components/ui/Card/index.vue';
import Dropdown from '@/components/ui/Dropdown/index.vue';
import Icon from '@/components/ui/Icon';
import { MenuItem } from '@headlessui/vue';
import { computeAllowedTransitions } from './allowedTransitions';

interface Task {
  id: number;
  title: string;
  description?: string | null;
  due_at?: string | null;
  assignee?: { id: number; name: string } | null;
  priority?: string | null;
  sla_chip?: 'ok' | 'dueSoon' | 'breached' | null;
  status_slug: string;
  previous_status_slug?: string | null;
  counts?: {
    comments?: number;
    attachments?: number;
    watchers?: number;
    subtasks?: number;
  };
  type?: {
    statuses?: Record<string, string[]>;
    status_flow_json?: [string, string][];
  };
}

interface Column {
  status: { slug: string; name: string };
  tasks: Task[];
}

const props = defineProps<{
  task: Task;
  columns: Column[];
  onMove: (task: Task, statusSlug: string, index: number) => Promise<void>;
  density: 'comfortable' | 'compact';
}>();
const emit = defineEmits<{ (e: 'assigned', task: Task): void }>();

const { t } = useI18n();
const notify = useNotify();
const auth = useAuthStore();
const router = useRouter();
const canMoveTasks = computed(() => auth.hasAny(['tasks.update', 'tasks.manage']));

function allowedTransitions(from: string): string[] {
  const canManage = auth.can('tasks.manage');
  const transitions = computeAllowedTransitions(
    props.task,
    from,
    canManage,
    props.columns,
  );
  return canManage ? transitions.filter((s) => s !== from) : transitions;
}

const statusOptions = computed(() => {
  const map: Record<string, string> = Object.fromEntries(
    props.columns.map((c) => [c.status.slug, c.status.name]),
  );
  const from = props.task.status_slug;
  const allowed = allowedTransitions(from);
  return allowed
    .map((slug: string) => ({ slug, name: map[slug] }))
    .filter((s) => !!s.name);
});

const canMoveLeft = computed(() => {
  const from = props.task.status_slug;
  const colIndex = props.columns.findIndex((c) =>
    c.tasks.some((t) => t.id === props.task.id),
  );
  const targetIndex = colIndex - 1;
  if (targetIndex < 0) return false;
  const targetSlug = props.columns[targetIndex].status.slug;
  const allowed = allowedTransitions(from);
  return allowed.includes(targetSlug);
});

const canMoveRight = computed(() => {
  const from = props.task.status_slug;
  const colIndex = props.columns.findIndex((c) =>
    c.tasks.some((t) => t.id === props.task.id),
  );
  const targetIndex = colIndex + 1;
  if (targetIndex >= props.columns.length) return false;
  const targetSlug = props.columns[targetIndex].status.slug;
  const allowed = allowedTransitions(from);
  return allowed.includes(targetSlug);
});

const titleInitials = computed(() => {
  const title = props.task.title;
  return title
    ? title
        .split(' ')
        .map((n) => n[0])
        .join('')
        .slice(0, 2)
    : '';
});

const assigneeInitials = computed(() =>
  props.task.assignee?.name
    ?.split(' ')
    .map((n) => n[0])
    .join('')
    .slice(0, 2) || '',
);

const daysLeft = computed(() => {
  if (!props.task.due_at) return 0;
  const due = new Date(props.task.due_at).getTime();
  const now = Date.now();
  return Math.max(0, Math.ceil((due - now) / (1000 * 60 * 60 * 24)));
});

const hasCounts = computed(() => {
  const c = props.task.counts;
  return !!c && (c.comments || c.attachments || c.watchers || c.subtasks);
});

function formatDate(d: string) {
  return new Date(d).toLocaleDateString();
}

async function assignMe() {
  try {
    await api.patch(`/tasks/${props.task.id}`, {
      assigned_user_id: auth.user.id,
    });
    emit('assigned', {
      ...props.task,
      assignee: { id: auth.user.id, name: auth.user.name },
    });
  } catch {
    notify.error(t('tasks.messages.error'));
  }
}

function changeStatus(slug: string) {
  const column = props.columns.find((c) => c.status.slug === slug);
  const index = column ? column.tasks.length : 0;
  props.onMove(props.task, slug, index);
}

function move(dir: number) {
  if (!canMoveTasks.value) return;
  const colIndex = props.columns.findIndex((c) =>
    c.tasks.some((t) => t.id === props.task.id),
  );
  const targetIndex = colIndex + dir;
  if (targetIndex < 0 || targetIndex >= props.columns.length) return;
  const targetSlug = props.columns[targetIndex].status.slug;
  const allowed = allowedTransitions(props.task.status_slug);
  if (!allowed.includes(targetSlug)) {
    notify.error(t('board.errorMove'));
    return;
  }
  const index = props.columns[targetIndex].tasks.length;
  props.onMove(props.task, targetSlug, index);
}

function editTask() {
  router.push({ name: 'tasks.edit', params: { id: props.task.id } });
}

function menuClass(active: boolean) {
  return (
    (active
      ? 'bg-slate-900 dark:bg-slate-600 dark:bg-opacity-70 text-white'
      : '') +
    ' w-full border-b border-b-gray-500 dark:border-b-slate-700 dark:text-slate-200 border-opacity-10 px-4 py-2 text-sm cursor-pointer flex space-x-2 items-center rtl:space-x-reverse'
  );
}

const priorityClasses: Record<string, string> = {
  low: 'bg-slate-100 text-slate-800',
  medium: 'bg-slate-100 text-slate-800',
  normal: 'bg-slate-100 text-slate-800',
  high: 'bg-warning-500 bg-opacity-[0.16] text-warning-500',
  urgent: 'bg-danger-500 bg-opacity-[0.16] text-danger-500',
};

const slaClasses: Record<string, string> = {
  ok: 'bg-success-500 bg-opacity-[0.16] text-success-500',
  dueSoon: 'bg-warning-500 bg-opacity-[0.16] text-warning-500',
  breached: 'bg-danger-500 bg-opacity-[0.16] text-danger-500',
};
</script>

<style scoped>
.date-label {
  @apply text-xs text-slate-400 dark:text-slate-400 mb-1;
}
.date-text {
  @apply text-xs text-slate-600 dark:text-slate-300 font-medium;
}
</style>

