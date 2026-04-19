@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
<div class="container-fluid py-3">
  <div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
        <div>
          <h5 class="mb-1">Notificaciones en este dispositivo</h5>
          <p class="text-sm text-muted mb-0">Activa aquí las alertas del admin después de instalar la PWA o agregar Shopix a la pantalla de inicio.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" id="backoffice-install-pwa" class="btn btn-outline-dark mb-0 d-none">
            Instalar app
          </button>
          <button type="button" id="backoffice-enable-browser-notifications" class="btn btn-dark mb-0 d-none">
            Activar alertas
          </button>
        </div>
      </div>

      <div class="alert alert-light border mb-2" id="backoffice-notification-setup-state" role="status">
        Revisa el estado de instalación y permisos para este iPhone o Android.
      </div>

      <div class="text-sm text-muted" id="backoffice-notification-setup-hint">
        Si estás en iPhone, primero abre Shopix desde Safari, toca Compartir y usa "Agregar a pantalla de inicio". Luego abre la app instalada y activa las alertas aquí.
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Notificaciones</h5>
      <span class="text-sm text-muted">{{ method_exists($notifications, 'total') ? $notifications->total() : 0 }} registros</span>
    </div>
    <div class="card-body">
      @if($notifications->isEmpty())
        <p class="text-muted mb-0">No tienes notificaciones.</p>
      @else
        <div class="list-group" id="notifications-list-group">
          @foreach($notifications as $notification)
            <div class="list-group-item d-flex justify-content-between align-items-start {{ is_null($notification['read_at']) ? 'bg-light' : '' }}" data-notification-id="{{ $notification['id'] }}">
              <div class="me-3">
                <h6 class="mb-1">{{ $notification['title'] ?? 'Notificación' }}</h6>
                <p class="mb-1 text-sm">{{ $notification['message'] ?? '' }}</p>
                <small class="text-muted">{{ \Carbon\Carbon::parse($notification['created_at'])->format('d/m/Y H:i') }}</small>
              </div>
              <div class="d-flex flex-column gap-2 align-items-end">
                @if(!empty($notification['target_url']))
                  <a href="{{ $notification['target_url'] }}" class="btn btn-sm btn-dark mb-0 url-icon-action-btn url-icon-action-btn-sm" aria-label="Abrir" title="Abrir">
                    <i class="material-symbols-rounded">open_in_new</i>
                  </a>
                @endif
                @if(is_null($notification['read_at']))
                  <form method="POST" action="{{ route('notifications.read', $notification['id']) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-dark mb-0">Marcar leída</button>
                  </form>
                @else
                  <span class="badge bg-success">Leída</span>
                @endif
              </div>
            </div>
          @endforeach
        </div>

        <div class="mt-3">
          {{ $notifications->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (() => {
    const setupState = document.getElementById('backoffice-notification-setup-state');
    const setupHint = document.getElementById('backoffice-notification-setup-hint');
    const list = document.getElementById('notifications-list-group');
    const userId = @json(optional(auth()->user())->id);
    if (!userId) return;

    const knownIds = new Set(Array.from(list?.querySelectorAll('[data-notification-id]') || []).map(el => el.getAttribute('data-notification-id')));

    function buildNotificationHtml(notification) {
      const isUnread = !notification.is_read;
      const rowClass = isUnread ? 'bg-light' : '';
      const openButton = notification.target_url
        ? `<a href="${notification.target_url}" class="btn btn-sm btn-dark mb-0 url-icon-action-btn url-icon-action-btn-sm" aria-label="Abrir" title="Abrir"><i class="material-symbols-rounded">open_in_new</i></a>`
        : '';
      const action = isUnread
        ? `<form method="POST" action="/notifications/${notification.id}/read">
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''}">
            <button type="submit" class="btn btn-sm btn-outline-dark mb-0">Marcar leída</button>
          </form>`
        : '<span class="badge bg-success">Leída</span>';

      return `
        <div class="list-group-item d-flex justify-content-between align-items-start ${rowClass}" data-notification-id="${notification.id}">
          <div class="me-3">
            <h6 class="mb-1">${notification.title || 'Notificación'}</h6>
            <p class="mb-1 text-sm">${notification.message || ''}</p>
            <small class="text-muted">${notification.created_at || ''}</small>
          </div>
          <div class="d-flex flex-column gap-2 align-items-end">${openButton}${action}</div>
        </div>
      `;
    }

    function appendNotification(notification) {
      if (!list) {
        return;
      }

      if (!notification || !notification.id || knownIds.has(notification.id)) {
        return;
      }

      knownIds.add(notification.id);
      const normalized = {
        id: notification.id,
        title: notification.title || 'Notificación',
        message: notification.message || '',
        created_at: notification.created_at || new Date().toLocaleString(),
        is_read: false,
        target_url: notification.target_url || null,
      };

      list.insertAdjacentHTML('afterbegin', buildNotificationHtml(normalized));
    }

    function setSetupState(message, tone = 'light') {
      if (!setupState) {
        return;
      }

      setupState.className = `alert alert-${tone} border mb-2`;
      setupState.textContent = message;
    }

    function setSetupHint(message) {
      if (!setupHint) {
        return;
      }

      setupHint.textContent = message;
    }

    function supportsBrowserNotifications() {
      return window.isSecureContext && 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
    }

    function isIosDevice() {
      return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    }

    function isAndroidDevice() {
      return /android/i.test(window.navigator.userAgent);
    }

    function isStandaloneMode() {
      return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    function browserNotificationSupportState() {
      return {
        secureContext: window.isSecureContext,
        notificationApi: 'Notification' in window,
        serviceWorkerApi: 'serviceWorker' in navigator,
        pushManagerApi: 'PushManager' in window,
        vapidConfigured: !!@json(config('webpush.vapid.public_key')),
      };
    }

    function updateInstallPwaUi() {
      const installPwaBtn = document.getElementById('backoffice-install-pwa');
      if (!installPwaBtn) {
        return;
      }

      installPwaBtn.classList.remove('d-none');

      if (isStandaloneMode()) {
        installPwaBtn.textContent = 'App instalada';
        installPwaBtn.classList.remove('btn-outline-dark');
        installPwaBtn.classList.add('btn-success');
        return;
      }

      installPwaBtn.classList.remove('btn-success');
      installPwaBtn.classList.add('btn-outline-dark');

      if (isIosDevice()) {
        installPwaBtn.textContent = 'Agregar a inicio';
        setSetupHint('En iPhone o iPad: abre Shopix en Safari, toca Compartir, usa "Agregar a pantalla de inicio" y luego vuelve aquí desde la app instalada para activar las alertas.');
        return;
      }

      if (isAndroidDevice()) {
        setSetupHint('En Android puedes instalar la app desde este botón o desde el menú del navegador. Luego activa las alertas desde esta misma pantalla.');
      }

      installPwaBtn.textContent = 'Instalar app';
    }

    async function installBackofficePwa() {
      const installPwaBtn = document.getElementById('backoffice-install-pwa');
      if (!installPwaBtn || isStandaloneMode()) {
        return;
      }

      if (isIosDevice()) {
        alert('En iPhone o iPad, abre este sitio en Safari, toca Compartir y luego selecciona "Agregar a pantalla de inicio". Después abre Shopix desde el icono instalado y vuelve a esta pantalla para activar las alertas.');
        return;
      }

      if (!window.__shopixDeferredInstallPrompt) {
        alert('La instalación aún no está disponible en este navegador. Recarga la página, usa HTTPS y asegúrate de que el sitio no esté ya instalado.');
        return;
      }

      window.__shopixDeferredInstallPrompt.prompt();
      const choice = await window.__shopixDeferredInstallPrompt.userChoice.catch(() => null);
      window.__shopixDeferredInstallPrompt = null;
      updateInstallPwaUi();

      if (choice?.outcome === 'accepted') {
        setSetupState('La instalación de Shopix fue aceptada. Cuando abras la app instalada, activa las alertas desde este mismo panel.', 'success');
      }
    }

    async function ensureServiceWorkerRegistration() {
      if (!supportsBrowserNotifications()) {
        return null;
      }

      if (!window.__shopixServiceWorkerRegistrationPromise) {
        window.__shopixServiceWorkerRegistrationPromise = navigator.serviceWorker.register(@json(url('/push-sw.js')), { scope: '/' });
      }

      return window.__shopixServiceWorkerRegistrationPromise;
    }

    function urlBase64ToUint8Array(base64String) {
      const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
      const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
      const rawData = window.atob(base64);
      const outputArray = new Uint8Array(rawData.length);

      for (let index = 0; index < rawData.length; index += 1) {
        outputArray[index] = rawData.charCodeAt(index);
      }

      return outputArray;
    }

    async function syncBrowserPushSubscription() {
      const vapidPublicKey = @json(config('webpush.vapid.public_key'));
      if (!supportsBrowserNotifications() || !vapidPublicKey) {
        return null;
      }

      const registration = await ensureServiceWorkerRegistration();
      if (!registration) {
        return null;
      }

      let subscription = await registration.pushManager.getSubscription();
      if (!subscription) {
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        });
      }

      await fetch('/push-subscriptions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({
          subscription: subscription.toJSON(),
        }),
      });

      return subscription;
    }

    function updateBrowserNotificationUi() {
      const enableBrowserNotificationsBtn = document.getElementById('backoffice-enable-browser-notifications');
      if (!enableBrowserNotificationsBtn) {
        return;
      }

      const support = browserNotificationSupportState();
      enableBrowserNotificationsBtn.classList.remove('d-none');

      if (!supportsBrowserNotifications()) {
        enableBrowserNotificationsBtn.textContent = 'Alertas no disponibles';
        enableBrowserNotificationsBtn.classList.remove('btn-success');
        enableBrowserNotificationsBtn.classList.add('btn-dark');
        setSetupState('Este dispositivo todavía no puede activar alertas web en este contexto.', 'warning');
        return;
      }

      if (!support.vapidConfigured) {
        enableBrowserNotificationsBtn.textContent = 'Alertas no configuradas';
        setSetupState('Las notificaciones push no están configuradas en el servidor.', 'warning');
        return;
      }

      if (isIosDevice() && !isStandaloneMode()) {
        enableBrowserNotificationsBtn.textContent = 'Instala la app primero';
        setSetupState('En iPhone debes abrir Shopix desde la app agregada a pantalla de inicio para poder activar notificaciones.', 'info');
        return;
      }

      const permission = Notification.permission;
      if (permission === 'granted') {
        enableBrowserNotificationsBtn.textContent = 'Alertas activas';
        enableBrowserNotificationsBtn.classList.remove('btn-dark');
        enableBrowserNotificationsBtn.classList.add('btn-success');
        setSetupState('Este dispositivo ya tiene las alertas del admin activas.', 'success');
        return;
      }

      enableBrowserNotificationsBtn.classList.remove('btn-success');
      enableBrowserNotificationsBtn.classList.add('btn-dark');

      if (permission === 'denied') {
        enableBrowserNotificationsBtn.textContent = 'Alertas bloqueadas';
        setSetupState('El permiso está bloqueado. Debes habilitarlo manualmente en la configuración del navegador o del sistema.', 'danger');
        return;
      }

      enableBrowserNotificationsBtn.textContent = 'Activar alertas';
      setSetupState('La app está lista para pedir permiso y vincular este dispositivo a las alertas del admin.', 'light');
    }

    async function requestBrowserNotificationPermission() {
      const enableBrowserNotificationsBtn = document.getElementById('backoffice-enable-browser-notifications');
      const support = browserNotificationSupportState();

      if (!supportsBrowserNotifications()) {
        const missing = [];
        if (!support.secureContext) missing.push('HTTPS');
        if (!support.notificationApi) missing.push('Notification API');
        if (!support.serviceWorkerApi) missing.push('Service Worker');
        if (!support.pushManagerApi) missing.push('Push API');

        alert(`Este navegador todavía no puede activar alertas web aquí. Falta: ${missing.join(', ')}.`);
        return;
      }

      if (isIosDevice() && !isStandaloneMode()) {
        alert('En iPhone debes abrir Shopix desde la app agregada a pantalla de inicio antes de activar las alertas.');
        return;
      }

      if (!support.vapidConfigured) {
        alert('Las notificaciones push aún no están configuradas en el servidor.');
        return;
      }

      if (Notification.permission === 'denied') {
        alert('El permiso de notificaciones está bloqueado. Debes habilitarlo manualmente en la configuración del navegador o del sistema.');
        return;
      }

      enableBrowserNotificationsBtn?.setAttribute('disabled', 'disabled');

      try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
          updateBrowserNotificationUi();
          return;
        }

        await syncBrowserPushSubscription();
        setSetupState('Las alertas del admin quedaron activadas en este dispositivo.', 'success');
      } finally {
        enableBrowserNotificationsBtn?.removeAttribute('disabled');
        updateBrowserNotificationUi();
      }
    }

    window.addEventListener('beforeinstallprompt', (event) => {
      event.preventDefault();
      window.__shopixDeferredInstallPrompt = event;
      updateInstallPwaUi();
    });

    window.addEventListener('appinstalled', () => {
      window.__shopixDeferredInstallPrompt = null;
      updateInstallPwaUi();
      updateBrowserNotificationUi();
    });

    window.addEventListener('shopix:backoffice-notification', (event) => {
      appendNotification(event.detail || null);
    });

    ensureServiceWorkerRegistration().catch(() => {});
    updateInstallPwaUi();
    updateBrowserNotificationUi();

    document.getElementById('backoffice-enable-browser-notifications')?.addEventListener('click', requestBrowserNotificationPermission);
    document.getElementById('backoffice-install-pwa')?.addEventListener('click', installBackofficePwa);
  })();
</script>
@endpush
