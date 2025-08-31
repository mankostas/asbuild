<template>
  <Card bodyClass="p-4">
    <div class="flex flex-wrap items-end gap-4">
      <Textinput
        id="typeName"
        v-model="localName"
        :label="t('types.form.name')"
        class="flex-1 min-w-[150px]"
        classInput="text-sm"
      />
      <div class="flex-1 min-w-[150px] relative">
        <!-- eslint-disable-next-line vuejs-accessibility/label-has-for -->
        <label
          for="typeSearch"
          class="input-label inline-flex items-center gap-1"
        >
          {{ t('types.form.search') }}
          <Icon
            v-tippy="{
              theme: 'light',
              trigger: 'mouseenter focus click',
              content: t('types.form.searchHelp'),
            }"
            icon="heroicons-outline:question-mark-circle"
            class="w-4 h-4"
            aria-hidden="true"
          />
        </label>
        <InputGroup id="typeSearch" v-model="localSearch" classInput="text-sm">
          <template #append>
              <Button
                v-if="localSearch"
                type="button"
                btnClass="btn-light px-2 py-1"
                :aria-label="t('actions.clear')"
                @click="clearSearch"
              >
                ✕
              </Button>
          </template>
        </InputGroup>
        <ul
          v-if="tenantSearchResults.length"
          class="absolute z-10 w-full bg-white border rounded mt-1 max-h-60 overflow-auto"
        >
          <li
            v-for="opt in tenantSearchResults"
            :key="opt.value"
            role="button"
            tabindex="0"
            class="px-2 py-1 cursor-pointer hover:bg-slate-100"
            @click="selectTenant(opt)"
            @keydown.enter="selectTenant(opt)"
            @keydown.space.prevent="selectTenant(opt)"
          >
            {{ opt.label }}
          </li>
        </ul>
      </div>
      <Select
        id="tenantSelect"
        v-model="localTenantId"
        :label="t('types.form.tenant')"
        :options="tenantOptions"
        :placeholder="t('none')"
        class="flex-1 min-w-[150px]"
        classInput="text-sm"
      />
    </div>
    <div class="flex gap-2 mt-2">
      <Badge
        v-if="localSearch"
        badgeClass="bg-primary-50 text-primary-700 flex items-center gap-1"
      >
        <span>{{ t('types.form.search') }}: {{ localSearch }}</span>
        <Button
          type="button"
          btnClass="btn-light px-1 py-0"
          :aria-label="t('actions.clear')"
          @click="clearSearch"
        >
          ✕
        </Button>
      </Badge>
      <Badge
        v-if="selectedTenant"
        badgeClass="bg-primary-50 text-primary-700 flex items-center gap-1"
      >
        <span>{{ t('types.form.tenant') }}: {{ selectedTenant.label }}</span>
        <Button
          type="button"
          btnClass="btn-light px-1 py-0"
          :aria-label="t('actions.clear')"
          @click="clearTenant"
        >
          ✕
        </Button>
      </Badge>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Card from '@/components/ui/Card/index.vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import InputGroup from '@/components/ui/InputGroup/index.vue';
import Select from '@/components/ui/Select/index.vue';
import Badge from '@/components/ui/Badge/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Icon from '@/components/Icon';

interface Option {
  value: number;
  label: string;
}

const props = defineProps<{
  name: string;
  search: string;
  tenantId: number | '';
  tenantOptions: Option[];
}>();

const emit = defineEmits<{
  (e: 'update:name', value: string): void;
  (e: 'update:search', value: string): void;
  (e: 'update:tenantId', value: number | ''): void;
}>();

const { t } = useI18n();

const localName = computed({
  get: () => props.name,
  set: (v: string) => emit('update:name', v),
});

const localSearch = computed({
  get: () => props.search,
  set: (v: string) => emit('update:search', v),
});

const localTenantId = computed({
  get: () => props.tenantId,
  set: (v: any) => emit('update:tenantId', v === '' ? '' : Number(v)),
});

const selectedTenant = computed(() =>
  props.tenantOptions.find((o) => o.value === localTenantId.value)
);

const tenantSearchResults = computed(() =>
  localSearch.value.length >= 3 ? props.tenantOptions : []
);

function clearSearch() {
  emit('update:search', '');
}

function clearTenant() {
  emit('update:tenantId', '');
}

function selectTenant(opt: Option) {
  emit('update:tenantId', opt.value);
  emit('update:search', '');
}
</script>
