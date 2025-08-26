<template>
  <div class="bg-white dark:bg-slate-800 rounded-md shadow-base w-full">
    <div
      v-if="$slots.search || $slots.filters"
      class="flex flex-col md:flex-row md:items-center md:justify-between p-4 gap-4"
    >
      <div class="flex-1">
        <slot name="search" :search="search" />
      </div>
      <div class="flex-1 md:text-right">
        <slot name="filters" />
      </div>
    </div>
    <div v-if="loading || !rows.length" class="p-4">
      <SkeletonTable :count="perPage" />
    </div>
    <div v-else>
      <div class="overflow-x-auto">
        <table
          class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700"
        >
          <thead
            class="bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300"
          >
            <tr>
              <th
                v-for="col in tableColumns"
                :key="col.field"
                class="table-th cursor-pointer select-none"
                @click="col.sortable !== false ? toggleSort(col.field) : null"
              >
                <div class="flex items-center">
                  <span>{{ col.label }}</span>
                  <span v-if="col.sortable !== false" class="ml-1">
                    <Icon
                      v-if="sort && sort.field === col.field && sort.type === 'asc'"
                      icon="heroicons-outline:chevron-up"
                      class="w-4 h-4"
                    />
                    <Icon
                      v-else-if="sort && sort.field === col.field && sort.type === 'desc'"
                      icon="heroicons-outline:chevron-down"
                      class="w-4 h-4"
                    />
                    <Icon
                      v-else
                      icon="heroicons-outline:chevron-up-down"
                      class="w-4 h-4 opacity-50"
                    />
                  </span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody
            class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700"
          >
            <tr
              v-for="row in rows"
              :key="row[rowKey]"
              class="hover:bg-slate-50 dark:hover:bg-slate-700"
            >
              <td
                v-for="col in columns"
                :key="col.field"
                class="table-td"
              >
                <span v-if="col.html" v-html="row[col.field]" />
                <span v-else>{{ row[col.field] }}</span>
              </td>
              <td v-if="hasActions" class="table-td">
                <slot name="actions" :row="row" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="p-4">
        <Pagination
          :total="total"
          :current="page"
          :perPage="perPage"
          :pageChanged="(p) => (page.value = p)"
          :perPageChanged="(pp) => (perPage.value = pp)"
          :options="perPageOptions"
          enableSelect
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, useSlots } from 'vue';
import Icon from '@/components/ui/Icon';
import Pagination from '@/components/ui/Pagination';
import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
import useServerTable from '@/composables/useServerTable';

interface Column {
  label: string;
  field: string;
  sortable?: boolean;
  html?: boolean;
}

const props = defineProps({
  columns: { type: Array as () => Column[], required: true },
  fetcher: { type: Function as unknown as () => any, required: true },
  rowKey: { type: String, default: 'id' },
  perPageOptions: {
    type: Array as () => { value: number; label: string }[],
    default: () => [
      { value: 10, label: '10' },
      { value: 25, label: '25' },
      { value: 50, label: '50' },
    ],
  },
});

const slots = useSlots();

const { rows, total, page, perPage, sort, search, loading } = useServerTable(
  props.fetcher,
);

const tableColumns = computed(() => {
  if (slots.actions) {
    return [...props.columns, { label: 'Actions', field: '__actions' }];
  }
  return props.columns;
});

const hasActions = computed(() => Boolean(slots.actions));

function toggleSort(field: string) {
  if (!field) return;
  if (!sort.value || sort.value.field !== field) {
    sort.value = { field, type: 'asc' } as any;
  } else if (sort.value.type === 'asc') {
    sort.value = { field, type: 'desc' } as any;
  } else {
    sort.value = null;
  }
}
</script>

<style scoped>
.table-th {
  @apply md:px-5 px-3 py-3 text-left text-sm font-medium text-slate-700 dark:text-slate-300;
}
.table-td {
  @apply md:px-5 px-3 py-3 text-sm text-slate-600 dark:text-slate-300;
}
</style>

