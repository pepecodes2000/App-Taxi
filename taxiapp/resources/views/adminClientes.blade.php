<h1 class="text-center">Gestión de Clientes</h1>
<p>Aquí puedes administrar los clientes.</p>
<button class="btn btn-success" onclick="abrirAgregarModal()">Agregar Cliente</button>

<!-- Tabla de clientes -->
<table class="table table-striped mt-3">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Celular</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="clientesTabla">
        <!-- Los clientes se cargarán aquí con AJAX -->
    </tbody>
</table>

<!-- Modal para agregar cliente -->
<div id="clienteModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Cliente</h5>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="clienteAgregarForm">
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" id="nameAgregar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Celular</label>
                        <input type="text" id="phoneAgregar" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="guardarCliente()">Guardar</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar cliente -->
<div id="clienteEditarModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Actualizar Cliente</h5>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="clienteEditarForm">
                    <input type="hidden" id="clienteIdEditar">
                    <div class="mb-3">
                        <label>ID</label>
                        <input type="text" id="idEditar" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" id="nameEditar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Celular</label>
                        <input type="text" id="phoneEditar" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="actualizarCliente()">Actualizar</button>
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
        cargarClientes();
    });

    function cargarClientes() {
        $.get('/api/customers', function(data) {
            let filas = '';
            data.forEach(cliente => {
                filas += `<tr>
                    <td>${cliente.id}</td>
                    <td>${cliente.name}</td>
                    <td>${cliente.phone}</td>
                    <td>
                        <button class="btn btn-warning" onclick="abrirEditarModal(${cliente.id})">Editar</button>
                        <button class="btn btn-danger" onclick="eliminarCliente(${cliente.id})">Eliminar</button>
                    </td>
                </tr>`;
            });
            $('#clientesTabla').html(filas);
        });
    }

    function abrirAgregarModal() {
        let modal = new bootstrap.Modal(document.getElementById('clienteModal'));
        $('#clienteAgregarForm')[0].reset();
        modal.show();
    }

    function abrirEditarModal(id) {
        $.get(`/api/customers/${id}`, function(cliente) {
            $('#clienteIdEditar').val(cliente.id);
            $('#idEditar').val(cliente.id);
            $('#nameEditar').val(cliente.name);
            $('#phoneEditar').val(cliente.phone);
            
            let modal = new bootstrap.Modal(document.getElementById('clienteEditarModal'));
            modal.show();
        }).fail(function() {
            alert("Error al obtener los datos del cliente.");
        });
    }

    function guardarCliente() {
        let datos = {
            name: $('#nameAgregar').val(),
            phone: $('#phoneAgregar').val()
        };

        $.ajax({
            url: '/api/customers',
            type: 'POST',
            data: JSON.stringify(datos),
            contentType: "application/json",
            success: function(response) {
                alert("✅ Cliente creado correctamente");
                $('#clienteModal').modal('hide');
                cargarClientes();
            },
            error: function(xhr) {
                let mensajeError = "Error al crear cliente.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensajeError = xhr.responseJSON.message;
                }
                alert("❌ " + mensajeError);
            }
        });
    }

    function actualizarCliente() {
        let id = $('#clienteIdEditar').val();

        if (!id) {
            alert("Error: ID de cliente no válido");
            return;
        }

        let data = {
            name: $('#nameEditar').val(),
            phone: $('#phoneEditar').val(),
            _method: 'PUT'
        };

        $.ajax({
            url: `/api/customers/${id}`,
            type: 'PUT',
            data: JSON.stringify(data),
            contentType: "application/json",
            success: function(response) {
                alert(response.message);
                $('#clienteEditarModal').modal('hide');
                cargarClientes();
            },
            error: function(xhr) {
                alert(xhr.responseJSON.message || 'Error al actualizar cliente');
            }
        });
    }

    function eliminarCliente(id) {
        if (confirm("¿Estás seguro de que quieres eliminar este cliente?")) {
            $.ajax({
                url: `/api/customers/${id}`,
                type: 'DELETE',
                success: function(response) {
                    alert(response.message);
                    cargarClientes();
                },
                error: function(xhr) {
                    alert(xhr.responseJSON.message);
                }
            });
        }
    }
</script>
