<template>
  <div class="space-y-2">
    <Tabs v-model="tab" :tabs="tabs" />
    <VueSelect
      :id="id"
      v-model="selected"
      :options="options"
      :label="label"
      classLabel="sr-only"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import Tabs from '@/components/ui/Tabs.vue';
import { useLookupsStore } from '@/stores/lookups';
import { useAuthStore } from '@/stores/auth';

interface ReviewerValue {
  kind: 'team' | 'employee';
  id: string;
}

const props = defineProps<{
  modelValue: ReviewerValue | null;
  id?: string;
  label?: string;
}>();
const emit = defineEmits<{ (e: 'update:modelValue', value: ReviewerValue | null): void }>();

const lookups = useLookupsStore();
const auth = useAuthStore();
const tab = ref<'teams' | 'employees'>('teams');
const selected = ref<any>(null);

const tabs = [
  { id: 'teams', label: 'Teams' },
  { id: 'employees', label: 'Employees' },
];

const options = computed(() => lookups.assignees[tab.value]);

onMounted(async () => {
  if (!lookups.assignees.teams.length && !lookups.assignees.employees.length) {
    await lookups.fetchAssignees('all', false, auth.allowedClientParams());
  }
  if (props.modelValue) {
    tab.value = props.modelValue.kind === 'team' ? 'teams' : 'employees';
    const list = lookups.assignees[tab.value];
    selected.value =
      list.find((a: any) => String(a.id) === String(props.modelValue?.id)) || null;
  }
});

watch(
  selected,
  (val) => {
    if (val) emit('update:modelValue', { kind: val.kind, id: String(val.id) });
    else emit('update:modelValue', null);
  },
  { deep: true },
);

watch(
  () => props.modelValue,
  (val) => {
    if (!val) {
      selected.value = null;
      return;
    }
    tab.value = val.kind === 'team' ? 'teams' : 'employees';
    const list = lookups.assignees[tab.value];
    selected.value =
      list.find((a: any) => String(a.id) === String(val.id)) || null;
  },
  { deep: true },
);
</script>
