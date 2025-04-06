<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard AppTaxi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
    background-color: #f8f9fa;
}

.navbar {
    width: 100%;
    z-index: 1050; /* Asegura que la navbar esté sobre todo */
}

/* Sidebar */
.sidebar {
    height: 100vh;
    width: 250px;
    background: #FFC107;
    position: fixed;
    padding-top: 20px;
    transition: all 0.3s;
    left: 0;
    z-index: 1100; /* Mayor que navbar */
}

.sidebar a {
    color: #333;
    padding: 15px;
    display: flex;
    align-items: center;
    text-decoration: none;
    font-weight: bold;
}

.sidebar a:hover {
    background: #e0a800;
    color: white;
}

/* Contenido */
.content {
    margin-left: 250px;
    padding: 20px;
    transition: margin-left 0.3s;
}

/* Cuando el sidebar está cerrado */
.sidebar-collapsed .content {
    margin-left: 0;
}

/* Botón de hamburguesa */
.toggle-btn {
    position: absolute;
    top: 15px;
    left: 260px;
    font-size: 24px;
    cursor: pointer;
    display: none;
}

/* Diseño responsivo */
@media (max-width: 768px) {
    .sidebar {
        left: -250px;
        position: fixed;
    }
    .content {
        margin-left: 0;
    }
    .toggle-btn {
        display: block;
        left: 20px;
    }
}

/* Estilos para las tarjetas */
.card-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center; /* Centrar tarjetas */
    gap: 15px;
}

.card {
    flex: 1 1 300px; /* Se adaptan sin volverse demasiado pequeñas */
    min-width: 250px;
    max-width: auto;
    text-align: center;
    padding: 15px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <!-- Botón para abrir la sidebar en pantallas pequeñas -->
            <button class="toggle-btn btn btn-xs btn-warning mx-auto d-lg-none px-1 py-0 w-auto" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand ms-5" href="#">AppTaxi</a>

            <!-- Botón "Cerrar Sesión" en móviles (visible en pantallas pequeñas) -->
            <form action="{{ route('logout') }}" method="GET" class="d-inline">
                @csrf
                <button class="btn btn-warning d-lg-none ms-auto">Cerrar Sesión</button>
            </form>

            <!-- Menú normal en pantallas grandes -->
            <div class="collapse navbar-collapse d-none d-lg-block" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="GET" class="d-inline">
                            @csrf
                            <button class="btn btn-warning" >Cerrar Sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="sidebar" id="sidebar">
        <a href="#" class="nav-link" data-url="{{ route('adminDashboard') }}"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="#" class="nav-link" data-url="{{ route('adminUsuarios') }}"><i class="fas fa-users"></i> Usuarios</a>
        <a href="#" class="nav-link" data-url="{{ route('adminConductores') }}"><i class="fas fa-car"></i> Conductores</a>
        <a href="#" class="nav-link" data-url="{{ route('adminClientes') }}"><i class="fas fa-user-friends"></i> Clientes</a>
        <a href="#" class="nav-link" data-url="{{ route('adminViajes') }}"><i class="fas fa-road"></i> Viajes </a>
        <a href="#" class="nav-link" data-url="{{ route('adminPricing') }}"><i class="fas fa-dollar-sign"></i> Precios</a>
    </div>

    <div class="content" id="main-content">
        <h1>Dashboard, Bienvenido  @auth  {{Auth::user()->name}} @endauth</h1>
        <div class="row">
            <div class="col-md-3">
                <div class="card p-3">
                    <div class="icon text-primary"><i class="fas fa-users"></i></div>
                    <h5>Usuarios Activos</h5>
                    <h2 id="totalUsers">0</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <div class="icon text-success"><i class="fas fa-car"></i></div>
                    <h5>Usuarios Conductores</h5>
                    <h2 id="totalDrivers">0</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <div class="icon text-warning"><i class="fas fa-dollar-sign"></i></div>
                    <h5>Ingresos Totales</h5>
                    <h2 id="totalIncome">$0</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <div class="icon text-purple"><i class="fas fa-star"></i></div>
                    <h5>Numero de Pedidos</h5>
                    <h2 id="totalRequests">0</h2>
                </div>
            </div>
        </div> 
    </div>
    
    <script>
        function cargarEstadisticas() {
            $.get('/api/stats', function(data) {
                console.log("Estadísticas actualizadas:", data);
                $("#totalUsers").text(data.totalUsers);
                $("#totalDrivers").text(data.totalDrivers);
                $("#totalRequests").text(data.totalRequests);
                $("#totalIncome").text(`COP ${data.totalIncome}`);
            }).fail(function() {
                alert("Error al cargar estadísticas.");
            });
        }
        $(document).ready(function () {

            cargarEstadisticas();
            // Manejo de clic en los enlaces
            $(".nav-link").click(function (e) {
                e.preventDefault();
                var url = $(this).data("url");
                loadPage(url);
            });

            function loadPage(url) {
                // Verificar si la URL es HTTP y convertirla a HTTPS
                /*if (url.startsWith("http://")) {
                    url = url.replace("http://", "https://");
                }*/
                
                $("#main-content").html("<p>Cargando...</p>"); // Mostrar mensaje de carga
                $.get(url, function (data) {
                    $("#main-content").html(data); // Insertar contenido dinámico
                });
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            if (sidebar.style.left === "0px") {
                sidebar.style.left = "-250px";
            } else {
                sidebar.style.left = "0px";
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
