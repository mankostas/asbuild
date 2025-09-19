import { defineStore } from 'pinia';
import type { RouteLocationRaw } from 'vue-router';
import router from '@/router';

interface TenantModalState {
  isOpen: boolean;
  previousUrl: string | null;
  targetUrl: string | null;
  historyStateId: string | null;
  popstateAttached: boolean;
}

export const useTenantModalStore = defineStore('tenantModal', {
  state: (): TenantModalState => ({
    isOpen: false,
    previousUrl: null,
    targetUrl: null,
    historyStateId: null,
    popstateAttached: false,
  }),
  actions: {
    open(target?: RouteLocationRaw | { href: string }): void {
      if (this.isOpen) {
        return;
      }

      this.isOpen = true;

      if (typeof window === 'undefined') {
        return;
      }

      const resolved =
        target && 'href' in target
          ? target
          : router.resolve(target ?? { name: 'tenants.create' });

      const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
      this.previousUrl = currentUrl;
      this.targetUrl = resolved.href;

      if (currentUrl !== resolved.href) {
        const stateId = `tenant-modal-${Date.now()}`;
        const state =
          window.history.state && typeof window.history.state === 'object'
            ? { ...window.history.state }
            : {};
        state.__tenantModalStateId = stateId;
        this.historyStateId = stateId;
        window.history.pushState(state, '', resolved.href);
      } else {
        this.historyStateId = null;
      }

      if (!this.popstateAttached) {
        window.addEventListener('popstate', this.handlePopState);
        this.popstateAttached = true;
      }
    },
    close(options?: { triggeredByPopstate?: boolean }): void {
      if (!this.isOpen) {
        return;
      }

      this.isOpen = false;

      if (typeof window !== 'undefined' && this.popstateAttached) {
        window.removeEventListener('popstate', this.handlePopState);
        this.popstateAttached = false;
      }

      const previousUrl = this.previousUrl;
      const targetUrl = this.targetUrl;
      this.previousUrl = null;
      this.targetUrl = null;
      this.historyStateId = null;

      if (typeof window === 'undefined') {
        return;
      }

      if (options?.triggeredByPopstate) {
        return;
      }

      if (previousUrl && targetUrl && previousUrl !== targetUrl) {
        const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;

        if (currentUrl === targetUrl) {
          window.history.back();
        } else if (currentUrl !== previousUrl) {
          window.history.replaceState(window.history.state, '', previousUrl);
        }
      }
    },
    handlePopState(event: PopStateEvent): void {
      if (!this.isOpen) {
        return;
      }

      const stateId =
        event.state && typeof event.state === 'object'
          ? (event.state as Record<string, any>).__tenantModalStateId
          : null;

      if (stateId && this.historyStateId && stateId === this.historyStateId) {
        return;
      }

      this.close({ triggeredByPopstate: true });
    },
  },
});
