import { ref, watch } from 'vue';

export default function useServerTable(fetcher) {
  const page = ref(1);
  const perPage = ref(10);
  const sort = ref(null);
  const search = ref('');
  const rows = ref([]);
  const total = ref(0);
  const loading = ref(false);

  const load = async () => {
    loading.value = true;
    try {
      const { rows: r, total: t } = await fetcher({
        page: page.value,
        perPage: perPage.value,
        sort: sort.value,
        search: search.value,
      });
      rows.value = r;
      total.value = t;
    } finally {
      loading.value = false;
    }
  };

  watch([page, perPage, sort, search], load, { immediate: true });

  return { page, perPage, sort, search, rows, total, loading, load };
}
