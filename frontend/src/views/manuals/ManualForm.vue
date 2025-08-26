<template>
  <div>
    <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit Manual' : 'Upload Manual' }}</h2>
    <form class="grid gap-4 max-w-xl" @submit.prevent="onSubmit">
      <div>
        <label class="block mb-1">File<span v-if="!isEdit" class="text-red-600">*</span></label>
        <input type="file" @change="onFile" />
      </div>
      <div>
        <label class="block mb-1">Category</label>
        <input v-model="category" class="border p-2 w-full" />
      </div>
      <div>
        <label class="block mb-1">Tags (comma separated)</label>
        <input v-model="tags" class="border p-2 w-full" />
      </div>
      <div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';

const route = useRoute();
const router = useRouter();

const file = ref<File | null>(null);
const category = ref('');
const tags = ref('');

const isEdit = computed(() => route.name === 'manuals.edit');

function onFile(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    file.value = target.files[0];
  }
}

onMounted(async () => {
  if (isEdit.value) {
    const { data } = await api.get(`/manuals/${route.params.id}`);
    category.value = data.category || '';
    tags.value = (data.tags || []).join(', ');
  }
});

async function onSubmit() {
  const tagsArray = tags.value
    .split(',')
    .map((t) => t.trim())
    .filter((t) => t);

  if (isEdit.value) {
    await api.patch(`/manuals/${route.params.id}`, {
      category: category.value || null,
      tags: tagsArray,
    });
    if (file.value) {
      const form = new FormData();
      form.append('file', file.value);
      await api.post(`/manuals/${route.params.id}/replace`, form);
    }
  } else {
    if (!file.value) return;
    const form = new FormData();
    form.append('file', file.value);
    if (category.value) form.append('category', category.value);
    tagsArray.forEach((t) => form.append('tags[]', t));
    await api.post('/manuals', form);
  }

  router.push({ name: 'manuals.list' });
}
</script>

