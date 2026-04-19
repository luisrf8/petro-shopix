self.addEventListener('push', function (event) {
  if (!event.data) {
    return;
  }

  const payload = event.data.json();
  const title = payload.title || 'Notificación';
  const options = {
    body: payload.body || '',
    icon: payload.icon || '/assets/img/shopix5.png',
    badge: payload.badge || '/assets/img/shopix5.png',
    data: payload.data || {},
    actions: payload.actions || [],
    tag: payload.tag,
    image: payload.image,
    vibrate: payload.vibrate,
    renotify: payload.renotify,
    requireInteraction: payload.requireInteraction,
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();

  const targetUrl = event.notification?.data?.url || '/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (const client of clientList) {
        if ('focus' in client) {
          client.navigate(targetUrl);
          return client.focus();
        }
      }

      if (self.clients.openWindow) {
        return self.clients.openWindow(targetUrl);
      }

      return undefined;
    })
  );
});