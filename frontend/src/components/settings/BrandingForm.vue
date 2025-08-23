<template>
  <form @submit.prevent="save" class="space-y-4">
    <div>
      <label class="block">Name</label>
      <input v-model="form.name" class="border p-2 w-full" />
    </div>
    <div>
      <label class="block">Logo URL</label>
      <input v-model="form.logo" class="border p-2 w-full" />
    </div>
    <div>
      <label class="block">Primary Color</label>
      <input type="color" v-model="form.color" class="border p-2 w-24" />
    </div>
    <div>
      <label class="block">Email From</label>
      <input type="email" v-model="form.email_from" class="border p-2 w-full" />
    </div>
    <button type="submit" class="bg-blue-500 text-white px-4 py-2">Save Branding</button>
  </form>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { useBrandingStore } from '@/stores/branding';

const store = useBrandingStore();
const form = reactive({ ...store.branding });

async function save() {
  await store.update(form);
}
</script>
