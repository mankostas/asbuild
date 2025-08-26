<template>
  <VueGoodTable
    :columns="tableColumns"
    :rows="rows"
    :pagination-options="{ enabled: true, perPage: perPage, total: total }"
    :search-options="{ enabled: true, externalQuery: search }"
    :sort-options="{ enabled: true }"
    :keyField="rowKey"
    :styleClass="'table w-full'"
    @on-page-change="onPageChange"
    @on-sort-change="onSortChange"
    @on-search="onSearch"
  >
    <template v-if="slots.actions" #table-row="props">
      <span v-if="props.column.field === '__actions'">
        <slot name="actions" v-bind="props" />
      </span>
      <span v-else>
        {{ props.formattedRow[props.column.field] }}
      </span>
    </template>
  </VueGoodTable>
</template>

<script setup>
import { computed, useSlots } from 'vue';
import { VueGoodTable } from 'vue-good-table-next';
import useServerTable from '@/composables/useServerTable';

const props = defineProps({
  columns: { type: Array, required: true },
  fetcher: { type: Function, required: true },
  rowKey: { type: String, default: 'id' }
});

const slots = useSlots();

const { rows, total, page, perPage, sort, search } = useServerTable(props.fetcher);

const tableColumns = computed(() => {
  if (slots.actions) {
    return [...props.columns, { label: 'Actions', field: '__actions' }];
  }
  return props.columns;
});

function onPageChange({ currentPage, currentPerPage }) {
  page.value = currentPage;
  perPage.value = currentPerPage;
}

function onSortChange(params) {
  const first = params[0];
  sort.value = first ? { field: first.field, type: first.type } : null;
}

function onSearch(value) {
  search.value = value;
}
</script>
