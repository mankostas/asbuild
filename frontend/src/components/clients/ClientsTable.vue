<template>
  <Card>
    <div class="space-y-4">
      <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <Breadcrumbs v-if="!$route.meta.hide" />
        <div class="flex flex-col-reverse gap-2 md:flex-row md:items-center md:justify-end">
          <slot name="header-actions" />
          <InputGroup
            v-model="localSearch"
            :placeholder="t('clients.form.search')"
            type="text"
            prependIcon="heroicons-outline:search"
            merged
            classInput="text-xs !h-8"
            @update:model-value="onSearch"
          />
        </div>
      </div>

      <div v-if="$slots.filters" class="space-y-3">
        <slot name="filters" />
      </div>
    </div>

    <div class="mt-4">
      <VueGoodTable
        :key="tableKey"
        :columns="columns"
        :rows="rows"
        :styleClass="'vgt-table bordered centered striped'"
        :search-options="{ enabled: true, externalQuery: searchQuery }"
        :select-options="selectOptions"
        :pagination-options="paginationOptions"
        :sort-options="sortOptions"
        :totalRows="total"
        mode="remote"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-selected-rows-change="onSelectedRowsChange"
      >
        <template #table-row="rowProps">
          <div v-if="rowProps.column.field === 'name'" class="flex flex-col">
            <span class="text-sm font-medium">{{ rowProps.row.name }}</span>
            <span v-if="rowProps.row.email" class="text-xs text-slate-500">
              {{ rowProps.row.email }}
            </span>
          </div>
          <span v-else-if="rowProps.column.field === 'email'">
            {{ rowProps.row.email || '—' }}
          </span>
          <span v-else-if="rowProps.column.field === 'phone'">
            {{ rowProps.row.phone || '—' }}
          </span>
          <span v-else-if="rowProps.column.field === 'ownerName'">
            {{ rowProps.row.ownerName || '—' }}
          </span>
          <span v-else-if="rowProps.column.field === 'tenantName'">
            {{ rowProps.row.tenantName || '—' }}
          </span>
          <span v-else-if="rowProps.column.field === 'status'">
            <Badge :badge-class="statusBadge(rowProps.row.status).class">
              {{ statusBadge(rowProps.row.status).label }}
            </Badge>
          </span>
          <span v-else-if="rowProps.column.field === 'actions'">
            <Dropdown classMenuItems="w-56">
              <span class="text-xl"><Icon icon="heroicons-outline:dots-vertical" /></span>
              <template #menus>
                <MenuItem v-if="canView">
                  <button
                    type="button"
                    class="menu-item"
                    @click="$emit('view', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:eye" /></span>
                    <span>{{ t('actions.view') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canEdit">
                  <button
                    type="button"
                    class="menu-item"
                    @click="$emit('edit', rowProps.row.id)"
                  >
                    <span class="text-base"
                      ><Icon icon="heroicons-outline:pencil-square"
                    /></span>
                    <span>{{ t('actions.edit') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canTransfer && rowProps.row.status !== 'trashed'">
                  <button
                    type="button"
                    class="menu-item"
                    @click="$emit('transfer', rowProps.row.id)"
                  >
                    <span class="text-base"
                      ><Icon icon="heroicons-outline:user-group"
                    /></span>
                    <span>{{ t('actions.transferOwnership') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canEdit && rowProps.row.status === 'active'">
                  <button
                    type="button"
                    class="menu-item"
                    @click="$emit('archive', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:archive-box" /></span>
                    <span>{{ t('actions.archive') }}</span>
                  </button>
                </MenuItem>
                <MenuItem
                  v-if="canEdit && rowProps.row.status === 'archived'"
                >
                  <button
                    type="button"
                    class="menu-item"
                    @click="$emit('restore', { id: rowProps.row.id, type: 'archive' })"
                  >
                    <span class="text-base"
                      ><Icon icon="heroicons-outline:arrow-path-rounded-square"
                    /></span>
                    <span>{{ t('actions.unarchive') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canDelete && rowProps.row.status === 'trashed'">
                  <button
                    type="button"
                    class="menu-item"
                    @click="$emit('restore', { id: rowProps.row.id, type: 'trash' })"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:arrow-uturn-left" /></span>
                    <span>{{ t('actions.restore') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canDelete">
                  <button
                    type="button"
                    class="menu-item danger"
                    @click="$emit('delete', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:trash" /></span>
                    <span>{{ t('actions.delete') }}</span>
                  </button>
                </MenuItem>
              </template>
            </Dropdown>
          </span>
        </template>

        <template #selected-row-actions>
          <div class="flex items-center gap-3">
            <slot name="selected-row-actions" :selected-ids="selectedIds">
              <button
                v-if="canDelete && selectedIds.length"
                type="button"
                class="ml-2 text-danger-500 hover:underline"
                @click="$emit('delete-selected', selectedIds)"
              >
                {{ t('actions.delete') }}
              </button>
            </slot>
          </div>
        </template>

        <template #table-actions-bottom>
          <div v-if="loading" class="py-4 text-sm text-slate-500">
            {{ t('clients.table.loading') }}
          </div>
        </template>

        <template #emptystate>
          <div class="py-8 text-center text-sm text-slate-500">
            {{ t('clients.table.empty') }}
          </div>
        </template>
      </VueGoodTable>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { VueGoodTable } from 'vue-good-table-next';
import { MenuItem } from '@headlessui/vue';
import Card from '@/components/ui/Card';
import InputGroup from '@/components/ui/InputGroup';
import Dropdown from '@/components/ui/Dropdown';
import Icon from '@/components/ui/Icon';
import Badge from '@/components/ui/Badge';
import Breadcrumbs from '@/Layout/Breadcrumbs.vue';
import { useI18n } from 'vue-i18n';
import { can } from '@/stores/auth';

interface ClientTableRow {
  id: number | string;
  name: string;
  email?: string | null;
  phone?: string | null;
  ownerName?: string | null;
  tenantName?: string | null;
  status: 'active' | 'archived' | 'trashed';
}

const props = defineProps<{
  rows: ClientTableRow[];
  loading?: boolean;
  total: number;
  page: number;
  perPage: number;
  search: string;
  sort: string;
  direction: 'asc' | 'desc';
  selectable?: boolean;
  showTenant?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:search', value: string): void;
  (e: 'update:page', value: number): void;
  (e: 'update:per-page', value: number): void;
  (e: 'update:sort', value: { sort: string; direction: 'asc' | 'desc' }): void;
  (e: 'selection-change', ids: Array<number | string>): void;
  (e: 'view', id: number | string): void;
  (e: 'edit', id: number | string): void;
  (e: 'archive', id: number | string): void;
  (e: 'restore', payload: { id: number | string; type: 'archive' | 'trash' }): void;
  (e: 'delete', id: number | string): void;
  (e: 'delete-selected', ids: Array<number | string>): void;
  (e: 'transfer', id: number | string): void;
}>();

const { t } = useI18n();
const localSearch = ref(props.search);
const selectedIds = ref<Array<number | string>>([]);

const rows = computed(() => props.rows);
const total = computed(() => props.total);
const loading = computed(() => props.loading ?? false);

const tableKey = computed(
  () =>
    `${props.page}-${props.perPage}-${props.sort}-${props.direction}-${props.search}`,
);

const columns = computed(() => {
  const base = [
    { label: t('clients.table.columns.name'), field: 'name', sortable: true },
    { label: t('clients.table.columns.email'), field: 'email', sortable: false },
    { label: t('clients.table.columns.phone'), field: 'phone', sortable: false },
    { label: t('clients.table.columns.owner'), field: 'ownerName', sortable: false },
  ];

  if (props.showTenant) {
    base.push({
      label: t('clients.table.columns.tenant'),
      field: 'tenantName',
      sortable: false,
    });
  }

  base.push({
    label: t('clients.table.columns.status'),
    field: 'status',
    sortable: false,
  });

  base.push({ label: t('actions.actions'), field: 'actions', sortable: false });
  return base;
});

const perPageOptions = [10, 20, 50, 100];

const paginationOptions = computed(() => ({
  enabled: true,
  mode: 'records',
  perPage: props.perPage,
  nextLabel: '›',
  prevLabel: '‹',
  rowsPerPageLabel: t('clients.table.perPage'),
  ofLabel: '/',
  pageLabel: t('clients.table.page'),
  totalLabel: t('clients.table.total'),
  perPageDropdown: perPageOptions,
  dropdownAllowAll: false,
  setCurrentPage: props.page,
}));

const sortOptions = computed(() => ({
  enabled: true,
  initialSortBy: { field: props.sort, type: props.direction },
}));

const selectOptions = computed(() => {
  if (!props.selectable || !canDelete.value) {
    return { enabled: false };
  }
  return {
    enabled: true,
    selectOnCheckboxOnly: true,
    selectionInfoClass: 'table-selected-bar',
    selectionText: t('clients.table.rowsSelected'),
    clearSelectionText: t('actions.clear'),
  };
});

const searchQuery = computed(() => props.search);

const canView = computed(() => can('clients.view'));
const canEdit = computed(() => can('clients.manage'));
const canTransfer = computed(() => can('clients.manage'));
const canDelete = computed(() => can('clients.delete') || can('clients.manage'));

watch(
  () => props.search,
  (value) => {
    if (value !== localSearch.value) {
      localSearch.value = value;
    }
  },
);

function onSearch(value: string) {
  emit('update:search', value);
}

function onPageChange({ currentPage }: { currentPage: number }) {
  emit('update:page', currentPage);
}

function onPerPageChange({ currentPerPage }: { currentPerPage: number }) {
  emit('update:per-page', currentPerPage);
}

function onSortChange(params: Array<{ field: string; type: 'asc' | 'desc' }>) {
  const first = params?.[0];
  if (!first || first.field === 'actions') {
    return;
  }
  emit('update:sort', {
    sort: first.field,
    direction: first.type === 'asc' ? 'asc' : 'desc',
  });
}

function onSelectedRowsChange(selection: {
  selectedRows: Array<{ id: number | string }>;
}) {
  selectedIds.value = selection.selectedRows.map((row) => row.id);
  emit('selection-change', selectedIds.value);
}

function statusBadge(status: 'active' | 'archived' | 'trashed') {
  switch (status) {
    case 'archived':
      return {
        label: t('clients.status.archived'),
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300',
      };
    case 'trashed':
      return {
        label: t('clients.status.trashed'),
        class: 'bg-danger-100 text-danger-600 dark:bg-danger-400/10 dark:text-danger-300',
      };
    default:
      return {
        label: t('clients.status.active'),
        class: 'bg-success-100 text-success-600 dark:bg-success-400/10 dark:text-success-300',
      };
  }
}
</script>

<style scoped>
.menu-item {
  @apply w-full px-4 py-2 text-sm flex items-center gap-2 text-left transition hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50;
}

.menu-item.danger {
  @apply bg-danger-500 bg-opacity-20 text-danger-500 hover:bg-opacity-100 hover:text-white;
}
</style>
