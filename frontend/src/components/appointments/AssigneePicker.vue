<template>
  <div class="space-y-2">
    <Tabs v-model="tab" :tabs="tabs" />
    <vSelect
      v-model="selected"
      :options="options"
      label="label"
      placeholder="Select assignee"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import vSelect from 'vue-select';
import Tabs from '@/components/ui/Tabs.vue';
import { useLookupsStore } from '@/stores/lookups';

interface AssigneeValue {
  kind: 'team' | 'employee';
  id: number;
}

const props = defineProps<{ modelValue: AssigneeValue | null }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: AssigneeValue | null): void }>();

const lookups = useLookupsStore();
const tab = ref<'teams' | 'employees'>('teams');
const selected = ref<any>(null);

const tabs = [
  { id: 'teams', label: 'Teams' },
  { id: 'employees', label: 'Employees' },
];

const options = computed(() => lookups.assignees[tab.value]);

onMounted(async () => {
  if (!lookups.assignees.teams.length && !lookups.assignees.employees.length) {
    await lookups.fetchAssignees('all');
  }
  if (props.modelValue) {
    tab.value = props.modelValue.kind === 'team' ? 'teams' : 'employees';
    const list = lookups.assignees[tab.value];
    selected.value = list.find((a: any) => a.id === props.modelValue?.id) || null;
  }
});

watch(
  selected,
  (val) => {
    if (val) emit('update:modelValue', { kind: val.kind, id: val.id });
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
    selected.value = list.find((a: any) => a.id === val.id) || null;
  },
  { deep: true },
);
</script>
