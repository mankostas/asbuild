<template>
  <div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-12">
      <div
        v-if="auth.isSuperAdmin"
        class="md:col-span-2 xl:col-span-3 filter-field"
      >
        <span class="filter-label">{{ t('tenant') }}</span>
        <div class="filter-control filter-control--input">
          <TenantSwitcher
            class="w-full"
            :impersonate="false"
          />
        </div>
      </div>

      <div class="md:col-span-2 xl:col-span-4 filter-field">
        <span id="board-filter-search-label" class="filter-label">
          {{ t('board.search') }}
        </span>
        <div :class="[filterControlClasses(local.q), 'gap-3']">
          <Icon icon="heroicons-outline:search" class="h-5 w-5 text-slate-400" />
          <input
            id="board-filter-search"
            v-model="local.q"
            type="search"
            :placeholder="t('board.search')"
            class="filter-input"
            aria-labelledby="board-filter-search-label"
            autocomplete="off"
          />
        </div>
      </div>

      <div class="xl:col-span-3 filter-field">
        <span id="board-filter-assignee-label" class="filter-label">
          {{ t('board.assignee') }}
        </span>
        <div :class="filterControlClasses(local.assigneeId)">
          <select
            id="board-filter-assignee"
            :value="local.assigneeId ?? ''"
            class="filter-select"
            aria-labelledby="board-filter-assignee-label"
            @change="onSelectChange('assigneeId', $event)"
          >
            <option value="">{{ t('board.assignee') }}</option>
            <option
              v-for="opt in assigneeOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </option>
          </select>
        </div>
      </div>

      <div class="xl:col-span-2 filter-field">
        <span id="board-filter-priority-label" class="filter-label">
          {{ t('board.priority') }}
        </span>
        <div :class="filterControlClasses(local.priority)">
          <select
            id="board-filter-priority"
            :value="local.priority ?? ''"
            class="filter-select"
            aria-labelledby="board-filter-priority-label"
            @change="onSelectChange('priority', $event)"
          >
            <option value="">{{ t('board.priority') }}</option>
            <option
              v-for="opt in priorityOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </option>
          </select>
        </div>
      </div>

      <div class="xl:col-span-2 filter-field">
        <span id="board-filter-sla-label" class="filter-label">
          {{ t('board.sla') }}
        </span>
        <div :class="filterControlClasses(local.sla)">
          <select
            id="board-filter-sla"
            :value="local.sla ?? ''"
            class="filter-select"
            aria-labelledby="board-filter-sla-label"
            @change="onSelectChange('sla', $event)"
          >
            <option value="">{{ t('board.sla') }}</option>
            <option
              v-for="opt in slaOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </option>
          </select>
        </div>
      </div>

      <div class="xl:col-span-3 filter-field">
        <span class="filter-label">{{ t('board.taskTypes') }}</span>
        <div :class="filterControlClasses(local.typeIds, 'button')">
          <Dropdown
            parentClass="block w-full"
            :classMenuItems="dropdownMenuClass"
            classItem="px-3 py-2"
          >
            <span :class="dropdownButtonClass">
              <span class="truncate">{{ typeFilterLabel }}</span>
              <Icon icon="heroicons-outline:chevron-down" class="h-4 w-4" />
            </span>
            <template #menus>
              <div class="space-y-1">
                <MenuItem
                  v-for="opt in taskTypeOptions"
                  :key="opt.value"
                  #default="{ active }"
                >
                  <div
                    class="rounded-md px-2 py-1.5 transition"
                    :class="[
                      active
                        ? 'bg-slate-100 text-slate-900 dark:bg-slate-700/60 dark:text-slate-100'
                        : 'text-slate-600 dark:text-slate-200',
                    ]"
                  >
                    <Checkbox
                      v-model="local.typeIds"
                      :value="opt.value"
                      :label="opt.label"
                      class="w-full"
                    />
                  </div>
                </MenuItem>
              </div>
            </template>
          </Dropdown>
        </div>
      </div>

      <div class="xl:col-span-3 filter-field">
        <div :class="filterControlClasses(local.hasPhotos, 'switch')">
          <Switch
            id="board-has-photos"
            v-model="hasPhotosToggle"
            :label="t('board.hasPhotos')"
            :description="t('board.hasPhotosHint')"
            :aria-label="t('board.hasPhotos')"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import Dropdown from '@dc/components/Dropdown';
import Checkbox from '@dc/components/Checkbox';
import Icon from '@dc/components/Icon';
import Switch from '@/components/ui/Switch/index.vue';
import { MenuItem } from '@headlessui/vue';
import { useLookupsStore } from '@/stores/lookups';
import { useTenantStore } from '@/stores/tenant';
import { useAuthStore } from '@/stores/auth';
import TenantSwitcher from '@/components/admin/TenantSwitcher.vue';

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

