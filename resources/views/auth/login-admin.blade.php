<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shopix | Login administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top right, #1f2937, #0f172a 45%, #020617 85%);
            color: #0f172a;
        }

        .login-shell {
            max-width: 980px;
        }

        .login-brand {
            width: 180px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 8px 22px rgba(2, 6, 23, 0.35));
        }

        .login-card {
            border: 1px solid rgba(203, 213, 225, 0.7);
            border-radius: 22px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 24px 54px rgba(2, 6, 23, 0.35);
        }

        .login-aside {
            background: linear-gradient(160deg, #0f172a, #1e293b);
            color: #fff;
            padding: 2rem 1.4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.75rem;
        }

        .login-aside h2 {
            font-size: clamp(1.2rem, 3.5vw, 1.8rem);
            font-weight: 700;
            margin-bottom: 0.45rem;
        }

        .login-aside p {
            color: rgba(255, 255, 255, 0.82);
            margin-bottom: 0;
            font-size: 0.92rem;
            line-height: 1.35;
        }

        .login-point {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.86rem;
            color: rgba(255, 255, 255, 0.86);
        }

        .login-form-wrap {
            padding: 2rem 1.5rem;
            background: #ffffff;
        }

        .login-title {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }

        .login-subtitle {
            color: #64748b;
            font-size: 0.92rem;
            margin-bottom: 1rem;
        }

        .login-field-label {
            font-weight: 600;
            color: #334155;
            font-size: 0.9rem;
        }

        .login-input {
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 0.62rem 0.82rem;
            font-size: 0.95rem;
        }

        .login-input:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 0.2rem rgba(15, 23, 42, 0.12);
        }

        .login-submit-btn {
            border-radius: 12px;
            padding: 0.7rem;
            font-weight: 600;
        }

        .login-submit-btn[disabled] {
            opacity: 0.75;
        }

        .login-error {
            border-radius: 12px;
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
            margin-bottom: 1rem;
        }

        @media (max-width: 991.98px) {
            .login-aside {
                padding: 1.25rem;
            }

            .login-form-wrap {
                padding: 1.35rem 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4 min-vh-100 d-flex align-items-center justify-content-center">
        <div class="login-shell w-100">
            <div class="login-card">
                <div class="row g-0">
                    <div class="col-lg-5 login-aside">
                        <h2>Acceso administrativo Shopix</h2>
                        <p>Este inicio de sesión es exclusivo para administración de tienda y panel superior.</p>
                        <div class="mt-2 d-grid gap-2">
                            <div class="login-point"><i class="bi bi-shield-check"></i><span>Acceso seguro y protegido</span></div>
                            <div class="login-point"><i class="bi bi-diagram-3"></i><span>Separado del acceso de clientes</span></div>
                            <div class="login-point"><i class="bi bi-stars"></i><span>Gestión del backoffice</span></div>
                        </div>
                        <a href="{{ route('client.login') }}" class="btn btn-outline-light btn-sm mt-3">Ir a login de cliente</a>
                    </div>
                    <div class="col-lg-7 login-form-wrap">
                        <div class="text-center mb-4">
                            <a href="/" class="text-decoration-none">
                                <img src="{{ asset('assets/img/shopix5.png') }}" class="login-brand" alt="Shopix">
                            </a>
                        </div>
                        <h1 class="h3 login-title">Login administrativo</h1>
                        <p class="login-subtitle">Ingresa con una cuenta de administración para continuar.</p>

                        <div id="login-alert" class="alert login-error d-none" role="alert"></div>

                        <form id="shopix-login-form" class="row g-3" novalidate>
                            @csrf
                            <div class="col-12">
                                <label for="email" class="form-label login-field-label">Correo electrónico</label>
                                <input type="email" class="form-control login-input" id="email" name="email" placeholder="Ingresa tu correo" required>
                            </div>
                            <div class="col-12">
                                <label for="password" class="form-label login-field-label">Contraseña</label>
                                <input type="password" class="form-control login-input" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                            </div>
                            <div class="col-12 d-grid">
                                <button id="shopix-login-submit" type="submit" class="btn btn-dark login-submit-btn">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const loginForm = document.getElementById('shopix-login-form');
        const submitButton = document.getElementById('shopix-login-submit');
        const loginAlert = document.getElementById('login-alert');

        function showLoginError(message) {
            if (!loginAlert) return;
            loginAlert.textContent = message;
            loginAlert.classList.remove('d-none');
        }

        function clearLoginError() {
            if (!loginAlert) return;
            loginAlert.textContent = '';
            loginAlert.classList.add('d-none');
        }

        loginForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            clearLoginError();

            const formData = new FormData(this);
            const defaultLabel = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Ingresando...';

            try {
                const response = await fetch('/admin/login', {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {
                    showLoginError(data.message || 'Credenciales incorrectas.');
                    return;
                }

                window.location.href = data?.redirect_to || '/products';
            } catch (error) {
                showLoginError('Ocurrió un error al intentar iniciar sesión. Intenta nuevamente.');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = defaultLabel;
            }
        });
    </script>
</body>
</html>
