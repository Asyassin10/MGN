import './bootstrap';
import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';

createInertiaApp({
    title: (title) => (title ? `${title} - Droguerie P` : 'Droguerie P'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx');
        return pages[`./Pages/${name}.jsx`]();
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <>
                <App {...props} />
                <Toaster richColors position="top-right" />
            </>,
        );
    },
    progress: {
        color: '#18181b',
    },
});

window.router = router;
