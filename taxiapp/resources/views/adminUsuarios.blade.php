<h1 class="text-center">Gestión de Usuarios</h1>
<p>Aquí puedes administrar los usuarios.</p>
<button class="btn btn-success" onclick="abrirAgregarModal()">Agregar Usuario</button>

<!-- Tabla de usuarios -->
<table class="table table-striped mt-3">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Celular</th>
            <th>Rol</th>
            <th>Email</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="usuariosTabla">
        <!-- Los usuarios se cargarán aquí con AJAX -->
    </tbody>
</table>

<!-- Modal para agregar usuario -->
<div id="usuarioModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Usuario</h5>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="usuarioAgregarForm">
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" id="nameAgregar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Celular</label>
                        <input type="text" id="cellphoneAgregar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Rol</label>
                        <select id="roleAgregar" class="form-control">
                            <option value="admin">Admin</option>
                            <option value="driver" selected>Conductor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" id="emailAgregar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Contraseña</label>
                        <input type="password" id="passwordAgregar" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="guardarUsuario()">Guardar</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar usuario -->
<div id="usuarioEditarModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Actualizar Usuario</h5>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="usuarioEditarForm">
                    <input type="hidden" id="userIdEditar">
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
                        <input type="text" id="cellphoneEditar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Rol</label>
                        <select id="roleEditar" class="form-control">
                            <option value="admin">Admin</option>
                            <option value="driver">Conductor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" id="emailEditar" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="actualizarUsuario()">Actualizar</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery y Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Cargar usuarios al cargar la página
    $(document).ready(function() {
        cargarUsuarios();
    });

    // Función para cargar usuarios en la tabla
    function cargarUsuarios() {
        $.get('/api/users', function(data) {
            let filas = '';
            data.forEach(user => {
                filas += `<tr>
                    <td>${user.id}</td>
                    <td>${user.name}</td>
                    <td>${user.cellphone}</td>
                    <td>${user.role}</td>
                    <td>${user.email}</td>
                    <td>
                        <button class="btn btn-warning" onclick="abrirEditarModal(${user.id})">Editar</button>
                        <button class="btn btn-danger" onclick="eliminarUsuario(${user.id})">Eliminar</button>
                    </td>
                </tr>`;
            });
            $('#usuariosTabla').html(filas);
        });
    }

    // Función para abrir el modal de agregar usuario
    function abrirAgregarModal() {
        let modal = new bootstrap.Modal(document.getElementById('usuarioModal'));
        $('#usuarioAgregarForm')[0].reset();
        modal.show();
    }

    // Función para abrir el modal de edición con los datos del usuario
    function abrirEditarModal(id) {
        $.get(`/api/users/${id}`, function(user) {
            $('#userIdEditar').val(user.id);
            $('#idEditar').val(user.id);
            $('#nameEditar').val(user.name);
            $('#cellphoneEditar').val(user.cellphone);
            $('#roleEditar').val(user.role);
            $('#emailEditar').val(user.email);
            
            let modal = new bootstrap.Modal(document.getElementById('usuarioEditarModal'));
            modal.show();
        }).fail(function() {
            alert("Error al obtener los datos del usuario.");
        });
    }


    // Función para guardar un usuario nuevo
    function guardarUsuario() {
        let datos = {
            name: $('#nameAgregar').val(),
            cellphone: $('#cellphoneAgregar').val(),
            role: $('#roleAgregar').val(),
            email: $('#emailAgregar').val(),
            password: $('#passwordAgregar').val()
        };

        $.ajax({
            url: '/api/register',
            type: 'POST',
            data: JSON.stringify(datos),
            contentType: "application/json",
            success: function(response) {
                alert("✅ Usuario creado correctamente");
                $('#usuarioModal').modal('hide'); // Cerrar modal
                cargarUsuarios(); // Recargar lista de usuarios
            },
            error: function(xhr) {
                let mensajeError = "Error al crear usuario.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensajeError = xhr.responseJSON.message;
                }
                alert("❌ " + mensajeError);
            }
        });
    }


    // Función para actualizar un usuario existente
    function actualizarUsuario() {
        let id = $('#userIdEditar').val(); // Verificar que este ID exista

        if (!id) {
            alert("Error: ID de usuario no válido");
            return;
        }

        let data = {
            name: $('#nameEditar').val(),
            cellphone: $('#cellphoneEditar').val(),
            role: $('#roleEditar').val(),
            email: $('#emailEditar').val(),
            _method: 'PUT'
        };

        console.log("Enviando datos a:", `/api/users/${id}`);
        console.log("Datos:", data);

        $.ajax({
            url: `/api/users/${id}`,
            type: 'PUT',
            data: JSON.stringify(data),
            contentType: "application/json",
            success: function(response) {
                alert(response.message); // Mensaje de éxito
                $('#modalEditarUsuario').modal('hide'); // Cerrar modal
                cargarUsuarios();; // Recargar la página para ver los cambios
            },
            error: function(xhr) {
                alert(xhr.responseJSON.message || 'Error al actualizar usuario');
            }
        });
    }

    // Función para eliminar usuario
    function eliminarUsuario(id) {
        if (confirm("¿Estás seguro de que quieres eliminar este usuario?")) {
            $.ajax({
                url: `/api/users/${id}`,
                type: 'DELETE',
                success: function(response) {
                    alert(response.message);
                    cargarUsuarios();
                },
                error: function(xhr) {
                    alert(xhr.responseJSON.message);
                }
            });
        }
    }

</script>

