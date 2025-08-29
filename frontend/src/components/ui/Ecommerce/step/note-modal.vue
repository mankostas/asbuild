<template>
  <Modal
    ref="modal1"
    :activeModal="activeModal"
    title="New Address"
    label="New"
    labelClass="btn-outline-dark"
    @close="emit('close')"
  >
    <form
      class="space-y-4 flex flex-col justify-between"
      @submit.prevent="submit"
    >
      <Textarea
        label="Address"
        :id="ids.address"
        name="pn4"
        placeholder="Your Address"
        horizontal
      />
      <div class="flex">
        <label
          :for="ids.country"
          class="flex-none flex-0 mr-6 md:w-[100px] break-words ltr:inline-block rtl:block input-label"
        >
          Country
        </label>
        <div class="flex-1 w-full">
          <Select
            :id="ids.country"
            :options="options"
            placeholder="Select Your Country"
            classLabel="!w-[150px]"
          />
        </div>
      </div>
      <div class="flex">
        <label
          :for="ids.state"
          class="flex-none flex-0 mr-6 md:w-[100px] break-words ltr:inline-block rtl:block input-label"
        >
          State
        </label>
        <div class="flex-1 w-full">
          <Select
            :id="ids.state"
            :options="options"
            placeholder="Select Your State"
            classLabel="!w-[150px]"
          />
        </div>
      </div>
      <div class="flex">
        <label
          :for="ids.city"
          class="flex-none flex-0 mr-6 md:w-[100px] break-words ltr:inline-block rtl:block input-label"
        >
          City
        </label>
        <div class="flex-1 w-full">
          <Select
            :id="ids.city"
            :options="options"
            placeholder="Select Your City"
            classLabel="!w-[150px]"
          />
        </div>
      </div>

      <InputGroup
        type="text"
        :id="ids.postalCode"
        label="Postal Code"
        name="ps-1"
        placeholder="Your Postal Code"
        horizontal
      />
      <InputGroup
        type="text"
        :id="ids.phone"
        label="Phone No"
        name="ps-1"
        placeholder="+880"
        horizontal
      />

      <div class="col-span-12 flex justify-end">
        <button
          type="submit"
          class="btn btn-dark h-min w-max text-sm font-normal"
        >
          Save Address
        </button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
const options = ref([
  { value: "option1", label: "Option 1" },
  { value: "option2", label: "Option 2" },
  { value: "option3", label: "Option 3" },
]);

import Modal from "@/components/ui/Modal";
import Textarea from "@/components/ui/Textarea";
import InputGroup from "@/components/ui/InputGroup";
import Select from "@/components/ui/Select";
import { defineProps, defineEmits, ref } from "vue";
const props = defineProps({
  activeModal: {
    type: Boolean,
    default: false,
  },
});
const emit = defineEmits(["close"]);
const ids = {
  address: 'address-input',
  country: 'country-select',
  state: 'state-select',
  city: 'city-select',
  postalCode: 'postal-code',
  phone: 'phone-number',
};
</script>

<style lang="scss" scoped></style>
