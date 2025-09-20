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
import { useAuthStore } from '@/stores/auth';

interface AssigneeValue {
  id: string;
}

const props = defineProps<{
  modelValue: AssigneeValue | null;
  id?: string;
  label?: string;
}>();
const emit = defineEmits<{ (e: 'update:modelValue', value: AssigneeValue | null): void }>();

const lookups = useLookupsStore();
const auth = useAuthStore();
const selected = ref<any>(null);

const options = computed(() => lookups.assignees.employees);

onMounted(async () => {
  if (!lookups.assignees.employees.length) {
    await lookups.fetchAssignees('all', false, auth.allowedClientParams());
  }
  if (props.modelValue) {
    const list = lookups.assignees.employees;
    selected.value =
      list.find((a: any) => String(a.id) === String(props.modelValue?.id)) || null;
  }
});

watch(
  selected,
  (val) => {
    if (val) emit('update:modelValue', { id: String(val.id) });
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
    selected.value =
      list.find((a: any) => String(a.id) === String(val.id)) || null;
  },
  { deep: true },
);
</script>
