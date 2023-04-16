import useSWRImmutable from 'swr/immutable'
import {
    getSupportArticles,
    getSupportArticleCategories,
    getSupportArticle,
    getSearchResults,
} from '@assist/api/Data'

export const useSupportArticles = () => {
    const { data, error } = useSWRImmutable('support-articles', async () => {
        const response = await getSupportArticles()
        if (!response?.data || !Array.isArray(response.data)) {
            console.error(response)
            throw new Error('Bad data')
        }
        return response.data
    })
    return { data, error, loading: !data && !error }
}

export const useSupportArticleCategories = () => {
    const { data, error } = useSWRImmutable(
        'support-article-categories',
        async () => {
            const response = await getSupportArticleCategories()
            if (!response?.data || !Array.isArray(response.data)) {
                console.error(response)
                throw new Error('Bad data')
            }
            return response.data
        },
    )
    return { data: data, error, loading: !data && !error }
}

export const useSupportArticle = (slug) => {
    const { data, error } = useSWRImmutable(
        `support-article-${slug}`,
        async () => {
            const response = await getSupportArticle(slug)
            if (!response?.data || !Array.isArray(response.data)) {
                console.error(response)
                throw new Error('Bad data')
            }
            return response.data?.[0] ?? {}
        },
    )
    return { data, error, loading: !data && !error }
}

export const useSearchArticles = ({ term, perPage, offset }) => {
    const { data, error } = useSWRImmutable(
        { term, perPage, offset },
        async ({ term, perPage, offset }) => {
            if (!term) return []

            const response = await getSearchResults(term, perPage, offset)
            if (!response?.data || !Array.isArray(response.data)) {
                throw new Error('Bad data')
            }
            return response.data
        },
    )
    return { data, error, loading: !data && !error }
}
