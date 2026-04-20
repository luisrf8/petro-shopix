<meta name="theme-color" content="{{ $tenantColorPrimary ?? '#0F172A' }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit('Shopix', 24, '') }}">
<link rel="manifest" href="{{ route('tenant.pwa.manifest', ['start_url' => route('landing.directory'), 'name' => 'Shopix', 'theme' => '2563eb']) }}">
<link rel="apple-touch-icon" href="{{ asset('assets/img/shopix5.png') }}">