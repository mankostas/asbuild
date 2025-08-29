<template>
  <div class="flex items-center gap-2">
    <Select
      id="task-status-changer-select"
      v-model="selected"
      :options="options"
      :placeholder="t('tasks.status.update')"
      classInput="min-w-[160px]"
      aria-label="Status"
    />
    <Button
      :text="t('tasks.status.update')"
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
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ taskId: number; statusId: number }>();
const emit = defineEmits<{ (e: 'updated', statusId: number): void }>();

const { t } = useI18n();
const notify = useNotify();

const transitions = ref<any[]>([]);
const selected = ref<number | null>(null);

onMounted(async () => {
  const { data } = await api.get(`/task-statuses/${props.statusId}/transitions`);
  transitions.value = data.data ?? data;
});

const options = computed(() =>
  transitions.value.map((s: any) => ({ value: s.id, label: s.name }))
);

async function apply() {
  const target = transitions.value.find((s: any) => s.id === selected.value);
  if (!target) return;
  try {
    await api.post(`/tasks/${props.taskId}/status`, { status: target.name });
    emit('updated', target.id);
    notify.success(t('tasks.status.updated'));
  } catch (e: any) {
    if (e.status === 422) {
      const reason = e.data?.reason || 'invalid_transition';
      notify.error(t(`tasks.status.errors.${reason}`));
    }
  }
}
</script>
