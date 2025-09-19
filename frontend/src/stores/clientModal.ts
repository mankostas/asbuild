import { defineStore } from 'pinia';

interface ClientModalState {
  isOpen: boolean;
}

export const useClientModalStore = defineStore('clientModal', {
  state: (): ClientModalState => ({
    isOpen: false,
  }),
  actions: {
    open(): void {
      this.isOpen = true;
    },
    close(): void {
      this.isOpen = false;
    },
  },
});
