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
      <div v-else-if="active === 'Logic'" class="space-y-2">
        <div v-for="(rule, rIdx) in logicRules" :key="rIdx" class="space-y-2 border p-2 rounded">
          <label class="block text-sm" :for="`logicField${rIdx}`">
            <span class="block mb-1">{{ t('Condition field') }}</span>
            <input
              :id="`logicField${rIdx}`"
              v-model="rule.if.field"
              class="w-full border rounded px-2 py-1"
              aria-label="Logic field"
            />
          </label>
          <label class="block text-sm" :for="`logicEq${rIdx}`">
            <span class="block mb-1">{{ t('Equals') }}</span>
            <input
              :id="`logicEq${rIdx}`"
              v-model="rule.if.eq"
              class="w-full border rounded px-2 py-1"
              aria-label="Logic equals"
            />
          </label>
          <div v-for="(action, aIdx) in rule.then" :key="aIdx" class="flex items-center gap-1">
            <select
              v-model="action.type"
              class="border rounded px-2 py-1 text-sm"
              aria-label="Logic action"
            >
              <option value="show">{{ t('Show') }}</option>
              <option value="require">{{ t('Require') }}</option>
            </select>
            <input
              v-model="action.target"
              class="flex-1 border rounded px-2 py-1"
              aria-label="Logic target"
            />
            <button
              type="button"
              class="text-red-600"
              aria-label="Remove action"
              @click="removeLogicAction(rule, aIdx)"
            >
              ✕
            </button>
          </div>
          <button
            type="button"
            class="text-sm px-2 py-1 border rounded"
            aria-label="Add action"
            @click="addLogicAction(rule)"
          >
            {{ t('Add action') }}
          </button>
          <button
            type="button"
            class="text-sm text-red-600"
            aria-label="Remove rule"
            @click="removeLogicRule(rIdx)"
          >
            {{ t('Remove rule') }}
          </button>
        </div>
        <button
          type="button"
          class="px-2 py-1 border rounded"
          aria-label="Add rule"
          @click="addLogicRule"
        >
          {{ t('Add rule') }}
        </button>
      </div>
      <div v-else-if="active === 'Roles'" class="space-y-2">
        <label class="block text-sm" for="rolesView">
          <span class="block mb-1">{{ t('View roles') }}</span>
          <select
            id="rolesView"
            v-model="roles.value.view"
            multiple
            class="w-full rounded border px-2 py-1"
            aria-label="View roles"
          >
            <option v-for="r in availableRoles" :key="r.id" :value="r.slug">{{ r.name }}</option>
          </select>
        </label>
        <label class="block text-sm" for="rolesEdit">
          <span class="block mb-1">{{ t('Edit roles') }}</span>
          <select
            id="rolesEdit"
            v-model="roles.value.edit"
            multiple
            class="w-full rounded border px-2 py-1"
            aria-label="Edit roles"
          >
            <option v-for="r in availableRoles" :key="r.id" :value="r.slug">{{ r.name }}</option>
          </select>
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
      <div v-else-if="active === 'Data'" class="space-y-2">
        <label class="block text-sm" for="dataDefault">
          <span class="block mb-1">{{ t('Default value') }}</span>
          <input
            id="dataDefault"
            v-model="dataObj.default"
            class="w-full border rounded px-2 py-1"
            aria-label="Default value"
          />
        </label>
        <label class="block text-sm" for="dataOptions">
          <span class="block mb-1">{{ t('Options') }}</span>
          <input
            id="dataOptions"
            v-model="optionsString"
            class="w-full border rounded px-2 py-1"
            aria-label="Options"
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

interface RoleOption {
  id: number;
  name: string;
  slug: string;
}

const props = withDefaults(
  defineProps<{ selected: any | null; roleOptions?: RoleOption[] }>(),
  { roleOptions: () => [] },
);
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
      val.logic ||= [];
      val.roles ||= { view: [], edit: [] };
      val.data ||= { default: '', enum: [] };
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

const logicRules = computed(() => {
  if (!props.selected) return [] as any[];
  return props.selected.logic;
});
function addLogicRule() {
  logicRules.value.push({ if: { field: '', eq: '' }, then: [] });
}
function removeLogicRule(idx: number) {
  logicRules.value.splice(idx, 1);
}
function addLogicAction(rule: any) {
  rule.then.push({ type: 'show', target: '' });
}
function removeLogicAction(rule: any, idx: number) {
  rule.then.splice(idx, 1);
}

const roles = computed(() => {
  if (!props.selected) return { view: [], edit: [] } as any;
  return props.selected.roles ?? { view: [], edit: [] };
});
const availableRoles = computed(() => props.roleOptions);

const dataObj = computed(() => {
  if (!props.selected) return { default: '', enum: [] } as any;
  return props.selected.data;
});
const optionsString = computed({
  get: () => (dataObj.value.enum ? dataObj.value.enum.join(',') : ''),
  set: (val: string) => {
    dataObj.value.enum = val ? val.split(',').map((s) => s.trim()) : [];
  },
});
</script>
