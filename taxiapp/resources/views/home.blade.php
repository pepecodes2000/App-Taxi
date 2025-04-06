<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppTaxi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Estilos personalizados -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">AppTaxi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="#como-funciona">Cómo Funciona</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
                    <li class="nav-item">
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#loginModal">Iniciar Sesión</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Banner -->
    <header class="bg-warning text-center py-5">
        <div class="container">
            <h1 class="fw-bold">Solicita tu Taxi Rápido y Seguro</h1>
            <p class="lead">Con un solo clic, el taxi más cercano irá por ti.</p>
            <a href="#" class="btn btn-dark btn-lg">Pedir Taxi</a>
        </div>
    </header>
    
    <!-- Sección Cómo Funciona -->
    <section id="como-funciona" class="container py-5 text-center">
        <h2>¿Cómo Funciona?</h2>
        <p>Regístrate, comparte tu ubicación y un conductor te recogerá en minutos.</p>
    </section>

    <!-- Modal de Inicio de Sesión -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Iniciar Sesión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm" method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">Ingresar</button>

                        <!-- Div para mostrar errores -->
                        <div id="loginError" class="alert alert-danger d-none mt-3"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de Inicio de Sesión -->

    <!-- Contacto -->
    <footer id="contacto" class="bg-dark text-white text-center py-3">
        <p>&copy; 2025 AppTaxi.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
