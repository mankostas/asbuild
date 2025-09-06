<template>
  <div v-if="task">
    <Card class="mb-4" :title="`Task ${task.id}`">
      <div class="mb-2 flex items-center gap-2">
        <span class="font-semibold">{{ task.type?.name || '—' }}</span>
        <span
          class="px-2 py-1 rounded-full text-xs font-semibold"
          :class="statusClasses[task.status]"
        >
          {{ task.status.replace(/_/g, ' ') }}
        </span>
        <Button
          class="ml-auto"
          :text="task.is_watching ? t('tasks.unwatch') : t('tasks.watch')"
          btnClass="btn-outline-dark"
          :aria-label="task.is_watching ? t('tasks.unwatch') : t('tasks.watch')"
          @click="toggleWatch"
        />
      </div>
      <ul class="text-sm mb-2">
        <li>Scheduled: {{ format(task.scheduled_at) || '—' }}</li>
        <li>Due: {{ format(task.due_at) || '—' }}</li>
        <li>Started: {{ format(task.started_at) || '—' }}</li>
        <li>Completed: {{ format(task.completed_at) || '—' }}</li>
        <li>Assignee: {{ task.assignee?.name || '—' }}</li>
        <li>Priority: {{ task.priority || '—' }}</li>
        <li>SLA End: {{ format(task.sla_end_at) || '—' }}</li>
        <li class="flex items-center gap-2">
          <span>{{ t('tasks.details.sla') }}:</span>
          <Badge
            :label="t(`tasks.chips.sla.${slaStatusKey}`)"
            :badgeClass="slaBadgeClass"
          />
        </li>
      </ul>
      <div class="mt-2">
        <StatusChanger
          v-if="
            currentStatusId &&
            (can('tasks.update') || can('tasks.manage'))
          "
          :task-id="task.id"
          :status-id="currentStatusId"
          @updated="onStatusChanged"
        />
      </div>
    </Card>

    <Tabs v-model="activeTab" :tabs="tabs">
      <template #default="{ active }">
        <div v-if="active === 'details'">
          <Card>
            <div class="text-sm">
              <div>Kau Notes: {{ task.kau_notes || '—' }}</div>
            </div>
          </Card>
        </div>
        <div v-else-if="active === 'photos'">
          <Card>
            <div class="flex flex-wrap gap-4">
              <template v-for="photo in task.photos" :key="photo.id">
                <img
                  v-if="hasThumb(photo.file)"
                  :src="photo.file.variants.thumb"
                  :alt="photo.file?.filename || ''"
                  class="w-24 h-24 object-cover rounded"
                />
                <div
                  v-else
                  class="flex items-center gap-2 px-2 py-1 border rounded text-sm text-gray-600"
                >
                  <span>{{ photo.file?.filename }}</span>
                  <Button text="Open" btnClass="btn-dark" :isDisabled="true" />
                </div>
              </template>
            </div>
          </Card>
        </div>
        <div v-else-if="active === 'subtasks'">
          <Card>
            <SubtaskList :task-id="task.id" />
          </Card>
        </div>
        <div v-else-if="active === 'comments'">
          <Card>
            <CommentsThread :comments="task.comments" class="mb-4" />
            <CommentEditor
              v-if="can('tasks.update') || can('tasks.manage')"
              :task-id="task.id"
              allow-files
              @added="onCommentAdded"
            />
          </Card>
        </div>
      </template>
    </Tabs>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import Card from '@/components/ui/Card/index.vue';
import Tabs from '@/components/ui/Tabs.vue';
import Button from '@/components/ui/Button/index.vue';
import CommentsThread from '@/components/comments/CommentsThread.vue';
import CommentEditor from '@/components/comments/CommentEditor.vue';
import SubtaskList from '@/components/tasks/SubtaskList.vue';
import StatusChanger from './StatusChanger.vue';
import { useTaskStatusesStore } from '@/stores/taskStatuses';
import { formatDisplay, parseISO, toISO } from '@/utils/datetime';
import { can } from '@/stores/auth';
import Badge from '@dc/components/Badge';

const route = useRoute();
const { t } = useI18n();

const task = ref<any>(null);
const statusesStore = useTaskStatusesStore();
const statuses = ref<any[]>([]);

const statusClasses: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-800',
  assigned: 'bg-blue-100 text-blue-800',
  in_progress: 'bg-yellow-100 text-yellow-800',
  completed: 'bg-green-100 text-green-800',
  rejected: 'bg-red-100 text-red-800',
  redo: 'bg-purple-100 text-purple-800',
};

const tabs = computed(() => [
  { id: 'details', label: t('tasks.tabs.details') },
  { id: 'photos', label: t('tasks.tabs.photos') },
  { id: 'subtasks', label: t('tasks.tabs.subtasks') },
  { id: 'comments', label: t('tasks.tabs.comments') },
]);
const activeTab = ref('details');

function format(date?: string) {
  return date ? formatDisplay(date) : '';
}

const slaStatusKey = computed(() => {
  const tsk = task.value;
  if (!tsk || !tsk.sla_end_at) return 'none';
  const reference = tsk.completed_at || toISO(new Date());
  return parseISO(reference) <= parseISO(tsk.sla_end_at) ? 'ok' : 'breached';
});

const slaBadgeClass = computed(() => {
  const k = slaStatusKey.value;
  if (k === 'ok') {
    return 'bg-success-500 text-success-500 bg-opacity-[0.12] pill';
  }
  if (k === 'breached') {
    return 'bg-danger-500 text-danger-500 bg-opacity-[0.12] pill';
  }
  return 'bg-secondary-500 text-secondary-500 bg-opacity-[0.12] pill';
});

function hasThumb(file: any) {
  if (file?.variants?.thumb) return true;
  console.warn('TODO: needs signed URL endpoint');
  return false;
}

async function load() {
  const { data } = await api.get(`/tasks/${route.params.id}`);
  task.value = data;
  const res = await statusesStore.fetch('all');
  statuses.value = res.data;
}

onMounted(load);

function onCommentAdded(c: any) {
  task.value.comments.push(c);
}

const currentStatusId = computed(() => {
  const current = task.value?.status;
  const found = statuses.value.find((s: any) => s.name === current);
  return found?.id;
});

function onStatusChanged(id: number) {
  const found = statuses.value.find((s: any) => s.id === id);
  if (found && task.value) {
    task.value.status = found.name;
  }
}

async function toggleWatch() {
  if (!task.value) return;
  if (task.value.is_watching) {
    await api.delete(`/tasks/${task.value.id}/watch`);
    task.value.is_watching = false;
  } else {
    await api.post(`/tasks/${task.value.id}/watch`);
    task.value.is_watching = true;
  }
}
</script>
