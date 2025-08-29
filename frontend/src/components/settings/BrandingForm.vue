<template>
  <form class="space-y-4" @submit.prevent="save">
    <Textinput v-model="form.name" :id="ids.name" label="Name" />
    <div>
      <label class="form-label" :for="ids.logo">Logo</label>
      <div
        v-bind="getLogoRootProps()"
        class="border-dashed border-2 rounded-md p-6 text-center cursor-pointer"
      >
        <input :id="ids.logo" v-bind="getLogoInputProps()" class="hidden" />
        <p v-if="!logoFile && !form.logo">Drop logo here or click to upload</p>
        <p v-else-if="logoFile">{{ logoFile.name }}</p>
        <img v-else :src="form.logo" alt="" class="mx-auto h-24" />
      </div>
    </div>
    <div>
      <label class="form-label" :for="ids.logoDark">Logo (Dark)</label>
      <div
        v-bind="getLogoDarkRootProps()"
        class="border-dashed border-2 rounded-md p-6 text-center cursor-pointer"
      >
        <input
          :id="ids.logoDark"
          v-bind="getLogoDarkInputProps()"
          class="hidden"
        />
        <p v-if="!logoDarkFile && !form.logo_dark">Drop logo here or click to upload</p>
        <p v-else-if="logoDarkFile">{{ logoDarkFile.name }}</p>
        <img v-else :src="form.logo_dark" alt="" class="mx-auto h-24" />
      </div>
    </div>
    <div>
      <label class="form-label" :for="ids.color">Primary Color</label>
      <input
        :id="ids.color"
        v-model="form.color"
        type="color"
        class="h-10 w-20 rounded border border-slate-200"
      />
    </div>
    <div>
      <label class="form-label" :for="ids.secondaryColor">Secondary Color</label>
      <input
        :id="ids.secondaryColor"
        v-model="form.secondary_color"
        type="color"
        class="h-10 w-20 rounded border border-slate-200"
      />
    </div>
    <div>
      <label class="form-label" :for="ids.colorDark">Primary Color (Dark)</label>
      <input
        :id="ids.colorDark"
        v-model="form.color_dark"
        type="color"
        class="h-10 w-20 rounded border border-slate-200"
      />
    </div>
    <div>
      <label class="form-label" :for="ids.secondaryColorDark">Secondary Color (Dark)</label>
      <input
        :id="ids.secondaryColorDark"
        v-model="form.secondary_color_dark"
        type="color"
        class="h-10 w-20 rounded border border-slate-200"
      />
    </div>
    <Textinput
      v-model="form.email_from"
      :id="ids.emailFrom"
      label="Email From"
      type="email"
    />
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
const ids = {
  name: 'branding-name',
  logo: 'branding-logo',
  logoDark: 'branding-logo-dark',
  color: 'branding-color',
  secondaryColor: 'branding-secondary-color',
  colorDark: 'branding-color-dark',
  secondaryColorDark: 'branding-secondary-color-dark',
  emailFrom: 'branding-email-from',
};

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
