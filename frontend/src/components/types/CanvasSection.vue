<template>
  <!-- eslint-disable vue/no-mutating-props -->
  <Card bodyClass="p-0">
    <header class="flex items-center justify-between bg-gray-50 px-2 py-1">
      <Button
        type="button"
        btnClass="btn-light p-1 handle cursor-move"
        aria-label="Drag section"
        aria-describedby="reorderHint reorderHintMobile"
        @keydown.enter.prevent="noop"
        @keydown.space.prevent="noop"
      >
        <Icon icon="heroicons-outline:bars-3" />
      </Button>
      <Textinput
        v-model="section.label[locale]"
        :label="t('Section label')"
        class="flex-1 mx-2"
        classInput="text-sm"
      />
      <!-- eslint-disable-next-line vuejs-accessibility/form-control-has-label -->
      <Select
        v-model="section.cols"
        :options="[
          { value: 1, label: '1' },
          { value: 2, label: '2' },
          { value: 3, label: '3' },
        ]"
        :label="'Columns'"
        class="w-20 mr-2"
        classLabel="sr-only"
        classInput="text-xs"
      />
      <Button
        type="button"
        btnClass="btn-outline-danger text-xs px-2 py-1"
        :aria-label="t('actions.delete')"
        aria-describedby="remove-section-desc"
        @click="$emit('remove')"
      >
        ✕
      </Button>
    </header>
    <span id="remove-section-desc" class="sr-only">{{ t('actions.delete') }}</span>
    <p id="fieldReorderHint" class="sr-only">{{ t('fields.reorderHint') }}</p>
    <div v-if="section.tabs && section.tabs.length" class="p-2">
      <UiTabs>
        <template #list>
          <Tab
            v-for="(tab, ti) in section.tabs"
            :key="tab.id"
            class="px-3 py-1 text-sm"
          >
            <span>{{ resolveI18n(tab.label) }}</span>
            <button
              type="button"
              class="ml-2 text-xs text-red-600"
              :aria-label="t('actions.delete')"
              @click.stop="removeTab(ti)"
            >
              ✕
            </button>
          </Tab>
        </template>
        <template #panel>
          <TabPanel v-for="(tab, ti) in section.tabs" :key="tab.id">
            <draggable
              v-model="tab.fields"
              item-key="id"
              handle=".field-handle"
              class="grid gap-2"
              :class="`grid-cols-${section.cols}`"
              aria-describedby="fieldReorderHint"
            >
              <template #item="{ element }">
                <Card
                  bodyClass="p-2 flex items-center gap-2 cursor-pointer"
                  tabindex="0"
                  role="button"
                  :class="`col-span-${element.cols}`"
                  @click="$emit('select', element)"
                  @keydown.enter.prevent="$emit('select', element)"
                  @keydown.space.prevent="$emit('select', element)"
                >
                  <Button
                    type="button"
                    btnClass="btn-light p-1 field-handle cursor-move"
                    aria-label="Drag field"
                    aria-describedby="fieldReorderHint"
                    @keydown.enter.prevent="noop"
                    @keydown.space.prevent="noop"
                  >
                    <Icon icon="heroicons-outline:bars-3" />
                  </Button>
                  <span class="flex-1">{{ resolveI18n(element.label) }}</span>
                  <Button
                    type="button"
                    btnClass="btn-outline-danger text-xs px-1 py-1"
                    :aria-label="t('actions.delete')"
                    @click.stop="$emit('remove-field', { field: element, tabIndex: ti })"
                  >
                    ✕
                  </Button>
                </Card>
              </template>
            </draggable>
            <div class="mt-2">
              <Button
                type="button"
                btnClass="btn-primary text-xs px-2 py-1"
                :aria-label="t('actions.addField')"
                @click="$emit('add-field', ti)"
              >
                {{ t('actions.addField') }}
              </Button>
            </div>
          </TabPanel>
        </template>
      </UiTabs>
    </div>
    <div v-else>
      <draggable
        v-model="section.fields"
        item-key="id"
        handle=".field-handle"
        class="p-2 grid gap-2"
        :class="`grid-cols-${section.cols}`"
        aria-describedby="fieldReorderHint"
      >
        <template #item="{ element }">
          <Card
            bodyClass="p-2 flex items-center gap-2 cursor-pointer"
            tabindex="0"
            role="button"
            :class="`col-span-${element.cols}`"
            @click="$emit('select', element)"
            @keydown.enter.prevent="$emit('select', element)"
            @keydown.space.prevent="$emit('select', element)"
          >
            <Button
              type="button"
              btnClass="btn-light p-1 field-handle cursor-move"
              aria-label="Drag field"
              aria-describedby="fieldReorderHint"
              @keydown.enter.prevent="noop"
              @keydown.space.prevent="noop"
            >
              <Icon icon="heroicons-outline:bars-3" />
            </Button>
            <span class="flex-1">{{ resolveI18n(element.label) }}</span>
            <Button
              type="button"
              btnClass="btn-outline-danger text-xs px-1 py-1"
              :aria-label="t('actions.delete')"
              @click.stop="$emit('remove-field', { field: element })"
            >
              ✕
            </Button>
          </Card>
        </template>
      </draggable>
    </div>
    <div class="p-2">
      <Dropdown align="left">
        <template #default>
          <Button
            type="button"
            btnClass="btn-primary text-xs items-center px-2 py-1"
            :aria-label="t('actions.add')"
          >
            <span class="inline-flex items-center gap-1">
              {{ t('actions.add') }}
              <Icon icon="heroicons-outline:chevron-down" />
            </span>
          </Button>
        </template>
        <template #menus>
          <MenuItem v-if="!section.tabs.length" #default="{ active }">
            <button type="button" :class="menuItemClass(active)" @click="$emit('add-field')">
              {{ t('actions.addField') }}
            </button>
          </MenuItem>
          <MenuItem #default="{ active }">
            <button type="button" :class="menuItemClass(active)" @click="$emit('add-section')">
              {{ t('actions.addSection') }}
            </button>
          </MenuItem>
          <MenuItem #default="{ active }">
            <button type="button" :class="menuItemClass(active)" @click="addTab">
              Add Tab
            </button>
          </MenuItem>
        </template>
      </Dropdown>
    </div>
  </Card>
