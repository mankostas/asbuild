<template>
  <div v-if="tenant" class="max-w-md">
    <div class="grid gap-2">
      <div><strong>ID:</strong> {{ tenant.id }}</div>
      <div><strong>Name:</strong> {{ tenant.name }}</div>
      <div><strong>Phone:</strong> {{ tenant.phone }}</div>
      <div><strong>Address:</strong> {{ tenant.address }}</div>
    </div>
    <div class="mt-4 flex gap-2">
      <Button btnClass="btn-primary btn-sm" text="Edit" :link="`/tenants/${tenant.id}/edit`" />
      <Button btnClass="btn-secondary btn-sm" text="Back" link="/tenants" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/services/api';
import Button from '@/components/ui/Button/index.vue';

const route = useRoute();
const tenant = ref<any>(null);

onMounted(async () => {
  const { data } = await api.get(`/tenants/${route.params.id}`);
  tenant.value = data;
});
</script>
