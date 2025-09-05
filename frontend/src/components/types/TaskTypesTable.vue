<template>
  <Card>
    <div class="md:flex justify-between pb-6 md:space-y-0 space-y-3 items-center">
      <h5>Task Types</h5>
      <div class="flex items-center gap-2">
        <InputGroup
          v-model="searchTerm"
          placeholder="Search"
          type="text"
          prependIcon="heroicons-outline:search"
          merged
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
      >
        <template #table-row="rowProps">
          <span v-if="rowProps.column.field === 'tenant'">
            {{ rowProps.row.tenant?.name || '—' }}
          </span>
          <span v-else-if="rowProps.column.field === 'published'">
            {{ rowProps.row.current_version?.published_at ? 'Yes' : 'No' }}
          </span>
          <span v-else-if="rowProps.column.field === 'actions'">
            <Dropdown classMenuItems=" w-[140px]">
              <span class="text-xl"><Icon icon="heroicons-outline:dots-vertical" /></span>
              <template #menus>
                <MenuItem v-if="!rowProps.row.current_version?.published_at">
                  <button
                    type="button"
                    class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                    @click="$emit('publish', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:check" /></span>
                    <span>Publish</span>
                  </button>
                </MenuItem>
                <MenuItem v-else>
                  <button
                    type="button"
                    class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                    @click="$emit('deprecate', rowProps.row.id)"
                  >
                    <span class="text-base"><Icon icon="heroicons-outline:x-mark" /></span>
                    <span>Unpublish</span>
                  </button>
                </MenuItem>
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
  tenant?: { id: number; name: string } | null;
  current_version?: { id: number; published_at?: string | null } | null;
}

const props = defineProps<{ rows: TaskType[] }>();
const emit = defineEmits<{
  (e: 'edit', id: number): void;
  (e: 'delete', id: number): void;
  (e: 'copy', id: number): void;
  (e: 'publish', id: number): void;
  (e: 'deprecate', id: number): void;
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
  { label: 'Tenant', field: 'tenant' },
  { label: 'Published', field: 'published' },
  { label: 'Actions', field: 'actions' },
];

const filteredRows = computed(() => {
  if (!searchTerm.value) return props.rows;
  return props.rows.filter((r) =>
    String(r.name).toLowerCase().includes(searchTerm.value.toLowerCase()),
  );
});
</script>
