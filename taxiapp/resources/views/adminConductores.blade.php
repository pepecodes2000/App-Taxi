<h1 class="text-center">Gestión de Conductores</h1>
<p>Aquí puedes administrar los conductores.</p>
<button class="btn btn-success" onclick="abrirAgregarModal()">Agregar Conductor</button>

<!-- Tabla de conductores -->
<table class="table table-striped mt-3">
    <thead>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Estado</th>
            <th>Placa</th>
            <th>Marca</th>
            <th>Año</th>
            <th>Licencia</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="conductoresTabla">
        <!-- Los conductores se cargarán aquí con AJAX -->
    </tbody>
</table>

<!-- Modal para agregar conductor -->
<div id="conductorModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Conductor</h5>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="conductorAgregarForm">
                    <div class="mb-3">
                        <label>ID Usuario</label>
                        <input type="number" id="userIdAgregar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Placa</label>
                        <input type="text" id="licensePlateAgregar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Marca</label>
                        <input type="text" id="brandAgregar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Año</label>
                        <input type="number" id="yearAgregar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Licencia</label>
                        <input type="text" id="driverLicenseAgregar" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="guardarConductor()">Guardar</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar conductor -->
<div id="conductorEditarModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Actualizar Conductor</h5>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="conductorEditarForm">
                    <input type="hidden" id="driverIdEditar">

                    <div class="mb-3">
                        <label>ID</label>
                        <input type="text" id="idEditar" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label>Usuario ID</label>
                        <input type="text" id="userIdEditar" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label>Estado</label>
                        <select id="statusEditar" class="form-control">
                            <option value="available">Disponible</option>
                            <option value="busy">Ocupado</option>
                            <option value="inactive" selected>Inactivo</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Placa</label>
                        <input type="text" id="licensePlateEditar" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Marca</label>
                        <input type="text" id="brandEditar" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Año</label>
                        <input type="number" id="yearEditar" class="form-control" min="1900" required>
                    </div>

                    <div class="mb-3">
                        <label>Licencia</label>
                        <input type="text" id="driverLicenseEditar" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="actualizarConductor()">Actualizar</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery y Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        cargarConductores();
    });

    function cargarConductores() {
        $.get('/api/drivers', function(data) {
            let filas = '';
            data.forEach(driver => {
                filas += `<tr>
                    <td>${driver.id}</td>
                    <td>${driver.user_id}</td>
                    <td>${driver.status}</td>
                    <td>${driver.license_plate}</td>
                    <td>${driver.brand}</td>
                    <td>${driver.year}</td>
                    <td>${driver.driver_license}</td>
                    <td>
                        <button class="btn btn-warning" onclick="abrirEditarModal(${driver.id})">Editar</button>
                        <button class="btn btn-danger" onclick="eliminarConductor(${driver.id})">Eliminar</button>
                    </td>
                </tr>`;
            });
            $('#conductoresTabla').html(filas);
        });
    }

    function abrirAgregarModal() {
        let modal = new bootstrap.Modal(document.getElementById('conductorModal'));
        $('#conductorAgregarForm')[0].reset();
        modal.show();
    }

    // Función para abrir el modal de edición con los datos del conductor
    function abrirEditarModal(id) {
        $.get(`/api/drivers/${id}`, function(driver) {
            $('#driverIdEditar').val(driver.id);
            $('#idEditar').val(driver.id);
            $('#userIdEditar').val(driver.user_id);
            $('#statusEditar').val(driver.status);
            $('#licensePlateEditar').val(driver.license_plate);
            $('#brandEditar').val(driver.brand);
            $('#yearEditar').val(driver.year);
            $('#driverLicenseEditar').val(driver.driver_license);

            let modal = new bootstrap.Modal(document.getElementById('conductorEditarModal'));
            modal.show();
        }).fail(function() {
            alert("Error al obtener los datos del conductor.");
        });
    }

    // Función para actualizar un conductor existente
    function actualizarConductor() {
        let id = $('#driverIdEditar').val(); // Verificar que este ID exista

        if (!id) {
            alert("Error: ID de conductor no válido");
            return;
        }

        let data = {
            user_id: $('#userIdEditar').val(),
            status: $('#statusEditar').val(),
            license_plate: $('#licensePlateEditar').val(),
            brand: $('#brandEditar').val(),
            year: $('#yearEditar').val(),
            driver_license: $('#driverLicenseEditar').val(),
            _method: 'PUT'
        };

        console.log("Enviando datos a:", `/api/drivers/${id}`);
        console.log("Datos:", data);

        $.ajax({
            url: `/api/drivers/${id}`,
            type: 'PUT',
            data: JSON.stringify(data),
            contentType: "application/json",
            success: function(response) {
                alert(response.message); // Mensaje de éxito
                $('#conductorEditarModal').modal('hide'); // Cerrar modal
                cargarConductores();
            },
            error: function(xhr) {
                alert(xhr.responseJSON.message || 'Error al actualizar conductor');
            }
        });
    }


    function guardarConductor() {
        let datos = {
            user_id: $('#userIdAgregar').val(),
            license_plate: $('#licensePlateAgregar').val(),
            brand: $('#brandAgregar').val(),
            year: $('#yearAgregar').val(),
            driver_license: $('#driverLicenseAgregar').val()
        };

        $.ajax({
            url: '/api/drivers',
            type: 'POST',
            data: JSON.stringify(datos),
            contentType: "application/json",
            success: function(response) {
                alert("✅ Conductor agregado correctamente");
                $('#conductorModal').modal('hide');
                cargarConductores();
            },
            error: function(xhr) {
                alert("❌ Error al agregar conductor: " + (xhr.responseJSON?.message || 'Desconocido'));
            }
        });
    }

    function eliminarConductor(id) {
        if (confirm("¿Estás seguro de que quieres eliminar este conductor?")) {
            $.ajax({
                url: `/api/drivers/${id}`,
                type: 'DELETE',
                success: function(response) {
                    alert(response.message);
                    cargarConductores();
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Error al eliminar conductor');
                }
            });
        }
    }
</script>
