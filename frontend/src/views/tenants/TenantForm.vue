<template>
  <Modal
    v-if="isModal"
    :activeModal="true"
    :title="modalTitle"
    sizeClass="max-w-3xl"
    @close="closeModal"
  >
    <div v-if="isLoading" class="py-10 flex justify-center">
      <img src="@/assets/images/svg/loader.svg" alt="" class="h-12 w-12" />
    </div>
    <div v-else-if="canAccess" class="max-w-md grid gap-4">
      <form class="grid gap-4" @submit.prevent="onSubmit">
        <Textinput v-model="form.name" :label="t('tenants.form.name')" />
        <div v-if="errors.name" class="text-red-600 text-sm">{{ errors.name }}</div>
        <Textinput
          v-model.number="form.quota_storage_mb"
          :label="t('tenants.form.storageQuota')"
          type="number"
        />
        <Textinput v-model="form.phone" :label="t('tenants.form.phone')" />
        <Textinput v-model="form.address" :label="t('tenants.form.address')" />
        <Textinput v-if="!isEdit" v-model="form.user_name" :label="t('tenants.form.adminName')" />
        <div v-if="!isEdit && errors.user_name" class="text-red-600 text-sm">{{ errors.user_name }}</div>
        <Textinput
          v-if="!isEdit"
          v-model="form.user_email"
          :label="t('tenants.form.adminEmail')"
          type="email"
        />
        <div v-if="!isEdit" class="space-y-2">
          <div v-if="errors.user_email" class="text-red-600 text-sm">
            {{ errors.user_email }}
          </div>
          <Switch
            v-model="notifyOwner"
            :label="t('tenants.form.notify.label')"
            :description="t('tenants.form.notify.description')"
            :disabled="notifyDisabled"
          />
          <div v-if="errors.notify_owner" class="text-red-600 text-sm">
            {{ errors.notify_owner }}
          </div>
        </div>
        <VueSelect :label="t('tenants.form.features')" :error="errors.features">
          <template #default="{ inputId }">
            <vSelect
              :id="inputId"
              v-model="form.features"
              :options="featureOptions"
              multiple
              :reduce="(f: any) => f.value"
            />
          </template>
        </VueSelect>
        <div v-if="form.features.length" class="grid gap-4">
          <h3 class="font-medium">{{ t('tenants.form.abilitiesPerFeature') }}</h3>
          <VueSelect
            v-for="f in form.features"
            :key="f"
            :label="featureMap[f]?.label || f"
          >
            <template #default="{ inputId }">
              <vSelect
                :id="inputId"
                v-model="featureAbilities[f]"
                :options="abilityOptionsFor(f)"
                multiple
                :reduce="(a: any) => a.value"
              />
            </template>
          </VueSelect>
        </div>
        <div v-if="serverError" class="text-red-600 text-sm">{{ serverError }}</div>
        <Button type="submit" :text="t('actions.save')" btnClass="btn-dark" />
      </form>
    </div>
  </Modal>

  <div v-else class="p-4">
    <div v-if="isLoading" class="py-10 flex justify-center">
      <img src="@/assets/images/svg/loader.svg" alt="" class="h-12 w-12" />
    </div>
    <div v-else-if="canAccess" class="max-w-md grid gap-4">
      <form class="grid gap-4" @submit.prevent="onSubmit">
        <Textinput v-model="form.name" :label="t('tenants.form.name')" />
        <div v-if="errors.name" class="text-red-600 text-sm">{{ errors.name }}</div>
        <Textinput
          v-model.number="form.quota_storage_mb"
          :label="t('tenants.form.storageQuota')"
          type="number"
        />
        <Textinput v-model="form.phone" :label="t('tenants.form.phone')" />
        <Textinput v-model="form.address" :label="t('tenants.form.address')" />
        <Textinput v-if="!isEdit" v-model="form.user_name" :label="t('tenants.form.adminName')" />
        <div v-if="!isEdit && errors.user_name" class="text-red-600 text-sm">{{ errors.user_name }}</div>
        <Textinput
          v-if="!isEdit"
          v-model="form.user_email"
          :label="t('tenants.form.adminEmail')"
          type="email"
        />
        <div v-if="!isEdit" class="space-y-2">
          <div v-if="errors.user_email" class="text-red-600 text-sm">
            {{ errors.user_email }}
          </div>
          <Switch
            v-model="notifyOwner"
            :label="t('tenants.form.notify.label')"
            :description="t('tenants.form.notify.description')"
            :disabled="notifyDisabled"
          />
          <div v-if="errors.notify_owner" class="text-red-600 text-sm">
            {{ errors.notify_owner }}
          </div>
        </div>
        <VueSelect :label="t('tenants.form.features')" :error="errors.features">
          <template #default="{ inputId }">
            <vSelect
              :id="inputId"
              v-model="form.features"
              :options="featureOptions"
              multiple
              :reduce="(f: any) => f.value"
            />
          </template>
        </VueSelect>
        <div v-if="form.features.length" class="grid gap-4">
          <h3 class="font-medium">{{ t('tenants.form.abilitiesPerFeature') }}</h3>
          <VueSelect
            v-for="f in form.features"
            :key="f"
            :label="featureMap[f]?.label || f"
          >
            <template #default="{ inputId }">
              <vSelect
                :id="inputId"
                v-model="featureAbilities[f]"
                :options="abilityOptionsFor(f)"
                multiple
                :reduce="(a: any) => a.value"
              />
            </template>
          </VueSelect>
        </div>
        <div v-if="serverError" class="text-red-600 text-sm">{{ serverError }}</div>
        <Button type="submit" :text="t('actions.save')" btnClass="btn-dark" />
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { extractFormErrors } from '@/services/api';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import vSelect from 'vue-select';
import { useForm } from 'vee-validate';
import { useTenantStore } from '@/stores/tenant';
import hasAbility from '@/utils/ability';
import { useFeaturesStore } from '@/stores/features';
import { storeToRefs } from 'pinia';
import { useI18n } from 'vue-i18n';
import Switch from '@/components/ui/Switch/index.vue';
import Modal from '@/components/ui/Modal';
import { useNotify } from '@/plugins/notify';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => route.name === 'tenants.edit');
const tenantStore = useTenantStore();
const featuresStore = useFeaturesStore();
const { featureMap } = storeToRefs(featuresStore);
const { t } = useI18n();
const notify = useNotify();

