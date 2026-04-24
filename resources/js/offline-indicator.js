function setOfflineState(isOffline) {
    if (!document.body) return;
    document.body.dataset.offline = isOffline ? 'true' : 'false';
}

function handleRetry() {
    if (navigator.onLine) {
        setOfflineState(false);
        window.location.reload();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setOfflineState(!navigator.onLine);

    window.addEventListener('offline', () => setOfflineState(true));
    window.addEventListener('online', () => setOfflineState(false));

    const retry = document.getElementById('offline-overlay-retry');
    if (retry) retry.addEventListener('click', handleRetry);
});
