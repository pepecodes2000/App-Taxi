console.log("🚀 Service Worker cargado correctamente.");

self.addEventListener("install", (event) => {
    console.log("📥 Instalando Service Worker...");
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    console.log("✅ Activado y controlando clientes.");
    self.clients.claim();
});

let currentDriverId = null;

// Escuchar mensajes de conductor.blade.php
self.addEventListener("message", (event) => {
    if (event.data && event.data.type === "START_UPDATING_LOCATION") {
        currentDriverId = event.data.driverId;
        console.log(`📩 Recibido driverId: ${currentDriverId}`);
        startLocationUpdates();
    }
});

function startLocationUpdates() {
    if (!currentDriverId) {
        console.warn("⚠️ No hay driverId asignado.");
        return;
    }

    setInterval(() => {
        console.log(`📍 Actualizando ubicación del conductor: ${currentDriverId}`);

        self.clients.matchAll().then((clients) => {
            clients.forEach((client) => {
                client.postMessage({ type: "REQUEST_LOCATION", driverId: currentDriverId });
            });
        });

    }, 10000); // Actualizar cada 10 segundos
}

