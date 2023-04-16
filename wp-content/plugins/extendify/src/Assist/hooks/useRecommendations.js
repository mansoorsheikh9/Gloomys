import useSWRImmutable from 'swr/immutable'
import { getRecommendations } from '@assist/api/Data'
import { getActivePlugins } from '@assist/api/WPApi'

export const useRecommendations = () => {
    const { data: recommendations, error } = useSWRImmutable(
        'recommendations',
        async () => {
            const { data: activePlugins } = await getActivePlugins()
            const response = await getRecommendations()
            if (!response?.data || !Array.isArray(response.data)) {
                console.error(response)
                throw new Error('Bad data')
            }
            // return response.data
            return response.data?.filter((recommendation) => {
                // If no plugins, show the recommendations
                if (!recommendation?.pluginDepSlugs?.length) return true
                // Check if task.plugins intersect with activePlugins
                return recommendation?.pluginDepSlugs?.some((plugin) =>
                    activePlugins?.includes(plugin),
                )
            })
        },
    )

    return { recommendations, error, loading: !recommendations && !error }
}
