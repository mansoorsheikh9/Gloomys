import useSWRImmutable from 'swr/immutable'
import { getTasks } from '@assist/api/Data'
import { completedDependency } from '@assist/api/Data'
import { getActivePlugins } from '@assist/api/WPApi'
import { useTasksStore, useTasksStoreReady } from '@assist/state/Tasks'

export const useTasks = () => {
    const { isCompleted, completeTask } = useTasksStore()
    const ready = useTasksStoreReady()
    const { data: tasks, error } = useSWRImmutable(
        () => (ready ? 'tasks' : null),
        async () => {
            const { data: activePlugins } = await getActivePlugins()
            const response = await getTasks()
            if (!response?.data || !Array.isArray(response.data)) {
                console.error(response)
                throw new Error('Bad data')
            }
            const tasks = response.data?.filter((task) => {
                // If no plugins, show the task
                if (!task?.plugins?.length) return true
                // Check if task.plugins intersect with activePlugins
                return task?.plugins?.some((plugin) =>
                    activePlugins?.includes(plugin),
                )
            })

            for (const task of tasks ?? []) {
                const { slug, doneDependencies } = task
                if (!doneDependencies) continue
                if (isCompleted(slug)) continue
                const { data: done } = await completedDependency(slug)
                if (done) completeTask(task.slug)
            }
            return tasks
        },
    )

    return { tasks, error, loading: !tasks && !error }
}
