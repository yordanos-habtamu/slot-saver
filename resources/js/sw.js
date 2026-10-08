// SlotSaver Progressive Web App Service Worker (source)
export const registerServiceWorker = () => {
    if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker
                .register('/sw.js')
                .then((registration) => {
                    console.log(
                        'SlotSaver ServiceWorker registered successfully:',
                        registration.scope,
                    );
                })
                .catch((error) => {
                    console.warn(
                        'SlotSaver ServiceWorker registration failed:',
                        error,
                    );
                });
        });
    }
};
