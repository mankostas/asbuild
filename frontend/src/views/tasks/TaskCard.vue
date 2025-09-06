<template>
  <div
    :class="[
      'bg-white rounded shadow flex flex-col gap-1',
      density === 'compact' ? 'p-1 text-sm' : 'p-2',
    ]"
  >
    <div class="font-medium">{{ task.title }}</div>
    <div class="text-xs flex flex-wrap gap-1 items-center">
      <span v-if="task.assignee" class="flex items-center gap-1">
        <span
          class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center text-[10px]"
          >{{ initials }}</span
        >
        <span>{{ task.assignee.name }}</span>
      </span>
      <Badge
        v-if="task.priority"
        :label="t(`tasks.priority.${priorityLabel}`)"
        :badgeClass="priorityBadgeClass"
      />
      <Badge v-if="slaText" :label="slaText" :badgeClass="slaBadgeClass" />
      <span v-if="task.due_at" class="px-1">{{ formatDate(task.due_at) }}</span>
    </div>
    <div class="flex flex-wrap gap-1 mt-1">
      <Button
        v-if="auth.can('tasks.assign')"
        btnClass="btn-xs btn-light"
        :aria-label="t('board.assignMe')"
        @click="assignMe"
        @keyup.enter="assignMe"
        @keyup.space.prevent="assignMe"
        >{{ t('board.assignMe') }}</Button
      >
      <Dropdown
        v-if="auth.can('tasks.status.update')"
        :label="t('board.changeStatus')"
        labelClass="btn btn-light btn-xs"
      >
        <template #menus>
          <MenuItem v-for="s in statusOptions" :key="s.slug" v-slot="{ active }">
            <button
              class="block w-full text-left px-4 py-2 text-sm"
              :class="active ? 'bg-slate-100' : ''"
              @click="changeStatus(s.slug)"
            >
              {{ s.name }}
            </button>
          </MenuItem>
        </template>
      </Dropdown>
      <Button
        v-if="auth.can('tasks.update')"
        btnClass="btn-xs btn-light"
        icon="heroicons-outline:chevron-left"
        iconClass="text-sm"
        :aria-label="t('board.moveLeft')"
        @click="() => move(-1)"
        @keyup.enter="() => move(-1)"
        @keyup.space.prevent="() => move(-1)"
      />
      <Button
        v-if="auth.can('tasks.update')"
        btnClass="btn-xs btn-light"
        icon="heroicons-outline:chevron-right"
        iconClass="text-sm"
        :aria-label="t('board.moveRight')"
        @click="() => move(1)"
        @keyup.enter="() => move(1)"
        @keyup.space.prevent="() => move(1)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';
import { useAuthStore } from '@/stores/auth';
import Button from '@/components/ui/Button/index.vue';
import Dropdown from '@/components/ui/Dropdown/index.vue';
import Badge from '@/components/ui/Badge/index.vue';
import { MenuItem } from '@headlessui/vue';

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

const priorityBadgeClass = computed(() => {
  switch (props.task.priority) {
    case 3:
      return 'bg-orange-100 text-orange-800';
    case 4:
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-slate-100 text-slate-800';
  }
});

const initials = computed(() =>
  props.task.assignee?.name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .slice(0, 2) || ''
);

const slaText = computed(() => {
  if (!props.task.due_at) return '';
  const due = new Date(props.task.due_at).getTime();
  const now = Date.now();
  if (due < now) return 'Breached';
  if (due - now < 48 * 3600 * 1000) return 'Due soon';
  return 'SLA OK';
});

const slaBadgeClass = computed(() => {
  switch (slaText.value) {
    case 'Breached':
      return 'bg-red-100 text-red-800';
    case 'Due soon':
      return 'bg-yellow-100 text-yellow-800';
    default:
      return 'bg-green-100 text-green-800';
  }
});

function formatDate(d: string) {
  return new Date(d).toLocaleDateString();
}

async function assignMe() {
  try {
    await api.patch(`/tasks/${props.task.id}`, { assigned_user_id: auth.user.id });
    emit('assigned', { ...props.task, assignee: { id: auth.user.id, name: auth.user.name } });
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