const props = defineProps<{ forceModal?: boolean }>();
const emit = defineEmits<{ (event: 'close'): void }>();

const isForcedModal = computed(() => Boolean(props.forceModal));
const isModal = computed(() => isForcedModal.value || Boolean(route.meta?.modal));
const modalTitle = computed(() =>
  isEdit.value ? t('routes.tenantEdit') : t('routes.tenantCreate'),
);

const canAccess = computed(
  () =>
    hasAbility('tenants.create') ||
    hasAbility('tenants.update') ||
    hasAbility('tenants.manage'),
);

const form = ref({
  name: '',
  quota_storage_mb: 0,
  phone: '',
  address: '',
  features: [] as string[],
  user_name: '',
  user_email: '',
});

const featureOptions = ref<{ label: string; value: string }[]>([]);
const featureAbilities = ref<Record<string, string[]>>({});

const serverError = ref('');
const { handleSubmit, setErrors, errors } = useForm();
const notifyOwner = ref(false);
const notifyDisabled = computed(() => !form.value.user_email);
const isLoading = ref(true);

watch(
  () => form.value.user_email,
  (email) => {
    if (!email && notifyOwner.value) {
      notifyOwner.value = false;
    }
    if (!email) {
      setErrors({ notify_owner: '' });
    }
  },
);

function closeModal() {
  emit('close');
}

onMounted(async () => {
  if (!canAccess.value) {
    isLoading.value = false;
    return;
  }
  try {
    const map = await featuresStore.load();
    featureOptions.value = Object.entries(map).map(([slug, data]) => ({
      label: data.label,
      value: slug,
    }));
  } catch (e) {
    featureOptions.value = [];
  }
  if (isEdit.value) {
    try {
      const { data } = await api.get(`/tenants/${route.params.id}`);
      form.value = {
        name: data.name || '',
        quota_storage_mb: data.quota_storage_mb || 0,
        phone: data.phone || '',
        address: data.address || '',
        features: Array.isArray(data.features) ? data.features : [],
        user_name: '',
        user_email: '',
      };
      featureAbilities.value = { ...(data.feature_abilities || {}) };
      notifyOwner.value = false;
      form.value.features.forEach((f: string) => {
        if (!featureAbilities.value[f]) {
          featureAbilities.value[f] = [...featuresStore.abilitiesFor(f)];
        }
      });
      tenantStore.setTenantFeatures(route.params.id as string, form.value.features);
      tenantStore.setAllowedAbilities(
        route.params.id as string,
        featureAbilities.value,
      );
    } catch (e: any) {
      serverError.value = e?.message || t('tenants.form.loadError');
    } finally {
      isLoading.value = false;
    }
  } else {
    isLoading.value = false;
  }
});

const onSubmit = handleSubmit(async () => {
  serverError.value = '';
  if (!canAccess.value) return;
  if (!isEdit.value && form.value.features.length === 0) {
    form.value.features = ['tasks'];
  }
  const payload: any = {
    name: form.value.name,
    quota_storage_mb: form.value.quota_storage_mb,
    phone: form.value.phone,
    address: form.value.address,
    features: form.value.features,
    feature_abilities: featureAbilities.value,
  };
  if (!isEdit.value) {
    payload.user_name = form.value.user_name;
    payload.user_email = form.value.user_email;
    payload.notify_owner = notifyOwner.value;
  }
  try {
    if (isEdit.value) {
      await api.patch(`/tenants/${route.params.id}`, payload);
    } else {
      await api.post('/tenants', payload);
    }
    notify.success(
      isEdit.value ? t('tenants.form.success.updated') : t('tenants.form.success.created'),
    );
    await tenantStore.loadTenants();
    if (isModal.value) {
      emit('close');
    } else {
      router.push({ name: 'tenants.list' });
    }
  } catch (e: any) {
    const errs = extractFormErrors(e);
    if (Object.keys(errs).length) {
      setErrors(errs);
    } else {
      serverError.value = e.message || t('tenants.form.saveError');
    }
  }
});

function abilityOptionsFor(feature: string) {
  return featuresStore.abilitiesFor(feature).map((a) => ({
    label: a,
    value: a,
  }));
}

watch(
  () => form.value.features,
  (features) => {
    const selected = new Set(features);
    Object.keys(featureAbilities.value).forEach((f) => {
      if (!selected.has(f)) {
        delete featureAbilities.value[f];
      }
    });
    features.forEach((f) => {
      if (!featureAbilities.value[f]) {
        featureAbilities.value[f] = [...featuresStore.abilitiesFor(f)];
      }
    });
    if (isEdit.value) {
      tenantStore.setTenantFeatures(route.params.id as string, features);
      tenantStore.setAllowedAbilities(
        route.params.id as string,
        featureAbilities.value,
      );
    }
  },
  { immediate: false },
);

watch(
  featureAbilities,
  (val) => {
    if (isEdit.value) {
      tenantStore.setAllowedAbilities(route.params.id as string, val);
    }
  },
  { deep: true },
);
</script>