const auth = useAuthStore();
const lookups = useLookupsStore();
const tenantStore = useTenantStore();
const canViewTasks = computed(() => auth.can('tasks.view'));
const assigneeOptions = ref<Option[]>([]);
const taskTypeOptions = ref<Option[]>([]);
const priorityOptions: Option[] = [
  { value: 'low', label: t('tasks.priority.low') },
  { value: 'medium', label: t('tasks.priority.medium') },
  { value: 'high', label: t('tasks.priority.high') },
];
const slaOptions: Option[] = [
  { value: 'start', label: t('board.sla') + ' Start' },
  { value: 'end', label: t('board.sla') + ' End' },
];

const dropdownMenuClass = 'mt-2 w-64 p-2';

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

const hasPhotosToggle = computed({
  get: () => !!local.value.hasPhotos,
  set: (value: boolean) => {
    local.value.hasPhotos = value ? true : null;
  },
});

const hasTypeSelection = computed(
  () => (local.value.typeIds?.length ?? 0) > 0,
);

const typeFilterLabel = computed(() => {
  const count = local.value.typeIds?.length ?? 0;
  if (!count) return t('board.taskTypes');
  return t('board.taskTypesSelected', { count });
});

const dropdownButtonClass = computed(() =>
  [
    'filter-dropdown-button btn btn-sm w-full items-center justify-between gap-2',
    hasTypeSelection.value ? 'btn-outline-primary active' : 'btn-outline-light',
  ].join(' '),
);

type FilterVariant = 'input' | 'button' | 'switch';

const filterControlClasses = (value: unknown, variant: FilterVariant = 'input') => {
  const classes = ['filter-control', `filter-control--${variant}`];
  const active = Array.isArray(value)
    ? value.length > 0
    : typeof value === 'string'
      ? value.trim().length > 0
      : !!value;
  if (active) classes.push('filter-control--active');
  return classes.join(' ');
};

const onSelectChange = (
  key: 'assigneeId' | 'priority' | 'sla',
  event: Event,
) => {
  const target = event.target as HTMLSelectElement;
  const value = target.value;
  local.value[key] = value ? value : null;
};

async function loadOptions(force = false) {
  if (!canViewTasks.value) {
    assigneeOptions.value = [];
    taskTypeOptions.value = [];
    return;
  }
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
  Object.assign(local.value, props.modelValue, {
    typeIds: Array.isArray(props.modelValue.typeIds)
      ? [...props.modelValue.typeIds]
      : [],
  });
});

watch(
  canViewTasks,
  (val) => {
    if (val) {
      loadOptions(true);
    } else {
      assigneeOptions.value = [];
      taskTypeOptions.value = [];
    }
  },
);

watch(
  () => tenantStore.currentTenantId,
  async () => {
    await loadOptions(true);
  },
);

watch(
  () => props.modelValue,
  (val) =>
    Object.assign(local.value, val, {
      typeIds: Array.isArray(val.typeIds) ? [...val.typeIds] : [],
    }),
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

<style scoped>
.filter-field {
  @apply flex flex-col gap-2;
}

.filter-label {
  @apply text-xs font-semibold uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400;
}

.filter-control {
  @apply relative flex w-full items-center rounded-2xl border border-slate-200/70 bg-white/80 px-3 py-2 shadow-[0_1px_0_rgba(15,23,42,0.06)] transition-all duration-200 backdrop-blur-xl dark:border-slate-700/60 dark:bg-slate-900/60;
}

.filter-control--input {
  @apply py-1.5;
}

.filter-control--button,
.filter-control--switch {
  @apply px-0;
}

.filter-control--switch {
  @apply justify-between px-5 py-4;
}

.filter-control--active {
  @apply border-primary-500/60 shadow-[0_10px_30px_rgba(79,70,229,0.18)];
}

.filter-input {
  @apply h-10 w-full rounded-[1.5rem] border-none bg-transparent px-0 text-sm font-medium text-slate-600 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-200 dark:placeholder:text-slate-400;
}

.filter-select {
  @apply h-11 w-full appearance-none rounded-[1.5rem] border-none bg-transparent pr-8 text-sm font-medium text-slate-600 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-200 dark:placeholder:text-slate-400;
  background-image: url('https://api.iconify.design/akar-icons/chevron-down.svg');
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
}

.dark .filter-select {
  background-image: url('https://api.iconify.design/heroicons/chevron-down-solid.svg?color=white');
}

.filter-select option {
  @apply text-slate-700 dark:text-slate-100;
}

.filter-dropdown-button {
  @apply inline-flex w-full items-center justify-between gap-2 rounded-2xl px-4 py-2 text-sm font-medium transition-colors duration-200;
}

.filter-dropdown-button span {
  @apply truncate;
}

.filter-dropdown-button:hover {
  @apply text-primary-600 dark:text-primary-200;
}
</style>
