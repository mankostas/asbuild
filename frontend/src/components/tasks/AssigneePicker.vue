<template>
  <VueSelect :id="id" :label="label" classLabel="sr-only">
    <template #default="{ inputId }">
      <vSelect
        :id="inputId"
        v-model="selected"
        :options="options"
        label="label"
        placeholder="Select assignee"
      />
    </template>
  </VueSelect>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import { useLookupsStore } from '@/stores/lookups';

interface AssigneeValue {
  id: number;
}

const props = defineProps<{
  modelValue: AssigneeValue | null;
  id?: string;
  label?: string;
}>();
const emit = defineEmits<{ (e: 'update:modelValue', value: AssigneeValue | null): void }>();

const lookups = useLookupsStore();
const selected = ref<any>(null);

const options = computed(() => lookups.assignees.employees);

onMounted(async () => {
  if (!lookups.assignees.employees.length) {
    await lookups.fetchAssignees('all');
  }
  if (props.modelValue) {
    const list = lookups.assignees.employees;
    selected.value = list.find((a: any) => a.id === props.modelValue?.id) || null;
  }
});

watch(
  selected,
  (val) => {
    if (val) emit('update:modelValue', { id: val.id });
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
    const list = lookups.assignees.employees;
    selected.value = list.find((a: any) => a.id === val.id) || null;
  },
  { deep: true },
);
</script>
