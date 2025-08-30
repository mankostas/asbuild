<template>
  <div>
    <Button :text="t('actions.chooseFile')" @click="onSelect" />
    <ProgressBar
      v-if="progress"
      :value="progress"
      role="progressbar"
      :aria-valuenow="progress"
      aria-valuemin="0"
      aria-valuemax="100"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { uploadFile } from '@/services/uploader';
import { pickFiles } from '@/services/native';
import Button from '@/components/ui/Button/index.vue';
import ProgressBar from '@/components/ui/ProgressBar/index.vue';

const progress = ref(0);
const { t } = useI18n();

async function onSelect() {
  const files = await pickFiles();
  const file = files[0];
  if (!file) return;
  uploadFile(file, { onProgress: (p: number) => (progress.value = p) }).catch(() => {});
}
</script>
