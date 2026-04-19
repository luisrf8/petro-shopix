<meta name="theme-color" content="{{ $tenantColorPrimary ?? '#0F172A' }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit((string) ($tenant->name ?? 'Shopix'), 24, '') }}">
<link rel="manifest" href="{{ route('tenant.pwa.manifest', ['start_url' => request()->getRequestUri(), 'name' => $tenant->name ?? 'Shopix', 'theme' => ltrim((string) ($tenantColorPrimary ?? '#0F172A'), '#')]) }}">
<link rel="apple-touch-icon" href="{{ \App\Support\ImageStorage::url($tenant->logo ?? null) ?? asset('assets/img/shopix5.png') }}">