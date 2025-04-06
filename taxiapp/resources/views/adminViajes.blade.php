<h1 class="text-center">Solicitudes de Taxi</h1>
<p>Aquí puedes ver todas las solicitudes de taxi.</p>

<!-- Tabla de solicitudes -->
<table class="table table-striped mt-3">
    <thead>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Conductor</th>
            <th>Estado</th>
            <th>Ubicacion de solicitud</th>
        </tr>
    </thead>
    <tbody id="solicitudesTabla">
        <!-- Las solicitudes se cargarán aquí con AJAX -->
    </tbody>
</table>

<!-- jQuery y Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        cargarSolicitudes();
    });

    function cargarSolicitudes() {
        $.get('/api/ride_requests', function(data) {
            let filas = '';
            data.forEach(solicitud => {
                filas += `<tr>
                    <td>${solicitud.id}</td>
                    <td>${solicitud.customer_id}</td>
                    <td>${solicitud.driver_id ? solicitud.driver_id : 'Sin asignar'}</td>
                    <td>${solicitud.status}</td>
                    <td>${solicitud.origin_location}</td>
                </tr>`;
            });
            $('#solicitudesTabla').html(filas);
        }).fail(function() {
            alert("Error al cargar las solicitudes.");
        });
    }
</script>