</template>

<script setup lang="ts">
import draggable from 'vuedraggable';
import { useI18n } from 'vue-i18n';
import { resolveI18n as resolveI18nUtil } from '@/utils/i18n';
import Icon from '@/components/ui/Icon/index.vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Card from '@/components/ui/Card/index.vue';
import Dropdown from '@/components/ui/Dropdown/index.vue';
import Select from '@/components/ui/Select/index.vue';
import UiTabs from '@/components/ui/Tabs/index.vue';
import { MenuItem, Tab, TabPanel } from '@headlessui/vue';

const props = defineProps<{ section: any }>();
const section = props.section;
defineEmits<{
  (e: 'remove'): void;
  (e: 'select', field: any): void;
  (e: 'add-field', tabIndex?: number): void;
  (e: 'add-section'): void;
  (e: 'remove-field', payload: { field: any; tabIndex?: number }): void;
}>();
const { t, locale } = useI18n();

const noop = () => {};

function addTab() {
  if (!section.tabs) section.tabs = [];
  const newTabIndex = section.tabs.length + 1;
  const fields =
    section.tabs.length === 0 && section.fields && section.fields.length
      ? section.fields.splice(0)
      : [];
  section.tabs.push({
    id: Date.now() + Math.random(),
    key: `tab${newTabIndex}`,
    label: { en: `Tab ${newTabIndex}`, el: `Tab ${newTabIndex}` },
    fields,
  });
}

function removeTab(index: number) {
  const removed = section.tabs.splice(index, 1)[0];
  if (section.tabs.length === 0 && removed && removed.fields) {
    section.fields = removed.fields;
  }
}

function resolveI18n(val: any) {
  return resolveI18nUtil(val, locale.value);
}

function menuItemClass(active: boolean) {
  return (
    (active
      ? 'bg-slate-100 dark:bg-slate-600 dark:bg-opacity-50'
      : 'text-slate-600 dark:text-slate-300') +
    ' block w-full text-left px-4 py-2'
  );
}
</script>
