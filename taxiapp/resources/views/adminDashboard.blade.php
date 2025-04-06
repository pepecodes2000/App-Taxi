@php
    $totalUsers = \App\Models\User::count();
    $totalDrivers = \App\Models\Driver::count();
    $totalRequests = \App\Models\RideRequest::count();
@endphp

<h1>Dashboard, Bienvenido  @auth {{ Auth::user()->name }} @endauth</h1>

<div class="row">
    <div class="col-md-3">
        <div class="card p-3">
            <div class="icon text-primary"><i class="fas fa-users"></i></div>
            <h5>Usuarios Activos</h5>
            <h2 id="totalUsers">{{ $totalUsers }}</h2> <!-- Aquí se actualiza -->
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="icon text-success"><i class="fas fa-car"></i></div>
            <h5>Usuarios Conductores</h5>
            <h2 id="totalDrivers">{{ $totalDrivers }}</h2> <!-- Aquí se actualiza -->
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="icon text-warning"><i class="fas fa-dollar-sign"></i></div>
            <h5>Ingresos Totales</h5>
            <h2 id="totalIncome">COP 0</h2> <!-- Se inicializa en 0 -->
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="icon text-purple"><i class="fas fa-star"></i></div>
            <h5>Solicitudes Totales</h5>
            <h2 id="totalRequests">{{ $totalRequests }}</h2> <!-- Aquí se actualiza -->
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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

    $(document).ready(function() {
        cargarEstadisticas();
    });
</script>
