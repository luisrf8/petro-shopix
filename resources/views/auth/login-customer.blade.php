<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shopix | Login cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top left, #fff7ed, #ffedd5 42%, #fed7aa 100%);
            color: #111827;
        }

        .customer-login-shell {
            max-width: 980px;
        }

        .customer-card {
            border: 1px solid rgba(251, 146, 60, 0.28);
            border-radius: 22px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 18px 42px rgba(120, 53, 15, 0.18);
        }

        .customer-aside {
            background: linear-gradient(165deg, #9a3412, #c2410c);
            color: #fff;
            padding: 2rem 1.35rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.9rem;
        }

        .customer-aside h2 {
            margin: 0;
            font-size: clamp(1.25rem, 3.2vw, 1.9rem);
            font-weight: 700;
        }

        .customer-aside p {
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.45;
            color: rgba(255, 255, 255, 0.92);
        }

        .customer-points {
            display: grid;
            gap: 0.55rem;
            font-size: 0.88rem;
        }

        .customer-points i {
            margin-right: 0.4rem;
        }

        .customer-form-wrap {
            padding: 2rem 1.5rem;
        }

        .customer-input {
            border: 1px solid #fdba74;
            border-radius: 12px;
            padding: 0.62rem 0.82rem;
        }

        .customer-input:focus {
            border-color: #fb923c;
            box-shadow: 0 0 0 0.2rem rgba(249, 115, 22, 0.16);
        }

        .customer-error {
            border-radius: 12px;
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container py-4 min-vh-100 d-flex align-items-center justify-content-center">
        <div class="customer-login-shell w-100">
            <div class="customer-card">
                <div class="row g-0">
                    <div class="col-lg-5 customer-aside">
                        <h2>Acceso de clientes</h2>
                        <p>Inicia sesión para revisar tus pedidos y continuar compras en la tienda.</p>
                        <div class="customer-points mt-2">
                            <span><i class="bi bi-bag-heart"></i>Diseñado para experiencia de compra</span>
                            <span><i class="bi bi-person-check"></i>No permite acceso al panel administrativo</span>
                            <span><i class="bi bi-shop-window"></i>Ideal para uso desde las landings de tienda</span>
                        </div>
                        <a href="{{ route('login') }}" class="btn btn-light btn-sm mt-3">Ir a login administrativo</a>
                    </div>
                    <div class="col-lg-7 customer-form-wrap">
                        <div class="mb-4">
                            <h1 class="h3 fw-bold mb-1">Login cliente</h1>
                            <p class="text-muted mb-0">Este acceso es exclusivo para cuentas de cliente.</p>
                        </div>

                        <div id="customer-login-alert" class="alert customer-error d-none" role="alert"></div>

                        <form id="customer-login-form" class="row g-3" novalidate>
                            @csrf
                            <div class="col-12">
                                <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                                <input type="email" class="form-control customer-input" id="email" name="email" placeholder="cliente@correo.com" required>
                            </div>
                            <div class="col-12">
                                <label for="password" class="form-label fw-semibold">Contraseña</label>
                                <input type="password" class="form-control customer-input" id="password" name="password" placeholder="Tu contraseña" required>
                            </div>
                            <div class="col-12 d-grid">
                                <button id="customer-login-submit" type="submit" class="btn btn-dark">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar como cliente
                                </button>
                            </div>
                        </form>

                        <div class="alert alert-warning mt-4 mb-0">
                            Recomendación: para comprar en una tienda específica, usa directamente su landing pública.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const customerLoginForm = document.getElementById('customer-login-form');
        const customerSubmitButton = document.getElementById('customer-login-submit');
        const customerLoginAlert = document.getElementById('customer-login-alert');

        function showCustomerLoginError(message) {
            if (!customerLoginAlert) return;
            customerLoginAlert.textContent = message;
            customerLoginAlert.classList.remove('d-none');
        }

        function clearCustomerLoginError() {
            if (!customerLoginAlert) return;
            customerLoginAlert.textContent = '';
            customerLoginAlert.classList.add('d-none');
        }

        customerLoginForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            clearCustomerLoginError();

            const formData = new FormData(this);
            const defaultLabel = customerSubmitButton.innerHTML;
            customerSubmitButton.disabled = true;
            customerSubmitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Ingresando...';

            try {
                const response = await fetch('/client/login', {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {
                    showCustomerLoginError(data.message || 'No fue posible iniciar sesión.');
                    return;
                }

                if (data?.token) {
                    localStorage.setItem('shopix_ecomm_token', data.token);
                }
                if (data?.user) {
                    localStorage.setItem('shopix_ecomm_user', JSON.stringify(data.user));
                }

                window.location.href = data?.redirect_to || '/';
            } catch (error) {
                showCustomerLoginError('Error de conexión. Intenta nuevamente.');
            } finally {
                customerSubmitButton.disabled = false;
                customerSubmitButton.innerHTML = defaultLabel;
            }
        });
    </script>
</body>
</html>
