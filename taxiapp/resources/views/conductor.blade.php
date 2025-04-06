<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Conductor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Estilos para el sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #343a40;
            padding-top: 20px;
            transition: 0.3s;
        }

        .sidebar a {
            padding: 15px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: block;
        }

        .sidebar a:hover {
            background: #495057;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
        }

        /* Estilos para hacer el diseño responsivo */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .content {
                margin-left: 0;
            }
        }

        /* Contenedor del mapa */
        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
        }
        #menu-options {
            transition: max-height 0.5s ease-out;
            overflow: hidden;
        }

        #menu-options.collapsed {
            max-height: 0;
        }

        #menu-options.expanded {
            max-height: 200px; /* ajusta el valor según sea necesario */
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&callback=initMap" async defer></script>

</head>
<body>
    <div class="sidebar">
        <h4 class="text-center text-white" id="menu-toggle">🚖 Conductor</h4>
        <div id="menu-options" class="collapsed">
            <a href="#"><i class="fas fa-car"></i> Mis Viajes</a>
            <a href="#"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </div>

    <div class="content">
    <h2 class="text-dark text-center">Bienvenido, <span id="driverName">Conductor</span>, <span id="driverID">ID</span></h2>
    
    <div class="row justify-content-center text-center">
        <div class="col-md-8 d-flex align-items-center gap-3 flex-wrap justify-content-center p-2">
            
            <!-- Selector de conductor más pequeño -->
            <div class="d-flex align-items-center gap-2">
                <select id="driverSelect" class="form-select form-select-sm" style="width: 130px;">
                    <option value="">Seleccione un conductor</option>
                </select>
            </div>

            <!-- Indicador de estado del conductor -->
            <div class="status-indicator">
                <span id="driverStatus" class="badge bg-success">🟢 Available</span>
            </div>

            <!-- Información del vehículo y placa -->
            <h5 class="mb-0">🚗 <span id="driverCar">Modelo</span></h5>
            <h5 class="mb-0">🔹 <span id="driverPlate">Placa</span></h5>

        </div>
    </div>

        <!-- Contenedor del mapa -->
        <div id="map" class="map-container"></div>
    </div>

    <!-- Modal para gestionar la carrera -->
    <div id="rideModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>🚖 Carrera en progreso</h3>
            <p>Tiempo transcurrido: <span id="timeElapsed">0</span> min</p>
            <p>Distancia recorrida: <span id="distanceTraveled">0</span> km</p>
            <p>Precio total: COP<span id="totalPrice">0.00</span></p>

            <button id="finishRide">Finalizar Carrera</button>
        </div>
    </div>

    <script>
        let map, marker, watchID, lastLocation = { lat: null, lng: null };

        function initMap() {
            if (navigator.geolocation) {
                map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 18,
                    center: { lat: 0, lng: 0 }
                });

                marker = new google.maps.Marker({
                    position: { lat: 0, lng: 0 },
                    map: map,
                    title: "Tu ubicación",
                    icon: {
                        url: "https://maps.google.com/mapfiles/kml/shapes/cabs.png",
                        scaledSize: new google.maps.Size(30, 30)
                    }
                });

                // ✅ Iniciar la actualización automática de ubicación
                startTracking();
            } else {
                alert("❌ Tu navegador no soporta geolocalización.");
            }
        }

        function startTracking() {
            watchID = navigator.geolocation.watchPosition(position => {
                let driverId = document.getElementById("driverID").innerText; // ID del conductor
                let newLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                // 📌 Evitar actualizaciones innecesarias si la ubicación no cambió significativamente
                if (!hasMovedSignificantly(newLocation)) {
                    return;
                }

                // ✅ Actualizar marcador y centrar el mapa
                marker.setPosition(newLocation);
                map.setCenter(newLocation);

                // ✅ Enviar actualización al servidor
                if (driverId && driverId !== "ID") {
                    updateDriverLocation(driverId, newLocation);
                }
            }, error => {
                console.warn("⚠️ No se pudo obtener la ubicación.", error);
            }, {
                enableHighAccuracy: true, // 📌 Ubicación precisa
                timeout: 10000,           // ⏳ Espera hasta 10 segundos
                maximumAge: 5000          // 📌 Usa datos de ubicación recientes hasta 5 segundos
            });
        }

        function hasMovedSignificantly(newLocation) {
            if (lastLocation.lat === null || lastLocation.lng === null) {
                lastLocation = newLocation;
                return true;
            }

            // 📌 Calcula la distancia entre la última y la nueva ubicación
            let distance = getDistance(lastLocation, newLocation);
            
            // 📌 Solo actualizar si el conductor se ha movido al menos 10 metros
            if (distance >= 0.01) { // 10 metros aproximadamente
                lastLocation = newLocation;
                return true;
            }
            return false;
        }

        function getDistance(loc1, loc2) {
            let R = 6371; // Radio de la Tierra en km
            let dLat = (loc2.lat - loc1.lat) * (Math.PI / 180);
            let dLon = (loc2.lng - loc1.lng) * (Math.PI / 180);
            let a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(loc1.lat * (Math.PI / 180)) * Math.cos(loc2.lat * (Math.PI / 180)) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
            let c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c; // Distancia en km
        }

        function updateDriverLocation(driverId, location) {
            fetch(`/api/drivers/${driverId}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    //"Authorization": "Bearer " + localStorage.getItem("token") // Si usas autenticación
                },
                body: JSON.stringify({
                    latitude: location.lat,
                    longitude: location.lng
                })
            })
            .then(response => response.json())
            .then(data => console.log(`📍 Ubicación de conductor ${driverId} actualizada`, data))
            .catch(error => console.error("❌ Error al actualizar ubicación:", error));
        }

        // Simulación de datos del conductor obtenidos del backend
        document.addEventListener("DOMContentLoaded", function() {
            
            const driverSelect = document.getElementById("driverSelect");
            const driverID = document.getElementById("driverID");
            const driverName = document.getElementById("driverName");
            const driverCar = document.getElementById("driverCar");
            const driverPlate = document.getElementById("driverPlate");
            const driverStatus = document.getElementById("driverStatus");

            // Obtener la lista de conductores y llenar el select
            fetch("/api/drivers")
                .then(response => response.json())
                .then(drivers => {
                    drivers.forEach(driver => {
                        let option = document.createElement("option");
                        option.value = driver.id;
                        option.textContent = driver.name; // Mostrar el nombre del conductor
                        driverSelect.appendChild(option);
                    });
                })
                .catch(error => console.error("Error cargando conductores:", error));

            // Evento para actualizar la info cuando se selecciona un conductor
            driverSelect.addEventListener("change", function () {
                let driverId = this.value;
                if (driverId) {
                    fetch(`/api/drivers/${driverId}`) // Ruta para obtener info de un conductor específico
                        .then(response => response.json())
                        .then(driver => {
                            driverID.innerText = driver.id;
                            driverName.innerText = driver.name;
                            driverCar.innerText = driver.brand;
                            driverPlate.innerText = driver.license_plate;

                            // Actualizar estado del conductor
                            if (driver.status === "available") {
                                driverStatus.innerText = "🟢 Available";
                                driverStatus.classList.remove("bg-danger");
                                driverStatus.classList.add("bg-success");
                            } else {
                                driverStatus.innerText = "🔴 Busy";
                                driverStatus.classList.remove("bg-success");
                                driverStatus.classList.add("bg-danger");
                            }
                        })
                        .catch(error => console.error("Error cargando datos del conductor:", error));
                } else {
                    driverName.innerText = "Conductor";
                    driverCar.innerText = "";
                    driverPlate.innerText = "";
                    driverStatus.innerText = "🟢 Available";
                    driverStatus.classList.remove("bg-danger");
                    driverStatus.classList.add("bg-success");
                }
            });

        });

        document.getElementById('menu-toggle').addEventListener('click', function() {
            var menuOptions = document.getElementById('menu-options');
            if (menuOptions.classList.contains('collapsed')) {
                menuOptions.classList.remove('collapsed');
                menuOptions.classList.add('expanded');
            } else {
                menuOptions.classList.remove('expanded');
                menuOptions.classList.add('collapsed');
            }
        });


        document.addEventListener("DOMContentLoaded", function () {
            let baseFare = 0, pricePerMinute = 0, pricePerKm = 0;

            // Fetch pricing data from the API
            fetch("/api/pricing")
                .then(response => response.json())
                .then(data => {
                    baseFare = data.price_base || 0;
                    pricePerMinute = data.price_per_minute || 0;
                    pricePerKm = data.price_per_km || 0;
                })
                .catch(() => {
                    console.error("Error fetching pricing data");
                });

            const driverStatus = document.getElementById("driverStatus");
            const rideModal = document.getElementById("rideModal");
            const timeElapsed = document.getElementById("timeElapsed");
            const distanceTraveled = document.getElementById("distanceTraveled");
            const totalPrice = document.getElementById("totalPrice");
            const finishRide = document.getElementById("finishRide");

            let isRiding = false;
            let startTime, interval, watchId;
            let distance = 0;
            let lastPosition = null;

            driverStatus.addEventListener("click", function () {
                if (!isRiding) {
                    isRiding = true;
                    driverStatus.textContent = "🔴 Busy";
                    driverStatus.classList.remove("bg-success");
                    driverStatus.classList.add("bg-danger");

                    startTime = Date.now();
                    distance = 0;
                    timeElapsed.textContent = "0";
                    distanceTraveled.textContent = "0.00";
                    totalPrice.textContent = "0.00";

                    rideModal.style.display = "block";
                    interval = setInterval(updateRideTime, 30000); // Actualiza 30seg

                    // Obtener ubicación en tiempo real
                    if (navigator.geolocation) {
                        watchId = navigator.geolocation.watchPosition(updateDistance, 
                            (error) => console.error("Error obteniendo ubicación:", error), 
                            { enableHighAccuracy: true }
                        );
                    }
                }
            });

            function updateRideTime() {
                let elapsedMinutes = Math.floor((Date.now() - startTime) / 60000);
                timeElapsed.textContent = elapsedMinutes;
                calculateTotalPrice();
            }

            function updateDistance(position) {
                if (lastPosition) {
                    let lat1 = lastPosition.latitude;
                    let lon1 = lastPosition.longitude;
                    let lat2 = position.coords.latitude;
                    let lon2 = position.coords.longitude;

                    let movedDistance = calculateDistance(lat1, lon1, lat2, lon2);
                    distance += movedDistance;
                    distanceTraveled.textContent = distance.toFixed(2);
                }
                lastPosition = position.coords;
                calculateTotalPrice();
            }

            function calculateTotalPrice() {
                let elapsedMinutes = Math.floor((Date.now() - startTime) / 60000);

                let base = parseFloat(baseFare) || 0;
                let perMinute = parseFloat(pricePerMinute) || 0;
                let perKm = parseFloat(pricePerKm) || 0;
                let dist = parseFloat(distance) || 0;

                let price = base + (perMinute * elapsedMinutes) + (perKm * dist);
                
                totalPrice.textContent = price.toFixed(2);
            }

            function calculateDistance(lat1, lon1, lat2, lon2) {
                let R = 6371; // Radio de la Tierra en km
                let dLat = (lat2 - lat1) * (Math.PI / 180);
                let dLon = (lon2 - lon1) * (Math.PI / 180);
                let a = 
                    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * (Math.PI / 180)) * Math.cos(lat2 * (Math.PI / 180)) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
                let c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c; // Distancia en km
            }

            finishRide.addEventListener("click", function () {
                clearInterval(interval);
                if (watchId) {
                    navigator.geolocation.clearWatch(watchId);
                }
                rideModal.style.display = "none";
                alert(`💰 Total Fare: $${totalPrice.textContent}`);

                isRiding = false;
                driverStatus.textContent = "🟢 Available";
                driverStatus.classList.remove("bg-danger");
                driverStatus.classList.add("bg-success");
            });
        });

        let wakeLock = null;

        // ✅ Función para activar Wake Lock (Evita que la pantalla se apague)
        async function requestWakeLock() {
            if ('wakeLock' in navigator) {
                try {
                    wakeLock = await navigator.wakeLock.request('screen');
                    console.log('🔒 Wake Lock activado.');
                    wakeLock.addEventListener('release', () => {
                        console.log('🔓 Wake Lock liberado.');
                    });
                } catch (err) {
                    console.error('❌ Error con Wake Lock:', err);
                }
            } else {
                console.warn('⚠️ Wake Lock no está soportado en este navegador.');
            }
        }

        // ✅ Activar Wake Lock cuando inicia el tracking
        function startTracking() {
            requestWakeLock(); // 🔒 Evita que la pantalla se apague

            watchID = navigator.geolocation.watchPosition(position => {
                let driverId = document.getElementById("driverID").innerText; // ID del conductor
                let newLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                if (!hasMovedSignificantly(newLocation)) {
                    return;
                }

                marker.setPosition(newLocation);
                map.setCenter(newLocation);

                if (driverId && driverId !== "ID") {
                    updateDriverLocation(driverId, newLocation);
                }
            }, error => {
                console.warn("⚠️ No se pudo obtener la ubicación.", error);
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 5000
            });
        }

        // ✅ Función para liberar Wake Lock cuando se detiene el tracking
        function stopTracking() {
            if (watchID) {
                navigator.geolocation.clearWatch(watchID);
                console.log("📍 Seguimiento detenido.");
            }
            if (wakeLock) {
                wakeLock.release();
                wakeLock = null;
                console.log("🔓 Wake Lock liberado.");
            }
        }

    </script>
</body>
</html>
