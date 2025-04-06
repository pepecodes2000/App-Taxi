<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Customer;
use App\Models\RideRequest;
use App\Models\Driver;
use App\Models\Pricing;
use Carbon\Carbon; // Para facilitar el manejo de tiempo.

class WhatsAppController extends Controller
{
    // Función para validar el webhook con Meta
    public function token(Request $request)
    {
        if ($request->isMethod('get')) {
            if ($request->hub_mode === 'subscribe' && 
                $request->hub_verify_token === env('META_WHATSAPP_TOKEN')) {
                Log::info('Webhook verificado correctamente.');
                return response($request->hub_challenge, 200);
            } else {
                Log::error('Fallo en la verificación del webhook.');
                return response('Forbidden', 403);
            }
        }
    }

    // Verifica si el cliente está registrado en la base de datos.
    private function clientExist($from)
    {
        return Customer::where('phone', $from)->exists();
    }

    // Función principal que escucha y procesa mensajes
    public function listen(Request $request)
    {
        $data = $request->json()->all();

        // Verificar si hay mensajes
        if (!isset($data['entry'][0]['changes'][0]['value']['messages'])) {
            Log::warning("⚠️ No hay mensajes en la solicitud.");
            return response()->json(['status' => 'no_message']);
        }

        $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $from = $message['from']; // Número del usuario
        $type = $message['type'] ?? '';

        Log::info("📩 Mensaje recibido de $from, tipo: $type", $message);

        // Llamamos la función según el tipo de mensaje
        if ($type === "text") {
            $this->handleTextMessage($from, $message);
        } elseif ($type === "interactive") {
            $this->handleInteractiveMessage($from, $message);
        } elseif ($type === "location") {
            $this->handleLocationMessage($from, $message);
        } else {
            Log::warning("⚠️ Tipo de mensaje no manejado: $type");
        }

        return response()->json(['status' => 'success']);
    }

    // ✅ Función para manejar mensajes de texto
    // Función para manejar mensajes de texto
    private function handleTextMessage($from, $message)
    {
        $text = strtolower(trim($message['text']['body'] ?? ''));
        Log::info("📩 Texto recibido: $text");

        // Buscar la solicitud activa de viaje
        $rideRequest = RideRequest::where(function ($query) use ($from) {
            $query->whereHas('customer', function ($q) use ($from) {
                $q->where('phone', $from);
            })->orWhereHas('driver', function ($q) use ($from) {
                $q->where('cellphone', $from);
            });
        })->whereIn('status', ['asignado', 'llegó', 'en_progreso'])->first();

        if ($rideRequest) {
            $this->handleRideChat($from, $rideRequest, $text);
            return;
        }

        // Si no hay viaje activo, mostrar botones interactivos
        $this->sendInteractiveButtons($from);
    }

    // Función para manejar el chat entre conductor y cliente
    private function handleRideChat($from, $rideRequest, $text)
    {
        if ($rideRequest->customer->phone == $from) {
            $recipient = $rideRequest->driver->cellphone;
            $prefix = " 📩 Mensaje de usuario: ";
        } elseif ($rideRequest->driver->cellphone == $from) {
            $recipient = $rideRequest->customer->phone;
            $prefix = " 📩 Mensaje de conductor: ";
        } else {
            return;
        }

        $this->sendMessage($recipient, $prefix . $text);
    }

