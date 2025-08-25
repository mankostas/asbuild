import { onMounted, onUnmounted, ref } from 'vue';

export interface Action {
  id: string;
  label: string;
  to?: string;
  run?: () => void;
}

const isOpen = ref(false);
const actions = ref<Action[]>([]);

function open(newActions: Action[] = []) {
  actions.value = newActions;
  isOpen.value = true;
}
function close() {
  isOpen.value = false;
}

function onKey(e: KeyboardEvent) {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    isOpen.value = !isOpen.value;
  }
}

export function useCommandPalette() {
  onMounted(() => window.addEventListener('keydown', onKey));
  onUnmounted(() => window.removeEventListener('keydown', onKey));
  return { isOpen, actions, open, close };
}
