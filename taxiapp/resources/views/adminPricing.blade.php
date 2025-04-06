<div class="container text-center">
    <h2 class="my-4">Configuración de Tarifas</h2>

    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-money-bill-wave"></i> Precio Base</h5>
                    <p class="card-text fs-3" id="display_price_base">--</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-clock"></i> Precio por Minuto</h5>
                    <p class="card-text fs-3" id="display_price_per_minute">--</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-road"></i> Precio por Kilómetro</h5>
                    <p class="card-text fs-3" id="display_price_per_km">--</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Botón único para actualizar todos los precios -->
<div class="text-center mt-3">
    <button class="btn btn-dark" onclick="openPricingModal()">Actualizar Precios</button>
</div>

<!-- Modal para actualizar precios -->
<div class="modal fade" id="pricingModal" tabindex="-1" aria-labelledby="pricingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pricingModalLabel">Actualizar Precios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="price_base" class="form-label">Precio Base</label>
                    <input type="number" class="form-control" id="price_base">
                </div>
                <div class="mb-3">
                    <label for="price_per_minute" class="form-label">Precio por Minuto</label>
                    <input type="number" class="form-control" id="price_per_minute">
                </div>
                <div class="mb-3">
                    <label for="price_per_km" class="form-label">Precio por Kilómetro</label>
                    <input type="number" class="form-control" id="price_per_km">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="savePrices()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    cargarPrecios();
});

function cargarPrecios() {
    $.get('/api/pricing', function(data) {
        $('#display_price_base').text(data.price_base + " COP");
        $('#display_price_per_minute').text(data.price_per_minute + " COP");
        $('#display_price_per_km').text(data.price_per_km + " COP");
    }).fail(function() {
        alert("Error al cargar los precios.");
    });
}

// Abre el modal y carga los valores actuales
function openPricingModal() {
    $.get('/api/pricing', function(data) {
        $('#price_base').val(data.price_base);
        $('#price_per_minute').val(data.price_per_minute);
        $('#price_per_km').val(data.price_per_km);
        $('#pricingModal').modal('show');
    }).fail(function() {
        alert("Error al cargar los precios.");
    });
}

// Guarda los precios actualizados
function savePrices() {
    let formData = {
        price_base: $('#price_base').val(),
        price_per_minute: $('#price_per_minute').val(),
        price_per_km: $('#price_per_km').val()
    };

    $.ajax({
        url: '/api/pricing',  // Asegúrate de que esta ruta esté configurada en Laravel
        type: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"  // Si usas Laravel Blade, reemplázalo por una variable JS si es necesario
        },
        success: function(data) {
            alert(data.message);
            $('#display_price_base').text(formData.price_base + " COP");
            $('#display_price_per_minute').text(formData.price_per_minute + " COP");
            $('#display_price_per_km').text(formData.price_per_km + " COP");
            $('#pricingModal').modal('hide');
        },
        error: function() {
            alert("Error al actualizar los precios.");
        }
    });
}
</script>
