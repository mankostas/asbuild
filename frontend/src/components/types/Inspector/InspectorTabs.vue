<template>
  <div>
    <div v-if="selected" class="inspector">
        <nav class="mb-2 flex gap-2">
          <button
            v-for="tab in tabs"
            :key="tab"
            :class="['px-2 py-1 text-sm rounded', active===tab ? 'bg-indigo-600 text-white':'bg-gray-100']"
            :aria-current="active===tab ? 'page' : undefined"
            @click="active = tab"
          >
            {{ tab }}
          </button>
        </nav>
        <div v-if="active === 'Basics'" class="space-y-2">
          <label class="block text-sm" for="fieldLabel">
            <span class="block mb-1">{{ t('Label') }}</span>
            <input
              id="fieldLabel"
              v-model="label"
              class="w-full border rounded px-2 py-1"
              aria-label="Field label"
            />
          </label>
          <label class="block text-sm" for="fieldRequired">
            <span class="block mb-1">{{ t('Required') }}</span>
            <input
              id="fieldRequired"
              v-model="required"
              type="checkbox"
              aria-label="Required"
            />
          </label>
        </div>
      <div v-else-if="active === 'Validation'" class="space-y-2">
        <label class="block text-sm" for="valRegex">
          <span class="block mb-1">{{ t('validation.regex') }}</span>
          <input
            id="valRegex"
            v-model="validations.regex"
            class="w-full border rounded px-2 py-1"
            aria-label="Regex pattern"
          />
        </label>
        <label class="block text-sm" for="valMin">
          <span class="block mb-1">{{ t('validation.min') }}</span>
          <input
            id="valMin"
            type="number"
            v-model.number="validations.min"
            class="w-full border rounded px-2 py-1"
            aria-label="Minimum"
          />
        </label>
        <label class="block text-sm" for="valMax">
          <span class="block mb-1">{{ t('validation.max') }}</span>
          <input
            id="valMax"
            type="number"
            v-model.number="validations.max"
            class="w-full border rounded px-2 py-1"
            aria-label="Maximum"
          />
        </label>
        <label class="block text-sm" for="valLenMin">
          <span class="block mb-1">{{ t('validation.lengthMin') }}</span>
          <input
            id="valLenMin"
            type="number"
            v-model.number="validations.lengthMin"
            class="w-full border rounded px-2 py-1"
            aria-label="Min length"
          />
        </label>
        <label class="block text-sm" for="valLenMax">
          <span class="block mb-1">{{ t('validation.lengthMax') }}</span>
          <input
            id="valLenMax"
            type="number"
            v-model.number="validations.lengthMax"
            class="w-full border rounded px-2 py-1"
            aria-label="Max length"
          />
        </label>
        <label class="block text-sm" for="valMime">
          <span class="block mb-1">{{ t('validation.mime') }}</span>
          <input
            id="valMime"
            v-model="mimeString"
            class="w-full border rounded px-2 py-1"
            aria-label="MIME types"
          />
        </label>
        <label class="block text-sm" for="valSize">
          <span class="block mb-1">{{ t('validation.size') }}</span>
          <input
            id="valSize"
            type="number"
            v-model.number="validations.size"
            class="w-full border rounded px-2 py-1"
            aria-label="Max size"
          />
        </label>
        <label class="inline-flex items-center gap-1" for="valUnique">
          <input id="valUnique" type="checkbox" v-model="validations.unique" aria-label="Unique" />
          <span>{{ t('validation.unique') }}</span>
        </label>
      </div>
      <div v-else-if="active === 'i18n'" class="space-y-2">
        <label class="block text-sm" for="i18nLabelEl">
          <span class="block mb-1">{{ t('Label') }} EL</span>
          <input
            id="i18nLabelEl"
            v-model="selected!.label.el"
            class="w-full border rounded px-2 py-1"
            aria-label="Label Greek"
          />
        </label>
        <label class="block text-sm" for="i18nLabelEn">
          <span class="block mb-1">{{ t('Label') }} EN</span>
          <input
            id="i18nLabelEn"
            v-model="selected!.label.en"
            class="w-full border rounded px-2 py-1"
            aria-label="Label English"
          />
        </label>
        <label class="block text-sm" for="i18nPhEl">
          <span class="block mb-1">{{ t('fields.placeholder') }} EL</span>
          <input
            id="i18nPhEl"
            v-model="selected!.placeholder.el"
            class="w-full border rounded px-2 py-1"
            aria-label="Placeholder Greek"
          />
        </label>
        <label class="block text-sm" for="i18nPhEn">
          <span class="block mb-1">{{ t('fields.placeholder') }} EN</span>
          <input
            id="i18nPhEn"
            v-model="selected!.placeholder.en"
            class="w-full border rounded px-2 py-1"
            aria-label="Placeholder English"
          />
        </label>
        <label class="block text-sm" for="i18nHelpEl">
          <span class="block mb-1">{{ t('fields.help') }} EL</span>
          <input
            id="i18nHelpEl"
            v-model="selected!.help.el"
            class="w-full border rounded px-2 py-1"
            aria-label="Help Greek"
          />
        </label>
        <label class="block text-sm" for="i18nHelpEn">
          <span class="block mb-1">{{ t('fields.help') }} EN</span>
          <input
            id="i18nHelpEn"
            v-model="selected!.help.en"
            class="w-full border rounded px-2 py-1"
            aria-label="Help English"
          />
        </label>
      </div>
      <div v-else class="text-sm text-gray-500" tabindex="0">{{ t('Not implemented') }}</div>
    </div>
    <div v-else class="text-sm text-gray-500">{{ t('Select a field') }}</div>
  </div>
</template>

<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ selected: any | null }>();
const { t, locale } = useI18n();
const tabs = ['Basics', 'Validation', 'Logic', 'Roles', 'i18n', 'Data'];
const active = ref('Basics');
const label = computed({
  get: () => props.selected?.label?.[locale.value] ?? '',
  set: (val: string) => {
    if (props.selected) props.selected.label[locale.value] = val;
  },
});
watch(
  () => props.selected,
  (val) => {
    if (val) {
      val.label ||= { en: '', el: '' };
      val.placeholder ||= { en: '', el: '' };
      val.help ||= { en: '', el: '' };
    }
  },
  { immediate: true },
);
const validations = computed(() => {
  if (!props.selected) return {} as any;
  if (!props.selected.validations) props.selected.validations = {};
  return props.selected.validations;
});
const required = computed({
  get: () => validations.value.required ?? false,
  set: (val: boolean) => {
    validations.value.required = val;
  },
});
const mimeString = computed({
  get: () => (validations.value.mime ? validations.value.mime.join(',') : ''),
  set: (val: string) => {
    validations.value.mime = val ? val.split(',').map((s) => s.trim()) : [];
  },
});
</script>
