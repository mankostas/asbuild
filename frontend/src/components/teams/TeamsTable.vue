<template>
  <Card>
    <div class="md:flex justify-between pb-6 md:space-y-0 space-y-3 items-center">
      <Breadcrumbs v-if="!$route.meta.hide" />
      <div class="flex items-center gap-2">
        <InputGroup
          v-model="searchTerm"
          :placeholder="t('teams.form.search')"
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
        <span v-if="rowProps.column.field === 'description'">
          <Tooltip
            v-if="rowProps.row.description"
            theme="light"
            trigger="mouseenter focus"
          >
            <template #button>
              <span class="cursor-help">
                {{ truncateDescription(rowProps.row.description) }}
              </span>
            </template>
            {{ rowProps.row.description }}
          </Tooltip>
          <span v-else>—</span>
        </span>
        <span v-else-if="rowProps.column.field === 'lead'">
          <div v-if="rowProps.row.lead" class="flex items-center gap-2">
            <div
              class="w-8 h-8 rounded-full bg-slate-200 text-xs font-medium text-slate-600 flex items-center justify-center overflow-hidden"
            >
              <img
                v-if="rowProps.row.lead.avatar"
                :src="rowProps.row.lead.avatar"
                alt="avatar"
                class="w-full h-full object-cover"
              />
              <span v-else>{{ getInitials(rowProps.row.lead.name) }}</span>
            </div>
            <span>{{ rowProps.row.lead.name }}</span>
          </div>
          <span v-else>—</span>
        </span>
        <span v-else-if="rowProps.column.field === 'members'">
          <AvatarGroup :members="rowProps.row.members" :max="3" />
        </span>
        <span v-else-if="rowProps.column.field === 'tenant'">
          {{ rowProps.row.tenant?.name || '—' }}
        </span>
        <span v-else-if="rowProps.column.field === 'updated_at'">
          {{ formatDate(rowProps.row.updated_at) }}
        </span>
        <span v-else-if="rowProps.column.field === 'actions'">
          <Dropdown classMenuItems=" w-[140px]">
            <span class="text-xl"><Icon icon="heroicons-outline:dots-vertical" /></span>
            <template #menus>
              <MenuItem v-if="can('teams.update') || can('teams.manage')">
                <button
                  type="button"
                  class="hover:bg-slate-900 hover:text-white dark:hover:bg-slate-600 dark:hover:bg-opacity-50 w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm flex space-x-2 items-center rtl:space-x-reverse"
                  @click="$emit('edit', rowProps.row.id)"
                >
                  <span class="text-base"><Icon icon="heroicons-outline:pencil-square" /></span>
                  <span>{{ t('actions.edit') }}</span>
                </button>
              </MenuItem>
              <MenuItem v-if="can('teams.delete') || can('teams.manage')">
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
          v-if="can('teams.delete') || can('teams.manage')"
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
import AvatarGroup from '@/components/ui/AvatarGroup.vue';
import Tooltip from '@/components/ui/Tooltip/index.vue';
import Breadcrumbs from '@/Layout/Breadcrumbs.vue';
import { useI18n } from 'vue-i18n';
import { can } from '@/stores/auth';

interface TeamRow {
  id: string;
  name: string;
  description: string | null;
  members: { name: string; avatar?: string | null }[];
  lead?: { id: string; name: string; avatar?: string | null } | null;
  created_at: string;
  updated_at: string;
  tenant?: { id: string; name: string } | null;
  tenant_id?: string | null;
}

const props = withDefaults(
  defineProps<{ rows: TeamRow[]; isSuperAdmin?: boolean }>(),
  { isSuperAdmin: false },
);
const emit = defineEmits<{
  (e: 'edit', id: string): void;
  (e: 'delete', id: string): void;
  (e: 'delete-selected', ids: string[]): void;
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

const columns = computed(() => {
  const cols = [
    { label: 'Name', field: 'name' },
    { label: 'Description', field: 'description' },
    { label: 'Team Lead', field: 'lead' },
    { label: 'Members', field: 'members' },
  ];
  if (props.isSuperAdmin) {
    cols.push({ label: 'Tenant', field: 'tenant' });
  }
  cols.push({ label: 'Updated', field: 'updated_at' });
  cols.push({ label: 'Actions', field: 'actions' });
  return cols;
});

const selectedIds = ref<string[]>([]);

const filteredRows = computed(() => {
  const rows = !searchTerm.value
    ? props.rows
    : props.rows.filter((r) =>
        String(r.name).toLowerCase().includes(searchTerm.value.toLowerCase()),
      );
  return rows;
});

function onSelectedRowsChange(params: any) {
  selectedIds.value = params.selectedRows.map((r: any) => String(r.id));
}

function truncateDescription(desc: string) {
  const maxLength = 80;
  return desc.length > maxLength ? `${desc.slice(0, maxLength)}…` : desc;
}

function formatDate(d: string) {
  return new Date(d).toLocaleDateString();
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
