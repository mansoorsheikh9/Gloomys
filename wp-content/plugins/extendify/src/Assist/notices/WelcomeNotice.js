import { useState, useEffect } from '@wordpress/element'
import { __ } from '@wordpress/i18n'
import { useGlobalStore, useGlobalStoreReady } from '@assist/state/Global'
import { useTourStore } from '@assist/state/Tours'
import siteAssistantTour from '@assist/tours/site-assistant.js'

const noticeKey = 'welcome-message'
export const WelcomeNotice = () => {
    const { isDismissed, dismissNotice } = useGlobalStore()
    const ready = useGlobalStoreReady()
    // To avoid content flash, we load in this partial piece of state early via php
    const dismissed = window.extAssistData.dismissedNotices.find(
        (notice) => notice.id === noticeKey,
    )
    const [enabled, setEnabled] = useState(false)
    const { startTour } = useTourStore()
    const { launchCompleted } = window.extAssistData

    useEffect(() => {
        if (dismissed || isDismissed(noticeKey)) {
            return
        }
        setEnabled(true)
    }, [dismissed, isDismissed, dismissNotice])

    useEffect(() => {
        if (!enabled || !ready || !launchCompleted) return
        // For this notice, we only want to show it once
        dismissNotice(noticeKey)
    }, [dismissNotice, enabled, ready, launchCompleted])

    if (!enabled) return null
    if (!launchCompleted) return null

    return (
        <div
            id="assist-welcome-notice"
            className="bg-design-main text-design-text p-12 max-w-screen-2xl mx-4 md:mx-12 3xl:mx-auto mt-6 xl:mt-12">
            <div className="grid grid-cols-1 xl:grid-cols-12 gap-12 items-start">
                <div className="xl:text-right col-span-5">
                    <p className="font-bold m-0 text-2xl">
                        {__('Congratulations!', 'extendify')}
                    </p>
                    <span className="block text-base">
                        {__('Your site is ready.', 'extendify')}
                    </span>
                </div>
                <div className="xl:max-w-lg col-span-7">
                    <h1 className="text-4xl mt-0 text-white">
                        {__("What's Next?", 'extendify')}
                    </h1>
                    <p className="text-base">
                        {__(
                            'The Extendify Assistant is your go-to dashboard to help you get the most out of your site. Take a quick tour!',
                            'extendify',
                        )}
                    </p>
                    <div className="flex mt-8">
                        <button
                            className="flex items-center gap-1 text-sm sm:text-base text-design-main cursor-pointer rounded-sm px-4 py-2 bg-white border-none no-underline"
                            onClick={() => startTour(siteAssistantTour)}>
                            {__('Take a tour', 'extendify')}
                        </button>
                        <button
                            className="bg-transparent text-white opacity-70 hover:opacity-100 border-0 shadow-none px-4 py-2 cursor-pointer text-base flex items-center"
                            type="button"
                            onClick={() => setEnabled(false)}>
                            <span className="dashicons dashicons-no-alt mr-1"></span>
                            <span>{__('Dismiss', 'extendify')}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    )
}
