<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Manuals</h2>
    <div class="flex gap-2 mb-4">
      <input
        v-model="search"
        @input="onSearch"
        placeholder="Search"
        class="border p-2 flex-1"
      />
      <select v-model="category" class="border p-2">
        <option value="">All</option>
        <option v-for="c in categories" :key="c">{{ c }}</option>
      </select>
      <button class="border px-2" @click="showFavorites = !showFavorites">
        {{ showFavorites ? 'All' : 'Favorites' }}
      </button>
    </div>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
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
    <div v-if="store.recents.length" class="mt-8">
      <h3 class="font-bold mb-2">Recent</h3>
      <ul>
        <li v-for="id in store.recents" :key="id">
          <router-link :to="`/manuals/${id}`">{{ getManualName(id) }}</router-link>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useManualsStore } from '@/stores/manuals';
import ManualCard from '@/components/manuals/ManualCard.vue';

const store = useManualsStore();
const router = useRouter();
const search = ref('');
const category = ref('');
const showFavorites = ref(false);

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

function getManualName(id: number) {
  const m = store.manuals.find((m) => m.id === id);
  return m ? m.file?.filename : `#${id}`;
}
</script>
