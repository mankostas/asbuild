<template>
  <div class="flex flex-wrap items-end gap-4">
    <InputGroup
      v-model="local.q"
      :placeholder="t('board.search')"
      classInput="h-10"
      :aria-label="t('board.search')"
    >
      <template #prepend>
        <Icon icon="heroicons-outline:search" />
      </template>
    </InputGroup>
    <Select
      v-model="local.assigneeId"
      :options="assigneeOptions"
      classInput="h-10"
      :placeholder="t('board.assignee')"
      :aria-label="t('board.assignee')"
    />
    <Select
      v-model="local.priority"
      :options="priorityOptions"
      classInput="h-10"
      :placeholder="t('board.priority')"
      :aria-label="t('board.priority')"
    />
    <Select
      v-model="local.sla"
      :options="slaOptions"
      classInput="h-10"
      :placeholder="t('board.sla')"
      :aria-label="t('board.sla')"
    />
    <Dropdown :label="t('board.taskTypes')" labelClass="btn btn-light h-10">
      <template #menus>
        <MenuItem
          v-for="opt in taskTypeOptions"
          :key="opt.value"
          #default="{ active }"
        >
          <div
            class="flex items-center space-x-2 px-4 py-2"
            :class="active ? 'bg-slate-100' : ''"
          >
            <Checkbox v-model="local.typeIds" :value="opt.value" />
            <span>{{ opt.label }}</span>
          </div>
        </MenuItem>
      </template>
    </Dropdown>
    <div class="inline-flex items-center gap-2">
      <Checkbox
        id="has-photos"
        v-model="local.hasPhotos"
        :aria-label="t('board.hasPhotos')"
      />
      <span>{{ t('board.hasPhotos') }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import InputGroup from '@dc/components/InputGroup';
import Select from '@dc/components/Select';
import Dropdown from '@dc/components/Dropdown';
import Checkbox from '@dc/components/Checkbox';
import Icon from '@dc/components/Icon';
import { MenuItem } from '@headlessui/vue';
import { useLookupsStore } from '@/stores/lookups';
import { useTenantStore } from '@/stores/tenant';

interface Option { value: any; label: string }
interface Filters {
  assigneeId: string | null;
  priority: string | null;
  sla: string | null;
  q: string | null;
  hasPhotos: boolean | null;
  typeIds: any[];
  mine: boolean;
  dueToday: boolean;
  breachedOnly: boolean;
}

const props = defineProps<{ modelValue: Filters }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: Filters): void }>();
const { t } = useI18n();

const lookups = useLookupsStore();
const tenantStore = useTenantStore();
const assigneeOptions = ref<Option[]>([]);
const taskTypeOptions = ref<Option[]>([]);
const priorityOptions: Option[] = [
  { value: 'low', label: t('tasks.priority.low') },
  { value: 'normal', label: t('tasks.priority.normal') },
  { value: 'high', label: t('tasks.priority.high') },
];
const slaOptions: Option[] = [
  { value: 'start', label: t('board.sla') + ' Start' },
  { value: 'end', label: t('board.sla') + ' End' },
];

const local = ref<Filters>({
  assigneeId: null,
  priority: null,
  sla: null,
  q: null,
  hasPhotos: null,
  typeIds: [],
  mine: false,
  dueToday: false,
  breachedOnly: false,
});

async function loadOptions(force = false) {
  await lookups.fetchAssignees('employees', force);
  assigneeOptions.value = lookups.assignees.employees.map((a: any) => ({
    value: String(a.id),
    label: a.name,
  }));
  const { data } = await api.get('/task-types/options');
  taskTypeOptions.value = data.map((t: any) => ({ value: String(t.id), label: t.name }));
}

onMounted(async () => {
  await loadOptions();
  Object.assign(local.value, props.modelValue);
});

watch(
  () => tenantStore.currentTenantId,
  async () => {
    await loadOptions(true);
  },
);

watch(
  () => props.modelValue,
  (val) => Object.assign(local.value, val),
  { deep: true },
);

let timer: number | undefined;
watch(
  local,
  (val) => {
    clearTimeout(timer);
    timer = window.setTimeout(() => emit('update:modelValue', { ...val }), 300);
  },
  { deep: true },
);
</script>
