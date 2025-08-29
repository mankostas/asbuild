<template>
  <form class="space-y-4" @submit.prevent="save">
    <Textinput v-model="form.left" label="Left Footer Text" />
    <Textinput v-model="form.right" label="Right Footer Text" />
    <Button type="submit" :isDisabled="!dirty" btnClass="btn-dark">Save Footer</Button>
  </form>
</template>

<script setup lang="ts">
import { reactive, onMounted, computed } from 'vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';
import { useFooterStore } from '@/stores/footer';
import { useNotify } from '@/plugins/notify';

const store = useFooterStore();
const notify = useNotify();

const form = reactive({ left: '', right: '' });
const initial = reactive({ left: '', right: '' });

onMounted(async () => {
  await store.load();
  form.left = initial.left = store.left;
  form.right = initial.right = store.right;
});

const dirty = computed(() => form.left !== initial.left || form.right !== initial.right);

async function save() {
  if (!dirty.value) return;
  await store.update(form.left, form.right);
  initial.left = form.left = store.left;
  initial.right = form.right = store.right;
  notify.success('Footer saved');
}
</script>
