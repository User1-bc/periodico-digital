// Service Worker para Notificaciones Push
self.addEventListener('push', function(event) {
    let data = { title: 'Nuevo contenido', body: 'Hay una nueva publicación en el periódico.', url: '/' };
    
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }
    
    const options = {
        body: data.body,
        icon: 'favicon.ico',
        badge: 'favicon.ico',
        data: { url: data.url || '/' },
        requireInteraction: true,
        silent: false,
        vibrate: [200, 100, 200],
        tag: 'periodico-notification'
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
            for (let client of windowClients) {
                if (client.url === event.notification.data.url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(event.notification.data.url);
            }
        })
    );
});

self.addEventListener('notificationclose', function(event) {
    console.log('Notificación cerrada:', event.notification.tag);
});