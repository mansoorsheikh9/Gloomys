import { useState, useEffect } from '@wordpress/element'
import { create } from 'zustand'
import { devtools, persist, createJSONStorage } from 'zustand/middleware'
import {
    getSupportArticlesData,
    saveSupportArticlesData,
} from '@assist/api/Data'

const state = (set) => ({
    articles: [],
    recentArticles: [],
    viewedArticles: [],
    activeCategory: null,
    searchTerm: null,
    offset: 0,
    pushArticle(article) {
        const { slug, title } = article
        set((state) => {
            const lastViewedAt = new Date().toISOString()
            const firstViewedAt = lastViewedAt
            const viewed = state.viewedArticles.find((a) => a.slug === slug)

            return {
                articles: [article, ...state.articles],
                recentArticles: [article, ...state.recentArticles.slice(0, 9)],
                viewedArticles: [
                    // Remove the article if it's already in the list
                    ...state.viewedArticles.filter((a) => a.slug !== slug),
                    // Either add the article or update the count
                    viewed
                        ? { ...viewed, count: viewed.count + 1, lastViewedAt }
                        : {
                              slug,
                              title,
                              firstViewedAt,
                              lastViewedAt,
                              count: 1,
                          },
                ],
            }
        })
    },
    popArticle() {
        set((state) => ({ articles: state.articles.slice(1) }))
    },
    clearArticles() {
        set({ articles: [] })
    },
    setActiveCategory(slug) {
        set({ activeCategory: slug })
    },
    reset() {
        set({ articles: [], activeCategory: null, searchTerm: null, offset: 0 })
    },
    updateTitle(slug, title) {
        // We don't always know the title until after we fetch the article data
        set((state) => ({
            articles: state.articles.map((article) => {
                if (article.slug === slug) {
                    article.title = title
                }
                return article
            }),
        }))
    },
    clearSearchTerm() {
        set({ searchTerm: null, offset: 0 })
    },
    setSearchTerm(term) {
        set({ searchTerm: term, offset: 0 })
    },
    setOffset(offset) {
        set({ offset })
    },
})
const storage = {
    getItem: async () => JSON.stringify(await getSupportArticlesData()),
    setItem: async (_, value) => await saveSupportArticlesData(value),
    removeItem: () => undefined,
}

export const useKnowledgeBaseStore = create(
    persist(devtools(state, { name: 'Extendify Assist Knowledge Base' }), {
        name: 'extendify-assist-knowledge-base',
        storage: createJSONStorage(() => storage),
        partialize: (state) => {
            delete state.articles
            delete state.activeCategory
            delete state.searchTerm
            delete state.offset
            return state
        },
    }),
    state,
)

/* Hook useful for when you need to wait on the async state to hydrate */
export const useKnowledgeBaseStoreReady = () => {
    const [hydrated, setHydrated] = useState(
        useKnowledgeBaseStore.persist.hasHydrated,
    )
    useEffect(() => {
        const unsubFinishHydration =
            useKnowledgeBaseStore.persist.onFinishHydration(() =>
                setHydrated(true),
            )
        return () => {
            unsubFinishHydration()
        }
    }, [])
    return hydrated
}
