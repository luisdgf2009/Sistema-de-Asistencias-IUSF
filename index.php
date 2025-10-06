<?php
session_start();

// --- ENRUTADOR DE ACCIONES ---
// Decide qué hacer basado en el parámetro 'action' en la URL.
$action = $_GET['action'] ?? 'view'; // Por defecto, muestra la página.

/**
 * Acción: 'generate_token'
 * Genera un token seguro, lo guarda en la sesión y lo devuelve como JSON.
 * Se llama mediante JavaScript desde la página principal.
 */
if ($action === 'generate_token') {
    // 1. Generar un token criptográficamente seguro.
    $token = bin2hex(random_bytes(32));

    // 2. Guardar el token y su tiempo de expiración (60 segundos) en la sesión.
    $_SESSION['attendance_token'] = $token;
    $_SESSION['token_expiry'] = time() + 60;

    // 3. Devolver el token en formato JSON y terminar la ejecución.
    header('Content-Type: application/json');
    echo json_encode(['token' => $token]);
    exit; // Detiene el script para no renderizar el HTML de abajo.
}

/**
 * Acción: 'register'
 * Valida el token recibido del QR y muestra un mensaje de confirmación/error.
 * Es la URL a la que apunta el código QR.
 */
if ($action === 'register') {
    // Simulación de base de datos de docentes
    $docentes = [
        'user123' => 'Juan Pérez',
        'user456' => 'Maria Rodriguez'
    ];
    // En un sistema real, el docente estaría logueado en su teléfono.
    // Simulamos que obtenemos su ID de la sesión de su dispositivo.
    $idDocenteLogueado = 'user123';

    $tokenRecibido = $_GET['token'] ?? '';
    $mensaje = '';
    $error = false;

    // 1. Validar que el token del QR existe y que hay un token guardado en sesión.
    if (!empty($tokenRecibido) && isset($_SESSION['attendance_token'])) {
        // 2. Validar que el token sea el correcto (usando hash_equals para seguridad) y no haya expirado.
        if (hash_equals($_SESSION['attendance_token'], $tokenRecibido) && time() < $_SESSION['token_expiry']) {
            // ¡Éxito! El token es válido.
            $nombreDocente = $docentes[$idDocenteLogueado] ?? 'Desconocido';
            $hora = date('h:i:s A');

            // Aquí iría el código para guardar en la base de datos real.
            // Ejemplo: registrarAsistenciaEnDB($idDocenteLogueado, time());

            $mensaje = "¡Asistencia registrada con éxito para <strong>{$nombreDocente}</strong> a las {$hora}!";

            // 3. Invalidar el token para que no se pueda volver a usar.
            unset($_SESSION['attendance_token']);
            unset($_SESSION['token_expiry']);
        } else {
            $mensaje = "Error: El código QR ha expirado o ya fue utilizado. Por favor, genera uno nuevo.";
            $error = true;
        }
    } else {
        $mensaje = "Error: Código QR no válido.";
        $error = true;
    }

    // 4. Mostrar la página de resultado al docente y terminar la ejecución.
    echo "<!DOCTYPE html><html lang='es'><head><title>Resultado de Marcación</title><meta name='viewport' content='width=device-width, initial-scale=1.0'></head><body style='font-family: sans-serif; text-align: center; padding-top: 50px;'>";
    echo "<h1 style='color: " . ($error ? '#D32F2F' : '#388E3C') . ";'>" . $mensaje . "</h1>";
    echo "<p>Puedes cerrar esta ventana.</p>";
    echo "</body></html>";
    exit; // Detiene el script para no renderizar el HTML principal.
}

// --- VISTA PRINCIPAL (ACCIÓN 'view' O POR DEFECTO) ---
// Si ninguna de las acciones anteriores se ejecutó, se muestra la página del kiosko.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia - IUSF</title>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        :root {
            --color-rojo-chillon: #FF0033;
            --color-gris-claro: #F0F0F0;
            --color-negro: #1a1a1a;
            --color-blanco: #FFFFFF;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            background-color: var(--color-gris-claro);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: var(--color-negro);
        }
        .attendance-container {
            background-color: var(--color-blanco);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 90%;
            max-width: 450px;
            border-top: 8px solid var(--color-rojo-chillon);
        }
        .logo-container {
            width: 150px; height: 150px; margin: 0 auto 20px; background-color: var(--color-gris-claro);
            border-radius: 50%; display: flex; justify-content: center; align-items: center;
            font-weight: bold; color: #888; font-size: 1.2rem;
            /* background-image: url('ruta/a/tu/logo.png'); */
            background-size: contain; background-repeat: no-repeat; background-position: center;
        }
        h1 { margin-bottom: 10px; font-size: 1.8rem; }
        p { color: #666; margin-bottom: 30px; }
        .qr-code-container {
            margin: 30px auto; padding: 20px; background-color: var(--color-gris-claro);
            border-radius: 10px; min-height: 256px; display: flex; justify-content: center;
            align-items: center; transition: opacity 0.3s;
        }
        #qrcode {
            background-color: white; padding: 15px; border-radius: 5px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .btn-generate {
            background-color: var(--color-rojo-chillon); color: var(--color-blanco); border: none;
            padding: 15px 30px; font-size: 1.1rem; font-weight: bold; border-radius: 50px;
            cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-generate:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 0, 51, 0.3);
        }
        .btn-generate:disabled {
            background-color: #ccc; cursor: not-allowed; transform: none; box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="attendance-container">
        <div class="logo-container">Logo IUSF</div>
        <h1>Registro de Asistencia</h1>
        <p>Presiona el botón para generar tu código de marcación único.</p>
        
        <button id="generateBtn" class="btn-generate">Generar QR para Marcar</button>

        <div class="qr-code-container">
            <div id="qrcode"></div>
        </div>
        <p id="status-text" style="color: #888; min-height: 20px;"></p>
    </div>

    <script>
        const generateBtn = document.getElementById('generateBtn');
        const qrCodeDiv = document.getElementById('qrcode');
        const statusText = document.getElementById('status-text');
        let qrcode = null;

        generateBtn.addEventListener('click', () => {
            // Deshabilitar botón para evitar múltiples clics
            generateBtn.disabled = true;
            generateBtn.textContent = 'Generando...';
            qrCodeDiv.innerHTML = ''; // Limpiar QR anterior
            statusText.textContent = 'Creando código seguro...';

            // 1. Llamar a este mismo archivo, pero con ?action=generate_token
            fetch('index.php?action=generate_token')
                .then(response => response.json())
                .then(data => {
                    if (data.token) {
                        // 2. Construir la URL para el QR, apuntando a ?action=register
                        const url = `${window.location.origin}${window.location.pathname}?action=register&token=${data.token}`;
                        
                        // 3. Generar el nuevo código QR
                        if (qrcode) {
                            qrcode.makeCode(url);
                        } else {
                            qrcode = new QRCode(qrCodeDiv, {
                                text: url,
                                width: 256,
                                height: 256,
                                colorDark: "#000000",
                                colorLight: "#ffffff",
                                correctLevel: QRCode.CorrectLevel.H
                            });
                        }
                        statusText.textContent = '¡Escanea el código! Expira en 60 segundos.';
                    }
                })
                .catch(error => {
                    console.error('Error al generar el token:', error);
                    statusText.textContent = 'Error de red. Inténtalo de nuevo.';
                })
                .finally(() => {
                    // Volver a habilitar el botón después de un segundo
                    setTimeout(() => {
                        generateBtn.disabled = false;
                        generateBtn.textContent = 'Generar Nuevo QR';
                    }, 1000);
                });
        });
    </script>
</body>
</html>