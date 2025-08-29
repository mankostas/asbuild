<template>
    <div>
      <div class="flex gap-2 mb-4 items-center">
      <span id="manual-search-label" class="text-sm">Search</span>
      <input
        v-model="search"
        placeholder="Search"
        class="border p-2 flex-1"
        aria-labelledby="manual-search-label"
        @input="onSearch"
      />
      <span id="manual-category-label" class="text-sm">Category</span>
      <select
        v-model="category"
        class="border p-2"
        aria-labelledby="manual-category-label"
      >
        <option value="">All</option>
        <option v-for="c in categories" :key="c">{{ c }}</option>
      </select>
      <button class="border px-2" @click="showFavorites = !showFavorites">
        {{ showFavorites ? 'All' : 'Favorites' }}
      </button>
    </div>
    <button
      v-if="isAdmin"
      class="bg-blue-600 text-white px-4 py-2 mb-4"
      @click="create"
    >
      Upload Manual
    </button>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="m in filteredManuals" :key="m.id" class="relative">
        <ManualCard
          :manual="m"
          :favorite="store.favorites.includes(m.id)"
          :offline="store.offline.includes(m.id)"
          @open="open"
          @toggle-favorite="store.toggleFavorite"
          @offline="toggleOffline"
        />
        <button
          v-if="isAdmin"
          class="absolute top-2 right-2 text-red-600"
          @click="remove(m.id)"
        >
          Delete
        </button>
      </div>
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
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import Swal from 'sweetalert2';

const store = useManualsStore();
const router = useRouter();
const search = ref('');
const category = ref('');
const showFavorites = ref(false);
const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.roles?.some((r: any) => r.name === 'ClientAdmin'));

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

async function create() {
  const name = prompt('Manual name');
  if (!name) return;
  await api.post('/manuals', { name });
  await store.fetch();
}

async function remove(id: number) {
  const res = await Swal.fire({
    title: 'Delete manual?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    await api.delete(`/manuals/${id}`);
    await store.fetch();
  }
}
</script>
