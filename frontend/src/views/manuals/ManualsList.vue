<template>
    <div>
      <div class="flex flex-wrap items-center gap-2 mb-4">
      <label :for="ids.search" class="text-sm">Search</label>
      <input
        :id="ids.search"
        v-model="search"
        placeholder="Search"
        type="text"
        class="border p-2 flex-1"
        @input="onSearch"
      />
      <label :for="ids.category" class="text-sm">Category</label>
      <select :id="ids.category" v-model="category" class="border p-2">
        <option value="">All</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
      <label class="flex items-center gap-1 text-sm">
        Favorites
        <input v-model="showFavorites" type="checkbox" class="form-switch" />
      </label>
      <label class="flex items-center gap-1 text-sm">
        Offline
        <input v-model="showOffline" type="checkbox" class="form-switch" />
      </label>
      <button class="btn btn-primary ml-auto" @click="openCreate">
        Upload Manual
      </button>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <ManualCard
        v-for="m in filteredManuals"
        :key="m.id"
        :manual="m"
        :favorite="store.favorites.includes(m.id)"
        :offline="store.offline.includes(m.id)"
        @open="open"
        @toggle-favorite="store.toggleFavorite"
        @offline="toggleOffline"
      />
    </div>
    <ManualForm
      v-if="showForm"
      :manual-id="editId"
      @close="closeForm"
      @saved="onSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useManualsStore } from '@/stores/manuals';
import ManualCard from '@/components/manuals/ManualCard.vue';
import ManualForm from './ManualForm.vue';

const router = useRouter();
const store = useManualsStore();

const search = ref('');
const category = ref('');
const showFavorites = ref(false);
const showOffline = ref(false);
const showForm = ref(false);
const editId = ref<string | null>(null);
const ids = { search: 'manuals-search', category: 'manuals-category' };

onMounted(async () => {
  await store.fetch();
  await store.loadOfflineList();
});

const categories = computed(() => [
  ...new Set(store.manuals.map((m) => m.category).filter(Boolean)),
]);

const filteredManuals = computed(() => {
  let arr = store.manuals;
  if (category.value) arr = arr.filter((m) => m.category === category.value);
  if (showFavorites.value)
    arr = arr.filter((m) => store.favorites.includes(m.id));
  if (showOffline.value)
    arr = arr.filter((m) => store.offline.includes(m.id));
  if (search.value) {
    const q = search.value.toLowerCase();
    arr = arr.filter((m) =>
      [m.file?.filename, m.category, ...(m.tags || [])].some((v) =>
        String(v ?? '').toLowerCase().includes(q),
      ),
    );
  }
  return arr;
});

function onSearch() {
  store.fetch(search.value);
}

function open(id: number) {
  router.push(`/manuals/${id}`);
}

async function toggleOffline(manual: any) {
  if (store.offline.includes(manual.id)) {
    await store.removeOffline(manual.id);
  } else {
    await store.keepOffline(manual);
  }
}

function openCreate() {
  editId.value = null;
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
}

async function onSaved() {
  showForm.value = false;
  await store.fetch(search.value);
}
</script>
