import { defineStore } from 'pinia';

let nextId = 1;

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: []
  }),
  actions: {
    add(message, type = 'error') {
      const id = nextId++;
      this.toasts.push({ id, message, type });
      // auto-remove after 5s
      setTimeout(() => {
        this.remove(id);
      }, 5000);
    },
    remove(id) {
      this.toasts = this.toasts.filter(t => t.id !== id);
    }
  }
});
