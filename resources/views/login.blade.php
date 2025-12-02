<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página de Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
        body {
            /* background: black; */
            background-size: cover;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.8); /* Fondo semitransparente */
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
</style>
<body>
    <div class="container d-flex justify-content-center flex-column align-items-center vh-100">
        <a class="d-flex justify-content-center align-items-center mb-5" href="/">
            <img src="../../assets/img/shopix5.png" class="w-50" alt="main_logo">
        </a>
        <!-- <img src="../../assets/img/fondo.jpg" class="navbar-brand-img" width="150" height="150" alt="main_logo"> -->

        <div class="col-md-5 d-flex justify-content-center flex-column login-container py-0 my-0">
            <h3 class="text-center">Iniciar sesion</h3>
            <form class="d-flex flex-column py-0 my-0">
                @csrf
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Ingresa tu correo" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                </div>
                <button type="submit" class="btn btn-dark">Ingresar</button>
            </form>
            <!-- <div class="text-center mt-3">
                <a>¿Olvidaste tu contraseña?</a>
            </div> -->
            <!-- <div class="text-center mt-2">
                <small>¿No tienes una cuenta? <a href="/register">Regístrate aquí</a></small>
            </div> -->
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.6.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<script>
    document.querySelector("form").addEventListener("submit", function(event) {
        event.preventDefault();

        let formData = new FormData(this);
        let submitButton = this.querySelector("button[type='submit']");

        submitButton.textContent = "Cargando...";
        submitButton.disabled = true;
        fetch("/login", {
            method: "POST",
            body: formData
        })
        .then(response => {
            return response.json().then(data => {
                if (response.ok) {
                    console.log("response", data)
                    window.location.href = '/dashboard'; // Descomenta si deseas redirigir
                } else {
                    alert(data.message || "Credenciales incorrectas");
                }
            });
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Ocurrió un error al intentar iniciar sesión");
        })
        .finally(() => {
            submitButton.textContent = "Ingresar";
            submitButton.disabled = false;
        });
    });
</script>
