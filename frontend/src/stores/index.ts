import { createPinia, setActivePinia } from 'pinia';

export const pinia = createPinia();

// Ensure Pinia is active for usages outside of Vue components
setActivePinia(pinia);

export default pinia;
