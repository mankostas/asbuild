<template>
  <div>
    <h2 class="text-lg font-semibold mb-2">{{ t('slaPolicies.title') }}</h2>
    <div v-for="(p, idx) in policies" :key="p.id ?? idx" class="mb-4">
      <Card>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Select
            :id="`priority-${idx}`"
            v-model="p.priority"
            :label="t('slaPolicies.priority')"
            :options="priorityOptions"
            class="w-full"
          />
          <Textinput
            :id="`response-${idx}`"
            v-model.number="p.response_within_mins"
            type="number"
            :label="t('slaPolicies.responseWithin')"
            class="w-full"
          />
          <Textinput
            :id="`resolve-${idx}`"
            v-model.number="p.resolve_within_mins"
            type="number"
            :label="t('slaPolicies.resolveWithin')"
            class="w-full"
          />
          <div class="md:col-span-2 space-y-2">
            <Switch
              :id="`use-calendar-${idx}`"
              v-model="p.useCalendar"
              :label="t('slaPolicies.useCalendar')"
            />
            <Textarea
              v-if="p.useCalendar"
              :id="`calendar-${idx}`"
              v-model="p.calendar_json"
              :label="t('slaPolicies.calendar')"
              rows="2"
              class="w-full"
            />
          </div>
          <Button
            type="button"
            btnClass="btn-outline-primary text-xs px-3 py-1"
            :aria-label="t('actions.save')"
            @click="save(p)"
          >
            {{ t('actions.save') }}
          </Button>
        </div>
      </Card>
    </div>
    <Button
      type="button"
      class="mt-2"
      btnClass="btn-outline-primary text-xs px-3 py-1"
      :aria-label="t('actions.add')"
      @click="addPolicy"
    >
      {{ t('actions.add') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import Card from '@/components/ui/Card/index.vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import Textarea from '@/components/ui/Textarea/index.vue';
import Select from '@/components/ui/Select/index.vue';
import Switch from '@/components/ui/Switch/index.vue';
import Button from '@/components/ui/Button/index.vue';

interface Policy {
  id?: number;
  priority: string;
  response_within_mins?: number | null;
  resolve_within_mins?: number | null;
  calendar_json?: string | null;
  useCalendar?: boolean;
}

const props = defineProps<{ taskTypeId?: number }>();
const { t } = useI18n();
const policies = ref<Policy[]>([]);
const priorityOptions = [
  { value: 'low', label: t('slaPolicies.low') },
  { value: 'medium', label: t('slaPolicies.medium') },
  { value: 'high', label: t('slaPolicies.high') },
];

onMounted(() => {
  if (props.taskTypeId) {
    load();
  }
});

async function load() {
  if (!props.taskTypeId) return;
  const res = await api.get(`/task-types/${props.taskTypeId}/sla-policies`);
  policies.value = res.data.data.map((p: any) => ({
    ...p,
    calendar_json: p.calendar_json ? JSON.stringify(p.calendar_json) : '',
    useCalendar: !!p.calendar_json,
  }));
}

function addPolicy() {
  policies.value.push({
    priority: 'low',
    response_within_mins: null,
    resolve_within_mins: null,
    calendar_json: '',
    useCalendar: false,
  });
}

async function save(p: Policy) {
  if (!props.taskTypeId) return;
  const payload: any = {
    priority: p.priority,
    response_within_mins: p.response_within_mins,
    resolve_within_mins: p.resolve_within_mins,
    calendar_json: p.useCalendar && p.calendar_json
      ? JSON.parse(p.calendar_json)
      : null,
  };
  if (p.id) {
    const res = await api.put(
      `/task-types/${props.taskTypeId}/sla-policies/${p.id}`,
      payload,
    );
    Object.assign(p, res.data.data, {
      calendar_json: p.calendar_json,
      useCalendar: p.useCalendar,
    });
  } else {
    const res = await api.post(
      `/task-types/${props.taskTypeId}/sla-policies`,
      payload,
    );
    Object.assign(p, res.data.data, {
      calendar_json: p.calendar_json,
      useCalendar: p.useCalendar,
    });
  }
}
</script>
