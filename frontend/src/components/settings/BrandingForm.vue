<template>
  <form class="space-y-4" @submit.prevent="save">
    <span class="form-label">Name</span>
    <Textinput v-model="form.name" aria-label="Name" />
    <div>
      <span class="form-label">Logo</span>
      <div
        v-bind="getLogoRootProps()"
        class="border-dashed border-2 rounded-md p-6 text-center cursor-pointer"
      >
        <input id="branding-logo" v-bind="getLogoInputProps()" class="hidden" aria-label="Logo" />
        <p v-if="!logoFile && !form.logo">Drop logo here or click to upload</p>
        <p v-else-if="logoFile">{{ logoFile.name }}</p>
        <img v-else :src="form.logo" alt="" class="mx-auto h-24" />
      </div>
    </div>
    <div>
      <span class="form-label">Logo (Dark)</span>
      <div
        v-bind="getLogoDarkRootProps()"
        class="border-dashed border-2 rounded-md p-6 text-center cursor-pointer"
      >
        <input
          id="branding-logo-dark"
          v-bind="getLogoDarkInputProps()"
          class="hidden"
          aria-label="Logo (Dark)"
        />
        <p v-if="!logoDarkFile && !form.logo_dark">Drop logo here or click to upload</p>
        <p v-else-if="logoDarkFile">{{ logoDarkFile.name }}</p>
        <img v-else :src="form.logo_dark" alt="" class="mx-auto h-24" />
      </div>
    </div>
    <div>
      <span class="form-label">Primary Color</span>
      <input
        id="branding-color"
        v-model="form.color"
        type="color"
        class="h-10 w-20 rounded border border-slate-200"
        aria-label="Primary Color"
      />
    </div>
    <div>
      <span class="form-label">Secondary Color</span>
      <input
        id="branding-secondary-color"
        v-model="form.secondary_color"
        type="color"
        class="h-10 w-20 rounded border border-slate-200"
        aria-label="Secondary Color"
      />
    </div>
    <div>
      <span class="form-label">Primary Color (Dark)</span>
      <input
        id="branding-color-dark"
        v-model="form.color_dark"
        type="color"
        class="h-10 w-20 rounded border border-slate-200"
        aria-label="Primary Color (Dark)"
      />
    </div>
    <div>
      <span class="form-label">Secondary Color (Dark)</span>
      <input
        id="branding-secondary-color-dark"
        v-model="form.secondary_color_dark"
        type="color"
        class="h-10 w-20 rounded border border-slate-200"
        aria-label="Secondary Color (Dark)"
      />
    </div>
    <span class="form-label">Email From</span>
    <Textinput v-model="form.email_from" type="email" aria-label="Email From" />
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
import { useNotify } from '@/plugins/notify';

const store = useBrandingStore();
const notify = useNotify();
const initial = { ...store.branding } as Record<string, any>;
const form = reactive({ ...initial });
const logoFile = ref<File | null>(null);
const logoDarkFile = ref<File | null>(null);
function onDrop(files: File[]) {
  logoFile.value = files[0] || null;
}
function onDropDark(files: File[]) {
  logoDarkFile.value = files[0] || null;
}

const { getRootProps: getLogoRootProps, getInputProps: getLogoInputProps } =
  useDropzone({ onDrop, multiple: false });
const {
  getRootProps: getLogoDarkRootProps,
  getInputProps: getLogoDarkInputProps,
} = useDropzone({ onDrop: onDropDark, multiple: false });

const dirty = computed(
  () =>
    JSON.stringify(form) !== JSON.stringify(initial) ||
    !!logoFile.value ||
    !!logoDarkFile.value,
);

async function save() {
  if (!dirty.value) return;
  let payload: any = { ...form };
  if (logoFile.value || logoDarkFile.value) {
    payload = new FormData();
    Object.entries(form).forEach(([k, v]) => payload.append(k, v as any));
    if (logoFile.value) payload.append('logo', logoFile.value);
    if (logoDarkFile.value) payload.append('logo_dark', logoDarkFile.value);
  }
  await store.update(payload);
  Object.assign(initial, store.branding);
  Object.assign(form, store.branding);
  logoFile.value = null;
  logoDarkFile.value = null;
  notify.success('Branding saved');
}
</script>
