import { useEffect, useState } from '@wordpress/element'
import { create } from 'zustand'
import { devtools, persist, createJSONStorage } from 'zustand/middleware'
import { getGlobalData, saveGlobalData } from '../api/Data'

const state = (set, get) => ({
    dismissedNotices: [],
    dismissedBanners: [],
    modals: [],
    pushModal(modal) {
        set((state) => ({ modals: [modal, ...state.modals] }))
    },
    popModal() {
        set((state) => ({ modals: state.modals.slice(1) }))
    },
    clearModals() {
        set({ modals: [] })
    },
    isDismissed(id) {
        return get().dismissedNotices.some((notice) => notice.id === id)
    },
    dismissNotice(id) {
        if (get().isDismissed(id)) return
        const notice = { id, dismissedAt: new Date().toISOString() }
        set((state) => ({
            dismissedNotices: [...state.dismissedNotices, notice],
        }))
    },
    isDismissedBanner(id) {
        return get().dismissedBanners.some((banner) => banner.id === id)
    },
    dismissBanner(id) {
        if (get().isDismissedBanner(id)) return
        const banner = { id, dismissedAt: new Date().toISOString() }
        set((state) => ({
            dismissedBanners: [...state.dismissedBanners, banner],
        }))
    },
})

const storage = {
    getItem: async () => JSON.stringify(await getGlobalData()),
    setItem: async (_, value) => await saveGlobalData(value),
    removeItem: () => undefined,
}

export const useGlobalStore = create(
    persist(devtools(state, { name: 'Extendify Assist Globals' }), {
        name: 'extendify-assist-globals',
        storage: createJSONStorage(() => storage),
        partialize: (state) => {
            delete state.modals
            return state
        },
    }),
    state,
)

/* Hook useful for when you need to wait on the async state to hydrate */
export const useGlobalStoreReady = () => {
    const [hydrated, setHydrated] = useState(useGlobalStore.persist.hasHydrated)
    useEffect(() => {
        const unsubFinishHydration = useGlobalStore.persist.onFinishHydration(
            () => setHydrated(true),
        )
        return () => {
            unsubFinishHydration()
        }
    }, [])
    return hydrated
}
