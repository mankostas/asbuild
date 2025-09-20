<template>
  <div class="flex items-center gap-2">
    <Select
      id="status-changer-select"
      v-model="selected"
      :options="options"
      placeholder="Change status"
      classInput="min-w-[160px]"
      aria-label="Status"
    />
    <Button
      text="Update"
      btnClass="btn-dark btn-sm"
      :isDisabled="!selected"
      @click="apply"
      @keyup.enter="apply"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import Select from '@/components/ui/Select/index.vue';
import Button from '@/components/ui/Button/index.vue';
import { useTaskStatusesStore } from '@/stores/taskStatuses';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';

const props = defineProps<{ taskId: string; statusId: string }>();
const emit = defineEmits<{ (e: 'updated', statusId: string): void }>();

const store = useTaskStatusesStore();
const notify = useNotify();

const transitions = ref<any[]>([]);
const selected = ref<string | null>(null);

onMounted(async () => {
  const response = await store.fetchTransitions(props.statusId);
  const list = response?.data ?? response;
  transitions.value = Array.isArray(list)
    ? list.map((s: any) => ({ ...s, id: String(s.public_id ?? s.id) }))
    : [];
});

const options = computed(() =>
  transitions.value.map((s: any) => ({ value: String(s.id), label: s.name }))
);

async function apply() {
  const target = transitions.value.find(
    (s: any) => String(s.id) === selected.value,
  );
  if (!target) return;
  try {
    await api.post(`/tasks/${props.taskId}/status`, { status: target.name });
    emit('updated', String(target.id));
    notify.success('Status updated');
  } catch (e: any) {
    if (e.status === 422) {
      notify.error('Invalid status transition');
    }
  }
}
</script>
