<template>
  <Card noborder>
    <div class="md:flex justify-between pb-6 md:space-y-0 space-y-3 items-center">
      <h5>Task Types</h5>
      <InputGroup
        v-model="searchTerm"
        placeholder="Search"
        type="text"
        prependIcon="heroicons-outline:search"
        merged
      />
    </div>

      <vue-good-table
        :columns="columns"
        :rows="filteredRows"
        styleClass="vgt-table bordered centered"
        :pagination-options="{ enabled: true, perPage: perPage }"
        :search-options="{ enabled: true, externalQuery: searchTerm }"
        :select-options="selectOptions"
      >
        <template #table-row="rowProps">
          <span v-if="rowProps.column.field === 'tenant_id'">
            {{ rowProps.row.tenant_id || '—' }}
          </span>
          <span v-else-if="rowProps.column.field === 'actions'">
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
                    <span>Edit</span>
                  </button>
                </MenuItem>
                <MenuItem>
                  <button
                    type="button"
                    class="bg-danger-500 text-danger-500 bg-opacity-30 hover:bg-opacity-100 hover:text-white w-full px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                    @click="$emit('delete', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:trash" /></span>
                    <span>Delete</span>
                  </button>
                </MenuItem>
                <MenuItem>
                  <button
                    type="button"
                    class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                    @click="$emit('copy', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:document-duplicate" /></span>
                    <span>Copy</span>
                  </button>
                </MenuItem>
              </template>
            </Dropdown>
          </span>
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

interface TaskType {
  id: number;
  name: string;
  tenant_id?: number | null;
}

const props = defineProps<{ rows: TaskType[] }>();
const emit = defineEmits<{
  (e: 'edit', id: number): void;
  (e: 'delete', id: number): void;
  (e: 'copy', id: number): void;
}>();

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
  selectioninfoClass: 'custom-class',
  selectionText: 'rows selected',
  clearSelectionText: 'clear',
  disableSelectinfo: true,
  selectAllByGroup: true,
};

const columns = [
  { label: 'ID', field: 'id' },
  { label: 'Name', field: 'name' },
  { label: 'Tenant', field: 'tenant_id' },
  { label: 'Actions', field: 'actions' },
];

const filteredRows = computed(() => {
  if (!searchTerm.value) return props.rows;
  return props.rows.filter((r) =>
    String(r.name).toLowerCase().includes(searchTerm.value.toLowerCase()),
  );
});
</script>
