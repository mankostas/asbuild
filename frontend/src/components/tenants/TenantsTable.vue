<template>
  <Card>
    <div class="md:flex justify-between pb-6 md:space-y-0 space-y-3 items-center">
      <Breadcrumbs v-if="!$route.meta.hide" />
      <div class="flex items-center gap-2">
        <InputGroup
          v-model="searchTerm"
          :placeholder="t('tenants.form.search')"
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
        <div v-if="rowProps.column.field === 'name'" class="flex flex-col leading-tight">
          <span class="text-sm font-medium">{{ rowProps.row.name }}</span>
          <span
            v-if="rowProps.row.slug || rowProps.row.domain"
            class="text-xs text-gray-500"
          >
            {{ rowProps.row.domain || rowProps.row.slug }}
          </span>
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
        <span v-else-if="rowProps.column.field === 'actions'">
          <Dropdown classMenuItems=" w-[160px]">
            <span class="text-xl"><Icon icon="heroicons-outline:dots-vertical" /></span>
            <template #menus>
              <MenuItem v-if="can('tenants.view')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('view', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:eye" /></span>
                  <span>{{ t('actions.view') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('tenants.update')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('edit', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:pencil-square" /></span>
                  <span>{{ t('actions.edit') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('tenants.manage')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('impersonate', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:user" /></span>
                  <span>{{ t('actions.impersonate') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('tenants.delete')">
                <button
                  type="button"
                  class="bg-danger-500 text-danger-500 bg-opacity-30 hover:bg-opacity-100 hover:text-white w-full px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
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
        <button
          v-if="can('tenants.delete')"
          type="button"
          class="ml-2 text-danger-500 hover:underline cursor-pointer"
          @click="emit('delete-selected', selectedIds)"
        >
          {{ t('actions.delete') }}
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
import { can } from '@/stores/auth';

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
}

const props = defineProps<{ rows: TenantRow[] }>();
const emit = defineEmits<{
  (e: 'view', id: number | string): void;
  (e: 'edit', id: number | string): void;
  (e: 'delete', id: number | string): void;
  (e: 'delete-selected', ids: Array<number | string>): void;
  (e: 'impersonate', id: number | string): void;
}>();

const { t } = useI18n();
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
  { label: 'Name', field: 'name' },
  { label: 'Phone', field: 'phone' },
  { label: 'Address', field: 'address' },
  { label: 'Features', field: 'feature_count' },
  { label: 'Actions', field: 'actions' },
];

const selectedIds = ref<Array<number | string>>([]);

const filteredRows = computed(() => {
  const term = searchTerm.value.toLowerCase();
  if (!term) return props.rows;
  return props.rows.filter((r) => {
    const name = String(r.name || '').toLowerCase();
    const phone = String(r.phone || '').toLowerCase();
    const address = String(r.address || '').toLowerCase();
    const slug = String(r.slug || '').toLowerCase();
    const domain = String(r.domain || '').toLowerCase();
    const featureCount = featureCountValue(r);
    const featureText = Array.isArray(r.features)
      ? r.features.join(' ').toLowerCase()
      : String(featureCount ?? '').toLowerCase();

    return (
      name.includes(term) ||
      phone.includes(term) ||
      address.includes(term) ||
      slug.includes(term) ||
      domain.includes(term) ||
      featureText.includes(term)
    );
  });
});

function onSelectedRowsChange(params: any) {
  selectedIds.value = params.selectedRows.map((r: TenantRow) => r.id);
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
