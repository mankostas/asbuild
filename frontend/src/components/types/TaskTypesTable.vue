<template>
  <Card>
    <div class="md:flex justify-between pb-6 md:space-y-0 space-y-3 items-center">
      <Breadcrumbs v-if="!$route.meta.hide" />
      <div class="flex items-center gap-2">
        <InputGroup
          v-model="searchTerm"
          :placeholder="t('types.form.search')"
          type="text"
          prependIcon="heroicons-outline:search"
          merged
          classInput="text-xs !h-8"
        />
        <slot name="header-actions" />
      </div>
    </div>

    <vue-good-table
      :columns="columns"
      :rows="filteredRows"
      styleClass="vgt-table bordered centered striped"
      :pagination-options="{ enabled: true, perPage: perPage }"
      :search-options="{ enabled: true, externalQuery: searchTerm }"
      :select-options="selectOptions"
      @selected-rows-change="onSelectedRowsChange"
    >
        <template #table-row="rowProps">
          <span v-if="rowProps.column.field === 'tenant'">
            {{ rowProps.row.tenant?.name || '—' }}
          </span>
          <span v-else-if="rowProps.column.field === 'require_subtasks_complete'">
            <Icon
              v-if="rowProps.row.require_subtasks_complete"
              icon="heroicons-outline:check"
              class="text-success-500"
            />
            <span v-else>—</span>
          </span>
          <span v-else-if="rowProps.column.field === 'actions' && canManageTaskTypes">
            <Dropdown classMenuItems=" w-[140px]">
              <span class="text-xl"><Icon icon="heroicons-outline:dots-vertical" /></span>
              <template #menus>
                <MenuItem>
                  <button
                    type="button"
                    class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                    @click="$emit('edit', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:pencil-square" /></span>
                <span>{{ t('actions.edit') }}</span>
                  </button>
                </MenuItem>
                <MenuItem>
                  <button
                    type="button"
                    class="bg-danger-500 text-danger-500 bg-opacity-30 hover:bg-opacity-100 hover:text-white w-full px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                    @click="$emit('delete', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:trash" /></span>
                    <span>{{ t('actions.delete') }}</span>
                  </button>
                </MenuItem>
                <MenuItem>
                  <button
                    type="button"
                    class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                    @click="$emit('copy', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:document-duplicate" /></span>
                    <span>{{ t(auth.isSuperAdmin ? 'actions.copy' : 'actions.duplicate') }}</span>
                  </button>
                </MenuItem>
              </template>
            </Dropdown>
          </span>
        </template>
        <template #selected-row-actions>
          <template v-if="canManageTaskTypes">
            <button
              type="button"
              class="ml-2 text-danger-500 hover:underline cursor-pointer"
              @click="emit('delete-selected', selectedIds)"
            >
              {{ t('actions.delete') }}
            </button>
            <button
              type="button"
              class="ml-2 text-primary-500 hover:underline cursor-pointer"
              @click="emit('copy-selected', selectedIds)"
            >
              {{ t(auth.isSuperAdmin ? 'actions.copy' : 'actions.duplicate') }}
            </button>
          </template>
        </template>
        <template #pagination-bottom="pagerProps">
          <div class="py-4 px-3">
            <Pagination
              :total="filteredRows.length"
              :current="current"
              :per-page="perPage"
              :pageRange="pageRange"
              :pageChanged="pagerProps.pageChanged"
              :perPageChanged="pagerProps.perPageChanged"
              :options="perPageOptions"
              enableSelect
              @page-changed="current = $event"
            />
          </div>
        </template>
      </vue-good-table>
  </Card>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { MenuItem } from '@headlessui/vue';
import Card from '@/components/ui/Card';
import InputGroup from '@/components/ui/InputGroup';
import Dropdown from '@/components/ui/Dropdown';
import Icon from '@/components/ui/Icon';
import Pagination from '@/components/ui/Pagination';
import Breadcrumbs from "@/Layout/Breadcrumbs.vue";
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { formatDisplay } from '@/utils/datetime';

interface TaskType {
  id: string;
  name: string;
  tenant?: { id: string; name: string } | null;
  statuses?: Record<string, string[]>;
  tasks_count?: number;
  updated_at?: string;
  require_subtasks_complete?: boolean;
}

const props = defineProps<{ rows: TaskType[] }>();
const emit = defineEmits<{
  (e: 'edit', id: string): void;
  (e: 'delete', id: string): void;
  (e: 'copy', id: string): void;
  (e: 'delete-selected', ids: string[]): void;
  (e: 'copy-selected', ids: string[]): void;
}>();

const { t } = useI18n();
const auth = useAuthStore();
const searchTerm = ref('');
const perPage = ref(10);
const current = ref(1);
const pageRange = ref(5);
const perPageOptions = [
  { value: '10', label: '10' },
  { value: '25', label: '25' },
  { value: '50', label: '50' },
];

const canManageTaskTypes = computed(() => auth.can('task_types.manage'));

const selectOptions = computed(() => ({
  enabled: canManageTaskTypes.value,
  selectOnCheckboxOnly: true,
  selectionInfoClass: 'custom-class',
  selectionText: 'rows selected',
  clearSelectionText: 'clear',
  selectAllByGroup: true,
}));

const columns = computed(() => {
  const base = [
    { label: 'ID', field: 'id' },
    { label: 'Name', field: 'name' },
    { label: 'Tenant', field: 'tenant' },
    { label: 'Tasks', field: 'tasks_count' },
    { label: 'Statuses', field: 'statusCount' },
    { label: 'Subtasks Required', field: 'require_subtasks_complete' },
    { label: 'Updated', field: 'updated_at' },
  ];
  if (canManageTaskTypes.value) {
    base.push({ label: 'Actions', field: 'actions' });
  }
  return base;
});

const selectedIds = ref<string[]>([]);

const filteredRows = computed(() => {
  const rows = !searchTerm.value
    ? props.rows
    : props.rows.filter((r) =>
        String(r.name).toLowerCase().includes(searchTerm.value.toLowerCase()),
      );
  return rows.map((row) => ({
    ...row,
    statusCount: Object.keys(row.statuses ?? {}).length,
    updated_at: row.updated_at ? formatDisplay(row.updated_at) : '',
  }));
});

function onSelectedRowsChange(params: any) {
  if (!canManageTaskTypes.value) {
    selectedIds.value = [];
    return;
  }
  selectedIds.value = params.selectedRows.map((r: any) => String(r.id));
}
</script>
