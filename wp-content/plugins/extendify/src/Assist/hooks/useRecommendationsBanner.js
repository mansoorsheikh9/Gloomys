import useSWRImmutable from 'swr/immutable'
import { getRecommendationsBanner } from '@assist/api/Data'

export const useRecommendationsBanner = () => {
    const { data: recommendationsBanner, error } = useSWRImmutable(
        'recommendationsBanner',
        async () => {
            const response = await getRecommendationsBanner()
            if (!response?.data || !Array.isArray(response.data)) {
                console.error(response)
                throw new Error('Bad data')
            }
            return response.data
        },
    )

    return {
        recommendationsBanner,
        error,
        loading: !recommendationsBanner && !error,
    }
}
