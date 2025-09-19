<template>
  <ClientForm v-if="isOpen" force-modal @close="close" />
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import ClientForm from '@/views/clients/ClientForm.vue';
import { useClientModalStore } from '@/stores/clientModal';

const clientModal = useClientModalStore();
const { isOpen } = storeToRefs(clientModal);

const close = () => {
  clientModal.close();
};
</script>
