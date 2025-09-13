<template>
  <Card>
    <div class="md:flex justify-between pb-6 md:space-y-0 space-y-3 items-center">
      <Breadcrumbs v-if="!$route.meta.hide" />
      <div class="flex items-center gap-2">
        <InputGroup
          v-model="searchTerm"
          :placeholder="t('roles.form.search')"
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
      @on-selected-rows-change="onSelectedRowsChange"
    >
      <template #table-row="rowProps">
        <span v-if="rowProps.column.field === 'tenant'">
          {{ rowProps.row.tenant?.name || '—' }}
        </span>
        <span v-else-if="rowProps.column.field === 'description'">
          {{ rowProps.row.description || '—' }}
        </span>
        <span v-else-if="rowProps.column.field === 'created_at'">
          {{ formatDate(rowProps.row.created_at) }}
        </span>
        <span v-else-if="rowProps.column.field === 'updated_at'">
          {{ formatDate(rowProps.row.updated_at) }}
        </span>
        <span v-else-if="rowProps.column.field === 'actions'">
          <Dropdown classMenuItems=" w-[160px]">
            <span class="text-xl"><Icon icon="heroicons-outline:dots-vertical" /></span>
            <template #menus>
              <MenuItem v-if="can('roles.update') || can('roles.manage')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('edit', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:pencil-square" /></span>
                  <span>{{ t('actions.edit') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('roles.delete') || can('roles.manage')">
                <button
                  type="button"
                  class="bg-danger-500 text-danger-500 bg-opacity-30 hover:bg-opacity-100 hover:text-white w-full px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('delete', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:trash" /></span>
                  <span>{{ t('actions.delete') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('roles.manage')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('assign', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:user-plus" /></span>
                  <span>{{ t('actions.assign') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="(can('roles.create') || can('roles.manage')) && (auth.isSuperAdmin || !rowProps.row.tenant_id)">
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
        <button
          v-if="can('roles.delete') || can('roles.manage')"
          type="button"
          class="ml-2 text-danger-500 hover:underline cursor-pointer"
          @click="emit('delete-selected', selectedIds)"
        >
          {{ t('actions.delete') }}
        </button>
        <button
          v-if="can('roles.create') || can('roles.manage')"
          type="button"
          class="ml-2 text-primary-500 hover:underline cursor-pointer"
          @click="emit('copy-selected', selectedIds)"
        >
          {{ t(auth.isSuperAdmin ? 'actions.copy' : 'actions.duplicate') }}
        </button>
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
import Breadcrumbs from '@/Layout/Breadcrumbs.vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore, can } from '@/stores/auth';

interface RoleRow {
  id: number;
  name: string;
  description?: string | null;
  level: number;
  created_at?: string;
  updated_at?: string;
  tenant?: { id: number; name: string } | null;
  tenant_id?: number | null;
}

const props = defineProps<{ rows: RoleRow[] }>();
const emit = defineEmits<{
  (e: 'edit', id: number): void;
  (e: 'delete', id: number): void;
  (e: 'assign', id: number): void;
  (e: 'copy', id: number): void;
  (e: 'delete-selected', ids: number[]): void;
  (e: 'copy-selected', ids: number[]): void;
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

const selectOptions = {
  enabled: true,
  selectOnCheckboxOnly: true,
  selectionInfoClass: 'custom-class',
  selectionText: 'rows selected',
  clearSelectionText: 'clear',
  selectAllByGroup: true,
};

const columns = [
  { label: 'ID', field: 'id' },
  { label: 'Name', field: 'name' },
  { label: 'Description', field: 'description' },
  { label: 'Level', field: 'level', type: 'number', sortable: true },
  { label: 'Tenant', field: 'tenant' },
  { label: 'Created', field: 'created_at' },
  { label: 'Updated', field: 'updated_at' },
  { label: 'Actions', field: 'actions' },
];

const selectedIds = ref<number[]>([]);

const filteredRows = computed(() => {
  const rows = !searchTerm.value
    ? props.rows
    : props.rows.filter((r) =>
        String(r.name).toLowerCase().includes(searchTerm.value.toLowerCase()),
      );
  return rows;
});

function onSelectedRowsChange(params: any) {
  selectedIds.value = params.selectedRows.map((r: any) => r.id);
}

function formatDate(d: string) {
  return new Date(d).toLocaleDateString();
}
</script>
