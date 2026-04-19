@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
<div class="container-fluid py-3">
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
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
  (() => {
    const list = document.getElementById('notifications-list-group');
    if (!list) return;
    const userId = @json(optional(auth()->user())->id);
    if (!userId) return;

    const knownIds = new Set(Array.from(list.querySelectorAll('[data-notification-id]')).map(el => el.getAttribute('data-notification-id')));

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

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const pusherKey = @json(config('broadcasting.connections.reverb.key'));
    if (!pusherKey) return;

    const configuredHost = @json(config('broadcasting.connections.reverb.options.host'));
    const configuredPort = Number(@json(config('broadcasting.connections.reverb.options.port')));
    const configuredScheme = @json(config('broadcasting.connections.reverb.options.scheme'));
    const configuredCluster = @json(config('broadcasting.connections.pusher.options.cluster'));

    const browserHost = window.location.hostname;
    const wsHost = !configuredHost || configuredHost === '127.0.0.1' || configuredHost === '0.0.0.0'
      ? browserHost
      : configuredHost;

    const forceTLS = configuredScheme
      ? configuredScheme === 'https'
      : window.location.protocol === 'https:';

    const wsPort = configuredPort || (forceTLS ? 443 : 80);

    const pusherOptions = {
      wsHost,
      wsPort,
      wssPort: wsPort,
      forceTLS,
      enabledTransports: ['ws', 'wss'],
      disableStats: true,
      authEndpoint: '/broadcasting/auth',
      auth: {
        headers: {
          'X-CSRF-TOKEN': csrf,
        },
      },
    };

    if (configuredCluster) {
      pusherOptions.cluster = configuredCluster;
    }

    const pusher = new Pusher(pusherKey, pusherOptions);

    const channel = pusher.subscribe(`private-App.Models.User.${userId}`);
    const handleIncoming = (notification) => appendNotification(notification);
    channel.bind('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleIncoming);
    channel.bind('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleIncoming);
  })();
</script>
@endpush