    // ✅ Función para manejar botones interactivos
    private function handleInteractiveMessage($from, $message)
    {
        $buttonId = $message['interactive']['button_reply']['id'] ?? '';

        Log::info("🔘 Botón presionado: $buttonId");

        if ($buttonId === "request_vehiculo") {
            if ($this->clientExist($from)) {
                $this->requestLocation($from);
            } else {
                $this->sendMessage($from, "❌ No eres un cliente registrado. Puedes contactarnos al +573173981461 📞.");
            }
        } elseif ($buttonId === "provide_contact") {
            $this->sendMessage($from, "Puedes contactarnos al +573173981461 📞.");
        } elseif (str_starts_with($buttonId, "accept_")) {
            $rideRequestId = explode("_", $buttonId)[1];
            $rideRequest = RideRequest::find($rideRequestId);
        
            if (!$rideRequest || $rideRequest->status !== 'pendiente') {
                $this->sendMessage($from, "❌ Lo sentimos, esta solicitud ya fue asignada.");
                return;
            }
        
            // Asignar el conductor
            $driver = Driver::where('cellphone', $from)->first();
            if (!$driver) {
                $this->sendMessage($from, "⚠️ No estás registrado como conductor.");
                return;
            }
        
            $rideRequest->update(['driver_id' => $driver->id, 'status' => 'asignado']);
        
            // Notificar al cliente
            $customer = Customer::find($rideRequest->customer_id);
            if ($customer) {
                $message = trim("🚖 Tu conductor {$driver->name} está en camino. 🚗 Modelo: {$driver->brand} Placa: {$driver->license_plate}\n📍 Espera en tu Ubicación. Que tengas un feliz viaje con Automobile");
                $this->sendMessage($customer->phone, $message);
            }

            // Guardar la conversación entre ambos
            $this->sendMessage($customer->phone, "💬 Puedes enviar mensajes aquí y el bot se los reenviará a tu conductor.");
            $this->sendMessage($from, "💬 Puedes enviar mensajes aquí y el bot se los reenviará a tu pasajero.");

            // Mensaje interactivo con botón "Llegué"
            $interactiveMessage = [
                "messaging_product" => "whatsapp",
                "to" => $from,
                "type" => "interactive",
                "interactive" => [
                    "type" => "button",
                    "body" => ["text" => "🚖 Has aceptado el viaje. Dirígete a la ubicación del cliente."],
                    "action" => [
                        "buttons" => [
                            [
                                "type" => "reply",
                                "reply" => [
                                    "id" => "arrived_{$rideRequestId}",
                                    "title" => "🛬 Llegué"
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $this->sendToWhatsApp($interactiveMessage);

        } elseif (str_starts_with($buttonId, "arrived_")) {
            $rideRequestId = explode("_", $buttonId)[1];
            $this->handleDriverArrival($from, $rideRequestId);
        }elseif (str_starts_with($buttonId, "start_")) {
            $rideRequestId = explode("_", $buttonId)[1];
            $this->handleStartRide($from, $rideRequestId);
        } elseif (str_starts_with($buttonId, "finish_")) {
            $rideRequestId = explode("_", $buttonId)[1];
            $this->handleFinishRide($from, $rideRequestId);
        } elseif (str_starts_with($buttonId, "cancel_")) {
            $rideRequestId = explode("_", $buttonId)[1];
            $this->handleCancelRide($from, $rideRequestId);
        }
    }

    // ✅ Función para manejar ubicación y asignar solicitud
    private function handleLocationMessage($from, $message)
    {
        $latitude = $message['location']['latitude'] ?? null;
        $longitude = $message['location']['longitude'] ?? null;
        $customer = Customer::where('phone', $from)->first();

        if ($latitude && $longitude && $customer) {
            $rideRequest = RideRequest::create([
                'customer_id' => $customer->id,
                'driver_id' => null,
                'status' => 'pendiente',
                'origin_location' => json_encode(['lat' => $latitude, 'lng' => $longitude]),
                'destination_location' => null,
                'distance_km' => null,
                'service_time_min' => null,
                'total_price' => null,
            ]);

            Log::info("📍 Nueva solicitud creada: Cliente {$customer->phone}, Ubicación: Lat {$latitude}, Long {$longitude}");

            // Enviar mensaje de confirmación al cliente
            $this->sendMessage($from, "✅ Su solicitud está en proceso, buscando un conductor 🚗 disponible.");
            
            // Enviar solicitud a conductores disponibles
            $this->notifyAvailableDrivers($latitude, $longitude, $rideRequest->id, $from);
        } else {
            Log::error("❌ Error: Mensaje de ubicación sin coordenadas.");
            $this->sendMessage($from, "⚠️ Hubo un problema con tu ubicación, intenta de nuevo o comunicate con nosotros al +573173981461 📞.");
        } 
    }

    // ✅ Función para notificar conductores cercanos (radio de 2 km)
    private function notifyAvailableDrivers($latitude, $longitude, $rideRequestId, $from)
    {
        $radius = 2; // 2 km
        $drivers = Driver::where('status', 'available')->get()->filter(function ($driver) use ($latitude, $longitude, $radius) {
            return $this->haversineGreatCircleDistance($latitude, $longitude, $driver->latitude, $driver->longitude) <= $radius;
        });

        $driversCount = $drivers->count(); // Contar conductores dentro del radio
        Log::info("🚖 Conductores disponibles en un radio de {$radius} km: {$driversCount}");

        if ($driversCount === 0) {
            Log::warning("⚠️ No hay conductores disponibles en el área.");

            // Cambiar estado de la solicitud a "sin_conductor"
            RideRequest::where('id', $rideRequestId)->update(['status' => 'sin_conductor']);

            // Enviar mensaje al cliente disculpándose
            $this->sendMessage($from, "⚠️ Lo sentimos, en este momento no hay conductores disponibles en su zona. Intente nuevamente en unos minutos. 🙏");

            return; // Salir de la función porque no hay conductores
        }

        foreach ($drivers as $driver) {
            // 📍 Enviar la ubicación del cliente
            $locationMessage = [
                "messaging_product" => "whatsapp",
                "to" => $driver->cellphone,
                "type" => "location",
                "location" => [
                    "latitude" => $latitude,
                    "longitude" => $longitude,
                    "name" => "Ubicación del Cliente",
                    "address" => "Punto de recogida"
                ]
            ];

            $this->sendToWhatsApp($locationMessage);

            // 🚖 Enviar mensaje con botón para aceptar la solicitud
            $interactiveMessage = [
                "messaging_product" => "whatsapp",
                "to" => $driver->cellphone,
                "type" => "interactive",
                "interactive" => [
                    "type" => "button",
                    "body" => [
                        "text" => "🚖 Nueva solicitud disponible.📍"
                    ],
                    "action" => [
                        "buttons" => [
                            [
                                "type" => "reply",
                                "reply" => [
                                    "id" => "accept_{$rideRequestId}",
                                    "title" => "✅ Aceptar"
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $this->sendToWhatsApp($interactiveMessage);
        }
    }

    // ✅ Función para calcular la distancia entre dos coordenadas (Haversine)
    private function haversineGreatCircleDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radio de la Tierra en km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    // ✅ Función para manejar la llegada del conductor
    private function handleDriverArrival($driverPhone, $rideRequestId)
    {
        RideRequest::where('id', $rideRequestId)->update(['status' => 'llegó']);
        
        // Obtener cliente
        $rideRequest = RideRequest::find($rideRequestId);
        $customerPhone = $rideRequest->customer->phone;
        
        // Notificar al cliente que el conductor ha llegado
        $this->sendMessage($customerPhone, "🚖 Su conductor ha llegado al punto de recogida. Por favor, salga al encuentro. 🏡");
        
        // Enviar botones al conductor para iniciar o cancelar el viaje
        $interactiveMessage = [
            "messaging_product" => "whatsapp",
            "to" => $driverPhone,
            "type" => "interactive",
            "interactive" => [
                "type" => "button",
                "body" => [
                    "text" => "🚖 ¿Listo para iniciar el viaje?"
                ],
                "action" => [
                    "buttons" => [
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "start_{$rideRequestId}",
                                "title" => "🚀 Iniciar Viaje"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "cancel_{$rideRequestId}",
                                "title" => "❌ Cancelar Viaje"
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->sendToWhatsApp($interactiveMessage);
    }

    // ✅ Función para Inciar el viaje
    private function handleStartRide($from, $rideRequestId)
    {
        $rideRequest = RideRequest::find($rideRequestId);

        if (!$rideRequest || $rideRequest->status !== 'llegó') {
            $this->sendMessage($from, "❌ No se puede iniciar este viaje.");
            return;
        }

        // Guardar la hora de inicio en la variable de sesión (o en caché)
        session(["ride_start_time_{$rideRequestId}" => now()]);

        // Actualizar estado del viaje
        $rideRequest->update(['status' => 'en_progreso']);

        // Notificar al cliente
        $customer = Customer::find($rideRequest->customer_id);
        if ($customer) {
            $this->sendMessage($customer->phone, "🚕 ¡Tu viaje ha comenzado! Disfruta del trayecto.");
        }

        // Enviar mensaje al conductor con botón de finalizar viaje
        $this->sendInteractiveButton($from, "⏳ Viaje en curso. Presiona 'Finalizar' cuando llegues al destino.", [
            ['id' => "finish_{$rideRequest->id}", 'title' => "Finalizar Viaje"]
        ]);
    }
        
    // ✅ Finalizar viaje
    private function handleFinishRide($from, $rideRequestId)
    {
        $rideRequest = RideRequest::find($rideRequestId);

        if (!$rideRequest || $rideRequest->status !== 'en_progreso') {
            $this->sendMessage($from, "❌ No se puede finalizar este viaje.");
            return;
        }

        // ✅ Obtener el cliente y el conductor
        $client = Customer::find($rideRequest->customer_id);
        $driver = Driver::find($rideRequest->driver_id);

        if (!$client || !$driver) {
            Log::error("❌ Error: No se encontró el cliente o el conductor.");
            $this->sendMessage($from, "❌ Error en el sistema. No se encontró el usuario.");
            return;
        }

        // ✅ Decodificar correctamente la ubicación de origen
        $origin = json_decode(stripslashes($rideRequest->origin_location), true);
        if (!is_array($origin) || !isset($origin['lat'], $origin['lng'])) {
            Log::error("❌ Error: No se pudo obtener la ubicación de origen correctamente.");
            $this->sendMessage($from, "❌ Error al obtener la ubicación de inicio del viaje.");
            return;
        }

        $originLat = floatval($origin['lat']);
        $originLng = floatval($origin['lng']);

        // ✅ Obtener destino desde la última ubicación del conductor
        $destinationLat = floatval($driver->latitude ?? 0);
        $destinationLng = floatval($driver->longitude ?? 0);

        // 📌 Registrar en logs las coordenadas recibidas
        Log::info("📍 Origen: lat={$originLat}, lon={$originLng}");
        Log::info("📍 Destino: lat={$destinationLat}, lon={$destinationLng}");

        // 📌 Verificar si las coordenadas son válidas antes de calcular la distancia
        if ($originLat == 0 || $originLng == 0 || $destinationLat == 0 || $destinationLng == 0) {
            Log::error("❌ Error: Coordenadas inválidas: lat1=$originLat, lon1=$originLng, lat2=$destinationLat, lon2=$destinationLng");
            $this->sendMessage($from, "❌ Error al calcular la distancia del viaje.");
            return;
        }

        // ✅ Calcular la distancia real usando la fórmula de Haversine
        $distanceKm = $this->haversineDistance($originLat, $originLng, $destinationLat, $destinationLng);

        // 📌 Si la distancia es menor o igual a 0, establecer un valor mínimo de 1 km
        if ($distanceKm <= 0) {
            Log::warning("⚠️ Distancia calculada 0 km. Se usará 1 km por defecto.");
            $distanceKm = 1;
        }

        // ✅ Calcular tiempo del servicio en minutos
        $startTime = strtotime($rideRequest->created_at);
        $endTime = strtotime(now());
        $serviceTimeMin = max(1, round(($endTime - $startTime) / 60)); // Mínimo 1 minuto

        Log::info("⏳ Tiempo del viaje: {$serviceTimeMin} min");

        // ✅ Calcular precio del viaje
        $totalPrice = ceil($this->calculatePrice($distanceKm, $serviceTimeMin));

        // ✅ Guardar la distancia, el tiempo y el precio en la BD
        $rideRequest->update([
            'distance_km' => $distanceKm,
            'service_time_min' => $serviceTimeMin,
            'total_price' => $totalPrice,
            'status' => 'finalizado'
        ]);

        Log::info("✅ Viaje finalizado: {$rideRequest->distance_km} km, {$rideRequest->service_time_min} min, \${$rideRequest->total_price}");

        // ✅ Notificar al cliente sobre el costo del viaje
        $clientMessage = "🚖 *Resumen del Viaje*\n"
            . "📍 Distancia: {$rideRequest->distance_km} km\n"
            . "⏳ Tiempo: {$rideRequest->service_time_min} min\n"
            . "💰 Costo total: \${$rideRequest->total_price}\n\n"
            . "Gracias por viajar con nosotros. ¡Esperamos verte pronto!";

        $this->sendMessage($client->phone, $clientMessage);

        // ✅ Notificar al conductor sobre el pago
        $driverMessage = "🛠 *Detalles del Viaje Finalizado*\n"
            . "📍 Distancia recorrida: {$rideRequest->distance_km} km\n"
            . "⏳ Tiempo total: {$rideRequest->service_time_min} min\n"
            . "💰 Total cobrado: \${$rideRequest->total_price}\n\n"
            . "Viaje finalizado. Gracias por tu conducción segura.";

        $this->sendMessage($driver->cellphone, $driverMessage);
    }

    
    /**
     * 📌 Función para calcular la distancia entre dos puntos usando la fórmula de Haversine
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radio de la Tierra en kilómetros
    
        // Convertir grados a radianes
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
    
        // Diferencia de coordenadas
        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;
    
        // Fórmula de Haversine
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1) * cos($lat2) *
            sin($dLon / 2) * sin($dLon / 2);
    
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
        return $earthRadius * $c; // Distancia en km
    }
    

    // ✅ Calcular precio
    private function calculatePrice($distanceKm, $serviceTimeMin)
    {
        Log::info("🔢 Iniciando cálculo de precio con distancia: $distanceKm km y tiempo: $serviceTimeMin min");

        // ✅ Obtener tarifas desde BD
        $pricing = Pricing::first();

        if (!$pricing) {
            Log::error("⚠️ No se encontraron tarifas en la BD. Usando valores por defecto.");
            $baseFare = 1.00;
            $pricePerKm = 0.50;
            $pricePerMin = 0.10;
        } else {
            $baseFare = $pricing->price_base ?? 1.00;
            $pricePerKm = $pricing->price_per_km ?? 0.50;
            $pricePerMin = $pricing->price_per_minute ?? 0.10;
        }

        Log::info("📊 Tarifas aplicadas: Base: \${$baseFare}, Precio/km: \${$pricePerKm}, Precio/min: \${$pricePerMin}");

        // ✅ Calcular precio final
        $totalPrice = $baseFare + ($pricePerKm * $distanceKm) + ($pricePerMin * $serviceTimeMin);

        Log::info("✅ Precio final calculado: \${$totalPrice}");

        return $totalPrice;
    }

    // ✅ Función para manejar cancelación de viaje
    private function handleCancelRide($from, $rideRequestId)
    {
        $rideRequest = RideRequest::find($rideRequestId);

        if (!$rideRequest || in_array($rideRequest->status, ['finalizado', 'cancelado'])) {
            $this->sendMessage($from, "❌ No se puede cancelar este viaje.");
            return;
        }

        $rideRequest->update(['status' => 'cancelado']);

        // Limpiar la variable de sesión si existía
        session()->forget("ride_start_time_{$rideRequestId}");

        // Notificar al cliente
        $customer = Customer::find($rideRequest->customer_id);
        if ($customer) {
            $this->sendMessage($customer->phone, "⚠️ Tu conductor ha cancelado el viaje. Puedes solicitar otro taxi.");
        }

        // Notificar al conductor
        $this->sendMessage($from, "🚫 Has cancelado el viaje.");
    }

    // ✅ Función para enviar botones iniciales
    private function sendInteractiveButtons($to)
    {
        $data = [
            "messaging_product" => "whatsapp",
            "to" => $to,
            "type" => "interactive",
            "interactive" => [
                "type" => "button",
                "body" => ["text" => trim("¡Hola! 👋🎉\n\nSoy Automobile, la aplicación que pone en tus manos viajes seguros 🚗.\n\n¡Prepárate para disfrutar de un viaje excepcional!")],
                "action" => [
                    "buttons" => [
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "request_vehiculo",
                                "title" => "🚖 SOLICITAR VEHICULO"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "provide_contact",
                                "title" => "📞 CONTACTO"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->sendToWhatsApp($data);
    }

    // ✅ Función para solicitar ubicación
    private function requestLocation($from)
    {
        $message = [
            "messaging_product" => "whatsapp",
            "to" => $from,
            "type" => "interactive",
            "interactive" => [
                "type" => "location_request_message",
                "body" => [
                    "text" => trim("📍 Comparte tu ubicación para que el conductor te recoja.\n- Si estás en tu ubicación actual, envíala con la opción de compartir ubicación en WhatsApp.\n- Si deseas otra ubicación, envíanos la dirección manualmente. 🏠✍️")
                ],
                "action" => [
                    "name" => "send_location"
                ]
            ]
        ];

        $this->sendToWhatsApp($message);
    }


    // ✅ Función para enviar mensaje de texto
    public function sendMessage($to, $text)
    {
        $data = [
            "messaging_product" => "whatsapp",
            "to" => $to,
            "type" => "text",
            "text" => ["body" => $text]
        ];

        $this->sendToWhatsApp($data);
    }

    // funcion interactivo con botones
    private function sendInteractiveButton($to, $bodyText, $buttons)
    {
        $interactiveMessage = [
            "messaging_product" => "whatsapp",
            "to" => $to,
            "type" => "interactive",
            "interactive" => [
                "type" => "button",
                "body" => ["text" => $bodyText],
                "action" => [
                    "buttons" => array_map(function ($button) {
                        return [
                            "type" => "reply",
                            "reply" => [
                                "id" => $button['id'],
                                "title" => $button['title']
                            ]
                        ];
                    }, $buttons)
                ]
            ]
        ];

        $this->sendToWhatsApp($interactiveMessage);
    }


    // ✅ Función para enviar datos a WhatsApp
    private function sendToWhatsApp($data)
    {
        $token = env('WHATSAPP_TOKEN');
        $phoneID = env('WHATSAPP_PHONE_ID');
        $url = "https://graph.facebook.com/v21.0/{$phoneID}/messages";

        $response = Http::withToken($token)->post($url, $data);

        if ($response->failed()) {
            Log::error("❌ Error al enviar mensaje a WhatsApp: " . $response->body());
            return false;
        }

        Log::info("✅ Respuesta de WhatsApp: " . $response->body());
        return true;
    }

}
