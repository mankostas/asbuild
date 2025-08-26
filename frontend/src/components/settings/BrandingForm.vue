<template>
  <form @submit.prevent="save" class="space-y-4">
    <Textinput label="Name" v-model="form.name" />
    <div>
      <label class="form-label">Logo</label>
      <div
        v-bind="getRootProps()"
        class="border-dashed border-2 rounded-md p-6 text-center cursor-pointer"
      >
        <input v-bind="getInputProps()" class="hidden" />
        <p v-if="!logoFile && !form.logo">Drop logo here or click to upload</p>
        <p v-else-if="logoFile">{{ logoFile.name }}</p>
        <img v-else :src="form.logo" class="mx-auto h-24" />
      </div>
    </div>
    <div>
      <label class="form-label">Primary Color</label>
      <input
        type="color"
        v-model="form.color"
        class="h-10 w-20 rounded border border-slate-200"
      />
    </div>
    <Textinput label="Email From" type="email" v-model="form.email_from" />
    <Button type="submit" :isDisabled="!dirty" btnClass="btn-dark"
      >Save Branding</Button
    >
  </form>
</template>

<script setup lang="ts">
import { reactive, ref, computed } from 'vue';
import { useBrandingStore } from '@/stores/branding';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';
import { useDropzone } from 'vue3-dropzone';
import { useToast } from '@/plugins/toast';

const store = useBrandingStore();
const toast = useToast();
const initial = { ...store.branding } as Record<string, any>;
const form = reactive({ ...initial });
const logoFile = ref<File | null>(null);

function onDrop(files: File[]) {
  logoFile.value = files[0] || null;
}

const { getRootProps, getInputProps } = useDropzone({ onDrop, multiple: false });

const dirty = computed(
  () => JSON.stringify(form) !== JSON.stringify(initial) || !!logoFile.value,
);

async function save() {
  if (!dirty.value) return;
  let payload: any = { ...form };
  if (logoFile.value) {
    payload = new FormData();
    Object.entries(form).forEach(([k, v]) => payload.append(k, v as any));
    payload.append('logo', logoFile.value);
  }
  await store.update(payload);
  Object.assign(initial, store.branding);
  Object.assign(form, store.branding);
  logoFile.value = null;
  toast.add({ severity: 'success', summary: 'Branding saved', detail: '' });
}
</script>
