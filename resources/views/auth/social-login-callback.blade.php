<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopix | Acceso social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(145deg, #111827, #1f2937 55%, #ea580c);
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        .social-sync-card {
            width: min(28rem, calc(100vw - 2rem));
            border-radius: 22px;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.14);
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.28);
            text-align: center;
        }

        .sync-spinner {
            width: 3rem;
            height: 3rem;
            border-width: 0.35rem;
        }
    </style>
</head>
<body>
    <div class="social-sync-card">
        <div class="spinner-border sync-spinner text-warning mb-3" role="status" aria-hidden="true"></div>
        <h1 class="h4 fw-bold mb-2">Conectando con {{ $providerLabel }}</h1>
        <p class="mb-0 text-white-50">Estamos preparando tu acceso y redirigiéndote.</p>
    </div>

    <script>
        localStorage.setItem('shopix_ecomm_token', @json($token));
        localStorage.setItem('shopix_ecomm_user', JSON.stringify(@json($user)));
        window.location.replace(@json($redirectTo ?: '/'));
    </script>
</body>
</html>