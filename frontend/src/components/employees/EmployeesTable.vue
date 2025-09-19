<template>
  <Card>
    <div class="md:flex justify-between pb-6 md:space-y-0 space-y-3 items-center">
      <Breadcrumbs v-if="!$route.meta.hide" />
      <div class="flex items-center gap-2">
        <InputGroup
          v-model="searchTerm"
          :placeholder="t('employees.form.search')"
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
        <div v-if="rowProps.column.field === 'name'" class="flex items-center gap-2">
          <div
            class="w-8 h-8 rounded-full bg-slate-200 text-xs font-medium text-slate-600 flex items-center justify-center overflow-hidden"
          >
            <img
              v-if="rowProps.row.avatar"
              :src="rowProps.row.avatar"
              alt="avatar"
              class="w-full h-full object-cover"
            />
            <span v-else>{{ getInitials(rowProps.row.name) }}</span>
          </div>
          <div class="flex flex-col leading-tight">
            <span class="text-sm font-medium">{{ rowProps.row.name }}</span>
            <span class="text-xs text-gray-500">{{ rowProps.row.email }}</span>
          </div>
        </div>
        <span v-else-if="rowProps.column.field === 'tenant'">
          {{ rowProps.row.tenant?.name || '—' }}
        </span>
        <span v-else-if="rowProps.column.field === 'roles'">
          {{ rowProps.row.roles || '—' }}
        </span>
        <span v-else-if="rowProps.column.field === 'status'">
          <Switch
            :model-value="rowProps.row.status === 'active'"
            @update:modelValue="(val) => toggleStatus(rowProps.row, val)"
          />
        </span>
        <span v-else-if="rowProps.column.field === 'last_login_at'">
          {{ formatDate(rowProps.row.last_login_at) }}
        </span>
        <span v-else-if="rowProps.column.field === 'actions'">
          <Dropdown classMenuItems=" w-[180px]">
            <span class="text-xl"><Icon icon="heroicons-outline:dots-vertical" /></span>
            <template #menus>
              <MenuItem v-if="can('employees.manage')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('impersonate', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:user" /></span>
                  <span>{{ t('actions.impersonate') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('employees.manage')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('resend-invite', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:envelope" /></span>
                  <span>{{ t('actions.resendInvite') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('employees.manage')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('reset-email', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:at-symbol" /></span>
                  <span>{{ t('actions.resetEmail') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('employees.manage')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('send-password-reset', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:key" /></span>
                  <span>{{ t('actions.sendPasswordReset') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('employees.update') || can('employees.manage')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('edit', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:pencil-square" /></span>
                  <span>{{ t('actions.edit') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('employees.delete') || can('employees.manage')">
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
          v-if="can('employees.delete') || can('employees.manage')"
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
import Switch from '@/components/ui/Switch/index.vue';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';

interface EmployeeRow {
  id: number;
  name: string;
  email: string;
  roles: string;
  department?: string | null;
  phone?: string | null;
  status?: string | null;
  last_login_at?: string | null;
  tenant?: { id: number; name: string } | null;
  tenant_id?: number | null;
  avatar?: string | null;
}

const props = defineProps<{ rows: EmployeeRow[] }>();
const emit = defineEmits<{
  (e: 'edit', id: number): void;
  (e: 'delete', id: number): void;
  (e: 'delete-selected', ids: number[]): void;
  (e: 'impersonate', id: number): void;
  (e: 'resend-invite', id: number): void;
  (e: 'reset-email', id: number): void;
  (e: 'send-password-reset', id: number): void;
}>();

const { t } = useI18n();
const notify = useNotify();
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
  { label: 'Department', field: 'department' },
  { label: 'Roles', field: 'roles' },
  { label: 'Phone', field: 'phone' },
  { label: 'Status', field: 'status' },
  { label: 'Last Login', field: 'last_login_at' },
  { label: 'Tenant', field: 'tenant' },
  { label: 'Actions', field: 'actions' },
];

const selectedIds = ref<number[]>([]);

const filteredRows = computed(() => {
  const term = searchTerm.value.toLowerCase();
  if (!term) return props.rows;
  return props.rows.filter((r) => {
    return (
      String(r.name).toLowerCase().includes(term) ||
      String(r.email).toLowerCase().includes(term) ||
      String(r.roles).toLowerCase().includes(term) ||
      String(r.department || '').toLowerCase().includes(term) ||
      String(r.phone || '').toLowerCase().includes(term) ||
      String(r.status || '').toLowerCase().includes(term) ||
      String(r.tenant?.name || '').toLowerCase().includes(term)
    );
  });
});

function onSelectedRowsChange(params: any) {
  selectedIds.value = params.selectedRows.map((r: any) => r.id);
}

async function toggleStatus(row: EmployeeRow, val: boolean) {
  const previous = row.status;
  row.status = val ? 'active' : 'inactive';
  try {
    const { data } = await api.patch(`/employees/${row.id}/toggle-status`);
    row.status = data.data?.status ?? row.status;
  } catch (e) {
    row.status = previous;
    notify.error(t('common.error'));
  }
}

function formatDate(d?: string) {
  return d ? new Date(d).toLocaleString() : '';
}

function getInitials(name: string) {
  return name
    .split(' ')
    .filter(Boolean)
    .map((n) => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}
</script>
