@php($tenantPwaIconVersion = (string) config('app.asset_version', '20260710'))
<meta name="theme-color" content="{{ $tenantColorPrimary ?? '#0F172A' }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit('Shopix', 24, '') }}">
<link rel="manifest" href="{{ route('tenant.pwa.manifest', ['start_url' => route('landing'), 'name' => 'Shopix', 'theme' => '2563eb', 'icon_variant' => 'client']) }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ route('pwa.icon', ['size' => 180, 'variant' => 'client', 'v' => $tenantPwaIconVersion]) }}">