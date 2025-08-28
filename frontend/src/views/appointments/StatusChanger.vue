<template>
  <div class="flex items-center gap-2">
    <Select
      v-model="selected"
      :options="options"
      placeholder="Change status"
      classInput="min-w-[160px]"
    />
    <Button
      text="Update"
      btnClass="btn-dark btn-sm"
      :isDisabled="!selected"
      @click="apply"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import Select from '@/components/ui/Select/index.vue';
import Button from '@/components/ui/Button/index.vue';
import { useStatusesStore } from '@/stores/statuses';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';

const props = defineProps<{ appointmentId: number; statusId: number }>();
const emit = defineEmits<{ (e: 'updated', statusId: number): void }>();

const store = useStatusesStore();
const notify = useNotify();

const transitions = ref<any[]>([]);
const selected = ref<number | null>(null);

onMounted(async () => {
  transitions.value = await store.fetchTransitions(props.statusId);
});

const options = computed(() =>
  transitions.value.map((s: any) => ({ value: s.id, label: s.name }))
);

async function apply() {
  const target = transitions.value.find((s: any) => s.id === selected.value);
  if (!target) return;
  try {
    await api.patch(`/appointments/${props.appointmentId}`, { status: target.name });
    emit('updated', target.id);
    notify.success('Status updated');
  } catch (e: any) {
    if (e.status === 422) {
      notify.error('Invalid status transition');
    }
  }
}
</script>
