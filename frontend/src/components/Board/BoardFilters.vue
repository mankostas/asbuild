<template>
  <div class="space-y-6">
    <div class="grid gap-4 xl:grid-cols-12">
      <div class="xl:col-span-5">
        <div class="filter-field">
          <span class="filter-label">{{ t('board.search') }}</span>
          <div
            class="filter-control filter-control--input"
            :class="{ 'filter-control--active': local.q }"
          >
            <InputGroup
              v-model="local.q"
              :placeholder="t('board.search')"
              class="w-full"
              classInput="!h-11 !bg-transparent !border-none !shadow-none !ring-0 !px-0 text-sm sm:text-base text-slate-700 placeholder:text-slate-400 dark:text-slate-100 dark:placeholder:text-slate-400"
              :aria-label="t('board.search')"
            >
              <template #prepend>
                <Icon
                  icon="heroicons-outline:search"
                  class="text-lg text-slate-400 dark:text-slate-400"
                />
              </template>
            </InputGroup>
          </div>
        </div>
      </div>

      <div class="xl:col-span-3">
        <div class="filter-field">
          <span class="filter-label">{{ t('board.assignee') }}</span>
          <div
            class="filter-control"
            :class="{ 'filter-control--active': local.assigneeId }"
          >
            <Select
              v-model="local.assigneeId"
              :options="assigneeOptions"
              :placeholder="t('board.assignee')"
              classInput="!h-11 !rounded-[1.5rem] !border-none !bg-transparent !px-0 !shadow-none text-sm sm:text-base font-medium text-slate-600 dark:text-slate-200 placeholder:text-slate-400"
              :aria-label="t('board.assignee')"
            />
          </div>
        </div>
      </div>

      <div class="xl:col-span-2">
        <div class="filter-field">
          <span class="filter-label">{{ t('board.priority') }}</span>
          <div
            class="filter-control"
            :class="{ 'filter-control--active': local.priority }"
          >
            <Select
              v-model="local.priority"
              :options="priorityOptions"
              :placeholder="t('board.priority')"
              classInput="!h-11 !rounded-[1.5rem] !border-none !bg-transparent !px-0 !shadow-none text-sm sm:text-base font-medium text-slate-600 dark:text-slate-200 placeholder:text-slate-400"
              :aria-label="t('board.priority')"
            />
          </div>
        </div>
      </div>

      <div class="xl:col-span-2">
        <div class="filter-field">
          <span class="filter-label">{{ t('board.sla') }}</span>
          <div class="filter-control" :class="{ 'filter-control--active': local.sla }">
            <Select
              v-model="local.sla"
              :options="slaOptions"
              :placeholder="t('board.sla')"
              classInput="!h-11 !rounded-[1.5rem] !border-none !bg-transparent !px-0 !shadow-none text-sm sm:text-base font-medium text-slate-600 dark:text-slate-200 placeholder:text-slate-400"
              :aria-label="t('board.sla')"
            />
          </div>
        </div>
      </div>

      <div class="xl:col-span-4">
        <div class="filter-field">
          <span class="filter-label">{{ t('board.taskTypes') }}</span>
          <div
            class="filter-control filter-control--button"
            :class="{ 'filter-control--active': hasTypeSelection }"
          >
            <Dropdown
              :label="typeFilterLabel"
              :labelClass="dropdownButtonClass"
              :classMenuItems="dropdownMenuClass"
              classItem="px-2 py-1.5"
            >
              <template #menus>
                <div class="space-y-1">
                  <MenuItem
                    v-for="opt in taskTypeOptions"
                    :key="opt.value"
                    #default="{ active }"
                  >
                    <button
                      type="button"
                      class="flex w-full items-center justify-between gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition"
                      :class="[
                        isTypeSelected(opt.value)
                          ? 'bg-primary-500/10 text-primary-600 dark:text-primary-200'
                          : active
                            ? 'bg-slate-100/80 text-slate-700 dark:bg-slate-700/40 dark:text-slate-200'
                            : 'text-slate-600 dark:text-slate-200',
                      ]"
                      @click.prevent="toggleType(opt.value)"
                      @keyup.enter.prevent="toggleType(opt.value)"
                      @keyup.space.prevent="toggleType(opt.value)"
                    >
                      <span class="truncate">{{ opt.label }}</span>
                      <Icon
                        v-if="isTypeSelected(opt.value)"
                        icon="heroicons-mini:check"
                        class="h-4 w-4"
                      />
                    </button>
                  </MenuItem>
                </div>
              </template>
            </Dropdown>
          </div>
        </div>
      </div>

      <div class="xl:col-span-8">
        <div class="filter-field">
          <span class="filter-label">{{ t('board.hasPhotos') }}</span>
          <div
            class="filter-control filter-control--switch"
            :class="{ 'filter-control--active': hasPhotosToggle }"
          >
            <div class="flex flex-col gap-1">
              <span class="text-sm font-semibold text-slate-700 dark:text-slate-100">
                {{ t('board.hasPhotos') }}
              </span>
              <span class="text-xs text-slate-400 dark:text-slate-400">
                {{ t('board.hasPhotosHint') }}
              </span>
            </div>
            <Switch
              v-model="hasPhotosToggle"
              :active="hasPhotosToggle"
              :aria-label="t('board.hasPhotos')"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import InputGroup from '@dc/components/InputGroup';
import Select from '@dc/components/Select';
import Dropdown from '@dc/components/Dropdown';
import Icon from '@dc/components/Icon';
import Switch from '@/components/ui/Switch/index.vue';
import { MenuItem } from '@headlessui/vue';
import { useLookupsStore } from '@/stores/lookups';
import { useTenantStore } from '@/stores/tenant';
import { useAuthStore } from '@/stores/auth';

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

const dropdownMenuClass =
  'mt-3 w-64 rounded-2xl border border-slate-200/70 bg-white/95 p-3 shadow-xl shadow-primary-500/10 backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/95';

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
  hasTypeSelection.value
    ? 'filter-dropdown-button text-primary-600 dark:text-primary-200'
    : 'filter-dropdown-button text-slate-600 dark:text-slate-200',
);

function isTypeSelected(id: string) {
  return local.value.typeIds?.includes(id) ?? false;
}

function toggleType(id: string) {
  const current = new Set<string>(local.value.typeIds ?? []);
  if (current.has(id)) {
    current.delete(id);
  } else {
    current.add(id);
  }
  local.value.typeIds = Array.from(current);
}

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

:deep(.filter-control .inputGroup) {
  @apply w-full;
}

:deep(.filter-control .input-group-control) {
  @apply !h-10 !w-full !rounded-[1.5rem] !border-none !bg-transparent !px-0 !text-sm !shadow-none !ring-0 text-slate-700 placeholder:text-slate-400 focus:!outline-none focus:!ring-0 dark:text-slate-100 dark:placeholder:text-slate-400;
}

:deep(.filter-control .input-group-text) {
  @apply bg-transparent text-slate-400 dark:text-slate-400;
}

:deep(.filter-control select) {
  @apply !h-11 !w-full !rounded-[1.5rem] !border-none !bg-transparent !px-0 !text-sm !shadow-none !ring-0 font-medium text-slate-600 placeholder:text-slate-400 focus:!outline-none dark:text-slate-200 dark:placeholder:text-slate-400;
}

:deep(.filter-control select option) {
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
