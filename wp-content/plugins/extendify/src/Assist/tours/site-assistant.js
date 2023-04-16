import { __ } from '@wordpress/i18n'

export default {
    id: 'site-assistant-tour',
    settings: {
        allowOverflow: false,
        startFrom:
            window.extAssistData.adminUrl +
            'admin.php?page=extendify-assist#dashboard',
    },
    steps: [
        {
            title: __('Site Assistant', 'extendify'),
            text: __(
                'The Site Assistant gives you personalized recommendations and helps with creating and maintaining your site.',
                'extendify',
            ),
            attachTo: {
                element: '#assist-menu-bar',
                offset: {
                    marginTop: 20,
                    marginLeft: -5,
                },
                position: {
                    x: 'left',
                    y: 'bottom',
                },
                hook: 'top left',
                boxPadding: {
                    top: 5,
                    bottom: 5,
                    left: 5,
                    right: 5,
                },
            },
            events: {
                onAttach: () => {
                    if (window.innerWidth <= 960) return
                    document.querySelector('#assist-menu-bar').scrollIntoView()
                },
            },
        },
        {
            title: __('Tasks', 'extendify'),
            text: __(
                "Now that you've created your starter site, make it your own with these follow up tasks.",
                'extendify',
            ),
            showOnlyIf: () => document.querySelector('.assist-tasks-module'),
            attachTo: {
                element: '#assist-tasks-module',
                offset: {
                    marginTop: 0,
                    marginLeft: 15,
                },
                position: {
                    x: 'right',
                    y: 'top',
                },
                hook: 'top left',
            },
            events: {
                onAttach: () => {
                    if (window.innerWidth <= 960) return
                    document
                        .querySelector('#assist-tasks-module')
                        .scrollIntoView()
                },
            },
        },
        {
            title: __('Knowledge Base', 'extendify'),
            text: __(
                'Find articles with information on accomplishing different things with WordPress, including screenshots, and videos.',
                'extendify',
            ),
            attachTo: {
                element: '#assist-knowledge-base-module',
                offset: {
                    marginTop: 0,
                    marginLeft: -15,
                },
                position: {
                    x: 'left',
                    y: 'top',
                },
                hook: 'top right',
            },
            events: {
                onAttach: () => {
                    if (window.innerWidth <= 960) return
                    document
                        .querySelector('#assist-knowledge-base-module')
                        .scrollIntoView()
                },
            },
        },
        {
            title: __('Tours', 'extendify'),
            text: __(
                'See additional tours of the different parts of WordPress. Restart your completed tours at any time.',
                'extendify',
            ),
            attachTo: {
                element: '#assist-tours-module',
                offset: {
                    marginTop: 0,
                    marginLeft: -15,
                },
                position: {
                    x: 'left',
                    y: 'top',
                },
                hook: 'top right',
            },
            events: {
                onAttach: () => {
                    if (window.innerWidth <= 960) return
                    document
                        .querySelector('#assist-tours-module')
                        .scrollIntoView()
                },
            },
        },
        {
            title: __('Quick Links', 'extendify'),
            text: __(
                'Easily access some of the most common items in WordPress with these quick links.',
                'extendify',
            ),
            attachTo: {
                element: '#assist-quick-links-module',
                offset: {
                    marginTop: 0,
                    marginLeft: -15,
                },
                position: {
                    x: 'left',
                    y: 'top',
                },
                hook: 'top right',
            },
            events: {
                onAttach: () => {
                    if (window.innerWidth <= 960) return
                    document
                        .querySelector('#assist-quick-links-module')
                        .scrollIntoView()
                },
            },
        },
        {
            title: __('Recommendations', 'extendify'),
            text: __(
                'See our personalized recommendations for you that will help you accomplish your goals.',
                'extendify',
            ),
            attachTo: {
                element: '#assist-recommendations-module',
                offset: {
                    marginTop: 0,
                    marginLeft: 15,
                },
                position: {
                    x: 'right',
                    y: 'top',
                },
                hook: 'top left',
            },
            events: {
                onAttach: () => {
                    if (window.innerWidth <= 960) return
                    document
                        .querySelector('#assist-recommendations-module')
                        .scrollIntoView()
                },
            },
        },
        {
            title: __('Site Assistant', 'extendify'),
            text: __(
                'Come back to the Site Assistant any time by clicking the menu item.',
                'extendify',
            ),
            attachTo: {
                element: '#toplevel_page_extendify-admin-page',
                offset: {
                    marginTop: 0,
                    marginLeft: 15,
                },
                position: {
                    x: 'right',
                    y: 'top',
                },
                hook: 'top left',
            },
            events: {},
        },
    ],
}
