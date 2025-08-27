<template>
  <form @submit.prevent="save" class="space-y-4">
    <Textinput label="Footer Text" v-model="form.text" />
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

const form = reactive({ text: '' });
const initial = reactive({ text: '' });

onMounted(async () => {
  await store.load();
  form.text = initial.text = store.text;
});

const dirty = computed(() => form.text !== initial.text);

async function save() {
  if (!dirty.value) return;
  await store.update(form.text);
  initial.text = form.text = store.text;
  notify.success('Footer saved');
}
</script>
