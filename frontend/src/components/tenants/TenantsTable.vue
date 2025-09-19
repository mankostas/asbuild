<template>
  <Card>
    <div class="space-y-4">
      <div
        v-if="!$route.meta.hide"
        class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
      >
        <Breadcrumbs />
      </div>

      <div class="space-y-3">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
          <div v-if="$slots.filters" class="flex-1 space-y-3">
            <slot name="filters" />
          </div>

          <div
            class="flex flex-col-reverse gap-2 md:flex-row md:items-center md:justify-end"
            :class="{ 'md:ml-auto': !$slots.filters }"
          >
            <InputGroup
              v-model="localSearch"
              :placeholder="t('tenants.form.search')"
              type="text"
              prependIcon="heroicons-outline:search"
              merged
              classInput="text-xs !h-8"
              @update:modelValue="onSearch"
            />
            <slot name="header-actions" />
          </div>
        </div>
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
        @selected-rows-change="onSelectedRowsChange"
      >
        <template #table-row="rowProps">
          <div
            v-if="rowProps.column.field === 'name'"
            class="flex items-center justify-center gap-3"
          >
            <div
              class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-sm font-medium text-slate-600"
            >
              {{ getInitials(rowProps.row.name) }}
            </div>
            <div class="flex flex-col leading-tight text-left">
              <span class="text-sm font-medium">{{ rowProps.row.name }}</span>
              <span
                v-if="rowProps.row.slug || rowProps.row.domain"
                class="text-xs text-gray-500"
              >
                {{ rowProps.row.domain || rowProps.row.slug }}
              </span>
            </div>
          </div>
          <span v-else-if="rowProps.column.field === 'phone'">
            {{ rowProps.row.phone || '—' }}
          </span>
          <span v-else-if="rowProps.column.field === 'address'">
            {{ rowProps.row.address || '—' }}
          </span>
          <span v-else-if="rowProps.column.field === 'feature_count'">
            {{ formatFeatureCount(rowProps.row) }}
          </span>
          <div v-else-if="rowProps.column.field === 'status'" class="flex justify-center">
            <div
              v-if="statusForRow(rowProps.row) === 'trashed'"
              class="flex justify-center"
            >
              <Badge :badge-class="statusBadge('trashed').class">
                {{ statusBadge('trashed').label }}
              </Badge>
            </div>
            <div v-else class="flex items-center justify-center gap-2">
              <Switch
                :model-value="statusForRow(rowProps.row) === 'active'"
                :disabled="
                  togglingStatusSet.has(String(rowProps.row.id)) ||
                  !canToggle ||
                  ['archived', 'trashed'].includes(statusForRow(rowProps.row))
                "
                :aria-label="t('tenants.table.columns.status')"
                @update:modelValue="(value: boolean) =>
                  $emit('toggle-status', { id: rowProps.row.id, active: value })
                "
              />
              <Badge :badge-class="statusBadge(statusForRow(rowProps.row)).class">
                {{ statusBadge(statusForRow(rowProps.row)).label }}
              </Badge>
            </div>
          </div>
          <div v-else-if="rowProps.column.field === 'actions'" class="flex justify-center">
            <Dropdown classMenuItems="w-56">
              <span class="text-xl"><Icon icon="heroicons-outline:dots-vertical" /></span>
              <template #menus>
                <MenuItem v-if="canView">
                  <button type="button" class="menu-item" @click="$emit('view', rowProps.row.id)">
                    <span class="text-base"><Icon icon="heroicons-outline:eye" /></span>
                    <span>{{ t('actions.view') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canManage">
                  <button type="button" class="menu-item" @click="$emit('edit', rowProps.row.id)">
                    <span class="text-base"><Icon icon="heroicons-outline:pencil-square" /></span>
                    <span>{{ t('actions.edit') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canManage">
                  <button
                    type="button"
                    class="menu-item"
                    @click="$emit('impersonate', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:user" /></span>
                    <span>{{ t('actions.impersonate') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canManage && rowProps.row.owner">
                  <button
                    type="button"
                    class="menu-item"
                    @click="$emit('owner-resend-invite', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:envelope" /></span>
                    <span>{{ t('actions.resendInvite') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canManage && rowProps.row.owner">
                  <button
                    type="button"
                    class="menu-item"
                    @click="$emit('owner-reset-email', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:at-symbol" /></span>
                    <span>{{ t('actions.resetEmail') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canManage && rowProps.row.owner">
                  <button
                    type="button"
                    class="menu-item"
                    @click="$emit('owner-password-reset', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:key" /></span>
                    <span>{{ t('actions.sendPasswordReset') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canManage && statusForRow(rowProps.row) === 'active'">
                  <button type="button" class="menu-item" @click="$emit('archive', rowProps.row.id)">
                    <span class="text-base"><Icon icon="heroicons-outline:archive-box" /></span>
                    <span>{{ t('actions.archive') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canManage && statusForRow(rowProps.row) === 'archived'">
                  <button type="button" class="menu-item" @click="$emit('unarchive', rowProps.row.id)">
                    <span class="text-base"><Icon icon="heroicons-outline:arrow-path-rounded-square" /></span>
                    <span>{{ t('actions.unarchive') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canDelete && statusForRow(rowProps.row) === 'trashed'">
                  <button type="button" class="menu-item" @click="$emit('restore', rowProps.row.id)">
                    <span class="text-base"><Icon icon="heroicons-outline:arrow-uturn-left" /></span>
                    <span>{{ t('actions.restore') }}</span>
                  </button>
                </MenuItem>
                <MenuItem v-if="canDelete">
                  <button type="button" class="menu-item danger" @click="$emit('delete', rowProps.row.id)">
                    <span class="text-base"><Icon icon="heroicons-outline:trash" /></span>
                    <span>{{ t('actions.delete') }}</span>
                  </button>
                </MenuItem>
              </template>
            </Dropdown>
          </div>
        </template>

        <template #selected-row-actions>
          <div class="flex items-center gap-3">
            <slot
              name="selected-row-actions"
              :selected-ids="selectedIds"
              :archivable-ids="archivableSelectedIds"
            >
              <button
                v-if="canManage && archivableSelectedIds.length"
                type="button"
                class="ml-2 text-amber-500 hover:underline"
                @click="$emit('archive-selected', archivableSelectedIds)"
              >
                {{ t('tenants.bulk.archiveSelected') }}
              </button>
              <button
                v-if="canDelete && selectedIds.length"
                type="button"
                class="ml-2 text-danger-500 hover:underline"
                @click="$emit('delete-selected', selectedIds)"
              >
                {{ t('tenants.bulk.deleteSelected') }}
              </button>
            </slot>
          </div>
        </template>

        <template #table-actions-bottom>
          <div v-if="loading" class="py-4 text-sm text-slate-500">
            {{ t('tenants.table.loading') }}
          </div>
        </template>

        <template #emptystate>
          <div class="py-8 text-center text-sm text-slate-500">
            {{ t('tenants.table.empty') }}
          </div>
        </template>

        <template #pagination-bottom="pagerProps">
          <div class="border-t border-slate-100 px-3 py-4 dark:border-slate-700">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
              <label class="flex items-center gap-2 text-sm text-slate-500" for="tenants-table-per-page">
                <span>{{ t('tenants.table.perPage') }}</span>
                <Select
                  id="tenants-table-per-page"
                  :modelValue="currentPerPage"
                  :options="perPageSelectOptions"
                  classInput="!h-9 w-24"
                  @update:model-value="(value) => onPerPageSelect(Number(value), pagerProps)"
                />
              </label>
              <Pagination
                :total="total"
                :current="currentPage"
                :per-page="currentPerPage"
                :pageRange="pageRange"
                :pageChanged="pagerProps.pageChanged"
                :perPageChanged="pagerProps.perPageChanged"
              />
            </div>
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
import Pagination from '@/components/ui/Pagination';
import Select from '@/components/ui/Select';
import Switch from '@/components/ui/Switch/index.vue';
import Breadcrumbs from '@/Layout/Breadcrumbs.vue';
import { useI18n } from 'vue-i18n';
import { can } from '@/stores/auth';

type SortDirection = 'asc' | 'desc';
type TenantStatus = 'active' | 'inactive' | 'archived' | 'trashed';

interface TenantOwner {
  id: number | string;
  name?: string | null;
  email?: string | null;
}

interface TenantRow {
  id: number | string;
  name: string;
  slug?: string | null;
  domain?: string | null;
  phone?: string | null;
  address?: string | null;
  features?: string[] | null;
  feature_count?: number | null;
  features_count?: number | null;
  owner?: TenantOwner | null;
  status?: 'active' | 'inactive';
  archived_at?: string | null;
  deleted_at?: string | null;
}

const props = defineProps<{
  rows: TenantRow[];
  total: number;
  page: number;
  perPage: number;
  search: string;
  sort: string;
  direction: SortDirection;
  loading?: boolean;
  selectable?: boolean;
  togglingStatusIds?: Array<number | string>;
}>();

const emit = defineEmits<{
  (e: 'update:search', value: string): void;
  (e: 'update:page', value: number): void;
  (e: 'update:per-page', value: number): void;
  (e: 'update:sort', value: { sort: string; direction: SortDirection }): void;
  (e: 'selection-change', ids: Array<number | string>): void;
  (e: 'view', id: number | string): void;
  (e: 'edit', id: number | string): void;
  (e: 'delete', id: number | string): void;
  (e: 'delete-selected', ids: Array<number | string>): void;
  (e: 'impersonate', id: number | string): void;
  (e: 'owner-resend-invite', id: number | string): void;
  (e: 'owner-reset-email', id: number | string): void;
  (e: 'owner-password-reset', id: number | string): void;
  (e: 'archive', id: number | string): void;
  (e: 'unarchive', id: number | string): void;
  (e: 'restore', id: number | string): void;
  (e: 'archive-selected', ids: Array<number | string>): void;
  (e: 'toggle-status', payload: { id: number | string; active: boolean }): void;
}>();

const { t } = useI18n();
const localSearch = ref(props.search);
const selectedIds = ref<Array<number | string>>([]);

const rows = computed(() => props.rows);
const total = computed(() => props.total);
const loading = computed(() => props.loading ?? false);
const currentPage = computed(() => props.page);
const currentPerPage = computed(() => props.perPage);

const tableKey = computed(
  () => `${props.page}-${props.perPage}-${props.sort}-${props.direction}-${props.search}`,
);

const columns = computed(() => [
  { label: t('tenants.table.columns.name'), field: 'name', sortable: true },
  { label: t('tenants.table.columns.phone'), field: 'phone', sortable: false },
  { label: t('tenants.table.columns.address'), field: 'address', sortable: false },
  { label: t('tenants.table.columns.features'), field: 'feature_count', sortable: false },
  { label: t('tenants.table.columns.status'), field: 'status', sortable: false },
  { label: t('actions.actions'), field: 'actions', sortable: false },
]);

const perPageOptions = [10, 25, 50, 100];
const pageRange = 5;

const perPageSelectOptions = computed(() =>
  perPageOptions.map((option) => ({ value: option, label: String(option) })),
);

const paginationOptions = computed(() => ({
  enabled: true,
  mode: 'records',
  perPage: props.perPage,
  nextLabel: '›',
  prevLabel: '‹',
  rowsPerPageLabel: t('tenants.table.perPage'),
  ofLabel: '/',
  pageLabel: t('tenants.table.page'),
  totalLabel: t('tenants.table.total'),
  perPageDropdown: perPageOptions,
  perPageDropdownEnabled: false,
  dropdownAllowAll: false,
  setCurrentPage: props.page,
}));

const sortOptions = computed(() => ({
  enabled: true,
  initialSortBy: { field: props.sort, type: props.direction },
}));

const selectOptions = computed(() => {
  if ((props.selectable === false) || !canDelete.value) {
    return { enabled: false };
  }
  return {
    enabled: true,
    selectOnCheckboxOnly: true,
    selectionInfoClass: 'table-selected-bar',
    selectionText: t('tenants.table.rowsSelected'),
    clearSelectionText: t('actions.clear'),
  };
});

const searchQuery = computed(() => props.search);

const selectedRows = computed(() => {
  const idSet = new Set(selectedIds.value.map((value) => String(value)));
  return rows.value.filter((row) => idSet.has(String(row.id)));
});

const archivableSelectedIds = computed(() =>
  selectedRows.value
    .filter((row) => {
      const status = statusForRow(row);
      return status === 'active' || status === 'inactive';
    })
    .map((row) => row.id),
);

const togglingStatusSet = computed(
  () =>
    new Set(
      (props.togglingStatusIds ?? []).map((value) => String(value)),
    ),
);

const canView = computed(() => can('tenants.view'));
const canManage = computed(() => can('tenants.manage'));
const canToggle = computed(() => can('tenants.update') || canManage.value);
const canDelete = computed(() => can('tenants.delete') || can('tenants.manage'));

function getInitials(name: string) {
  return (name || '')
    .split(' ')
    .filter(Boolean)
    .map((n) => n[0] ?? '')
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

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

function onPerPageChange({
  currentPerPage,
  currentPage,
}: {
  currentPerPage: number;
  currentPage?: number;
}) {
  emit('update:per-page', currentPerPage);
  if (typeof currentPage === 'number') {
    emit('update:page', currentPage);
  }
}

interface PagerSlotHandlers {
  pageChanged?: (payload: { currentPage: number }) => void;
  perPageChanged?: (payload: { currentPerPage: number }) => void;
}

function onPerPageSelect(value: number, pagerProps: PagerSlotHandlers) {
  if (!Number.isFinite(value) || value <= 0 || value === props.perPage) {
    return;
  }
  const firstPage = 1;
  if (pagerProps.pageChanged) {
    pagerProps.pageChanged({ currentPage: firstPage });
  } else {
    emit('update:page', firstPage);
  }
  pagerProps.perPageChanged?.({ currentPerPage: value });
}

function onSortChange(params: Array<{ field: string; type: SortDirection }>) {
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

function statusForRow(row: TenantRow): TenantStatus {
  if (row.deleted_at) {
    return 'trashed';
  }
  if (row.archived_at) {
    return 'archived';
  }
  if (row.status === 'inactive') {
    return 'inactive';
  }
  return 'active';
}

function statusBadge(status: TenantStatus) {
  switch (status) {
    case 'inactive':
      return {
        label: t('tenants.status.inactive'),
        class: 'bg-slate-200 text-slate-600 dark:bg-slate-700/40 dark:text-slate-200',
      };
    case 'archived':
      return {
        label: t('tenants.status.archived'),
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300',
      };
    case 'trashed':
      return {
        label: t('tenants.status.trashed'),
        class: 'bg-danger-100 text-danger-600 dark:bg-danger-400/10 dark:text-danger-300',
      };
    default:
      return {
        label: t('tenants.status.active'),
        class: 'bg-success-100 text-success-600 dark:bg-success-400/10 dark:text-success-300',
      };
  }
}

function featureCountValue(row: TenantRow): number | null {
  if (typeof row.feature_count === 'number') return row.feature_count;
  if (typeof row.features_count === 'number') return row.features_count;
  if (Array.isArray(row.features)) return row.features.length;
  return null;
}

function formatFeatureCount(row: TenantRow) {
  const count = featureCountValue(row);
  return typeof count === 'number' ? count : '—';
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
