import { defineStore } from 'pinia'

export const useSidebarStore = defineStore('user', {
  state: () => ({
    isOpen: false,
  }),

  actions: {
    toggleIsOpen(status: boolean) {
      this.isOpen = !this.isOpen;
    }
  }
})