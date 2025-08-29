<template>
  <Modal
    ref="modal1"
    :activeModal="activeModal"
    title="Pickup Address"
    label="Pickup"
    labelClass="btn-outline-dark"
    @close="emit('close')"
  >
    <form class="space-y-5" @submit.prevent="submit">
      <InputGroup
        type="text"
        name="ps-1"
        placeholder="Enter Pickup Address"
        horizontal
      />
      <div class="space-y-3">
        <div class="card border dark:border-slate-700 rounded-lg p-5">
          <label class="flex gap-1 items-center cursor-pointer">
            <Radio v-model="picked1" name="x" value="A" />
            <div class="space-y-1">
              <p class="font-medium text-base rtl:text-right">Mohakhali DOHS</p>
              <p class="font-normal text-sm text-slate-500 flex items-center">
                <Icon icon="mi:location" class="mr-1 rtl:ml-1" /> House 395,
                Road 29, New Area
              </p>
              <p class="font-normal text-sm text-slate-500 flex items-center">
                <Icon icon="solar:phone-outline" class="mr-1 rtl:ml-1" />
                +880123456789
              </p>
            </div>
          </label>
        </div>
        <div class="card border dark:border-slate-700 rounded-lg p-5">
          <label class="flex gap-1 items-center cursor-pointer">
            <Radio v-model="picked1" name="x" value="B" />
            <div class="space-y-1">
              <p class="font-medium text-base rtl:text-right">Mohakhali DOHS</p>
              <p class="font-normal text-sm text-slate-500 flex items-center">
                <Icon icon="mi:location" class="mr-1 rtl:ml-1" /> House 395,
                Road 29, New Area
              </p>
              <p class="font-normal text-sm text-slate-500 flex items-center">
                <Icon icon="solar:phone-outline" class="mr-1 rtl:ml-1" />
                +880123456789
              </p>
            </div>
          </label>
        </div>
        <div class="card border dark:border-slate-700 rounded-lg p-5">
          <label class="flex gap-1 items-center cursor-pointer">
            <Radio v-model="picked1" name="x" value="C" />
            <div class="space-y-1">
              <p class="font-medium text-base rtl:text-right">Mohakhali DOHS</p>
              <p class="font-normal text-sm text-slate-500 flex items-center">
                <Icon icon="mi:location" class="mr-1 rtl:ml-1" /> House 395,
                Road 29, New Area
              </p>
              <p class="font-normal text-sm text-slate-500 flex items-center">
                <Icon icon="solar:phone-outline" class="mr-1 rtl:ml-1" />
                +880123456789
              </p>
            </div>
          </label>
        </div>
      </div>
      <div class="col-span-12 flex justify-end">
        <button
          type="submit"
          class="btn btn-dark h-min w-max text-sm font-normal"
        >
          Done
        </button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import Icon from "@/components/ui/Icon";
import InputGroup from "@/components/ui/InputGroup";
import Radio from "@/components/ui/Radio";
import Modal from "@/components/ui/Modal";

import { defineProps, defineEmits, ref } from "vue";
const props = defineProps({
  activeModal: {
    type: Boolean,
    default: false,
  },
});
const emit = defineEmits(["close"]);

const picked1 = ref("A");
</script>

<style lang="scss" scoped></style>
