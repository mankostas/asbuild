<template>
  <div v-if="appointment">
    <Card class="mb-4" :title="`Appointment ${appointment.id}`">
      <div class="mb-2 flex items-center gap-2">
        <span class="font-semibold">{{ appointment.type?.name || '—' }}</span>
        <span
          class="px-2 py-1 rounded-full text-xs font-semibold"
          :class="statusClasses[appointment.status]"
        >
          {{ appointment.status.replace(/_/g, ' ') }}
        </span>
      </div>
      <ul class="text-sm mb-2">
        <li>Scheduled: {{ format(appointment.scheduled_at) || '—' }}</li>
        <li>Started: {{ format(appointment.started_at) || '—' }}</li>
        <li>Completed: {{ format(appointment.completed_at) || '—' }}</li>
        <li>SLA End: {{ format(appointment.sla_end_at) || '—' }}</li>
        <li>SLA: {{ slaStatus }}</li>
      </ul>
      <div class="flex flex-wrap gap-2 mt-2">
        <Button
          v-for="a in changeActions"
          :key="a.value"
          :text="`Mark ${a.label}`"
          btnClass="btn-outline-primary btn-sm"
          @click="updateStatus(a.value)"
        />
      </div>
    </Card>

    <Tabs v-model="activeTab" :tabs="tabs">
      <template #default="{ active }">
        <div v-if="active === 'details'">
          <Card>
            <div class="text-sm">
              <div>Kau Notes: {{ appointment.kau_notes || '—' }}</div>
            </div>
          </Card>
        </div>
        <div v-else-if="active === 'photos'">
          <Card>
            <div class="flex flex-wrap gap-4">
              <template v-for="photo in appointment.photos" :key="photo.id">
                <img
                  v-if="hasThumb(photo.file)"
                  :src="photo.file.variants.thumb"
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
        <div v-else-if="active === 'comments'">
          <Card>
            <CommentsThread :comments="appointment.comments" class="mb-4" />
            <CommentEditor :appointment-id="appointment.id" @added="onCommentAdded" />
          </Card>
        </div>
      </template>
    </Tabs>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/services/api';
import Card from '@/components/ui/Card/index.vue';
import Tabs from '@/components/ui/Tabs.vue';
import Button from '@/components/ui/Button/index.vue';
import { useNotify } from '@/plugins/notify';
import CommentsThread from '@/components/comments/CommentsThread.vue';
import CommentEditor from '@/components/comments/CommentEditor.vue';

const route = useRoute();
const notify = useNotify();

const appointment = ref<any>(null);

const statusClasses: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-800',
  assigned: 'bg-blue-100 text-blue-800',
  in_progress: 'bg-yellow-100 text-yellow-800',
  completed: 'bg-green-100 text-green-800',
  rejected: 'bg-red-100 text-red-800',
  redo: 'bg-purple-100 text-purple-800',
};

const tabs = [
  { id: 'details', label: 'Details' },
  { id: 'photos', label: 'Photos' },
  { id: 'comments', label: 'Comments' },
];
const activeTab = ref('details');

function format(date?: string) {
  return date ? new Date(date).toLocaleString() : '';
}

const slaStatus = computed(() => {
  const appt = appointment.value;
  if (!appt) return 'none';
  if (appt.sla_status) return appt.sla_status;
  if (!appt.sla_end_at) return 'none';
  const reference = appt.completed_at || new Date().toISOString();
  return new Date(reference) <= new Date(appt.sla_end_at)
    ? 'within'
    : 'breached';
});

function hasThumb(file: any) {
  if (file?.variants?.thumb) return true;
  console.warn('TODO: needs signed URL endpoint');
  return false;
}

async function load() {
  const { data } = await api.get(`/appointments/${route.params.id}`);
  appointment.value = data;
}

onMounted(load);

function onCommentAdded(c: any) {
  appointment.value.comments.push(c);
}

async function updateStatus(status: string) {
  if (!appointment.value) return;
  try {
    await api.patch(`/appointments/${appointment.value.id}`, { status });
    appointment.value.status = status;
    notify.success('Status updated');
  } catch (e: any) {
    if (e.status === 422) {
      notify.error('Invalid status transition');
    }
  }
}

const changeActions = [
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Completed', value: 'completed' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Redo', value: 'redo' },
];
</script>
