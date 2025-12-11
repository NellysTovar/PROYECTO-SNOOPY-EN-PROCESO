<?php
// Configuración de Cabeceras
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Desactivar errores visuales
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'conexion.php';

$response = ["success" => false, "message" => "Acción no válida"];

try {
    // 1. VALIDACIÓN DE SESIÓN
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Sesión expirada. Por favor inicie sesión nuevamente.");
    }

    $idJuez = $_SESSION['user_id'];
    $rol = $_SESSION['user_role'] ?? '';

    // Validar que sea Juez o Coach-Juez
    if ($rol !== 'JUEZ' && $rol !== 'COACH_JUEZ' && $rol !== 'ADMIN') {
        throw new Exception("No tienes permisos de Juez.");
    }

    // 2. OBTENER DATOS
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($method === 'POST') {
        
        // --- GUARDAR EVALUACIÓN ---
        if ($action === 'guardar_evaluacion') {
            $idEquipo = $input['id_equipo'] ?? 0;
            $total = $input['total'] ?? 0;

            if ($idEquipo <= 0) throw new Exception("ID de equipo no válido.");

            // Llamada al Procedimiento Almacenado
            // RegistrarEvaluacion(id_equipo, id_juez, total, OUT resultado)
            $stmt = $pdo->prepare("CALL RegistrarEvaluacion(:ide, :idj, :tot, @res)");
            $stmt->bindParam(':ide', $idEquipo);
            $stmt->bindParam(':idj', $idJuez);
            $stmt->bindParam(':tot', $total);
            $stmt->execute();
            $stmt->closeCursor();

            // Obtener resultado
            $output = $pdo->query("SELECT @res as mensaje")->fetch(PDO::FETCH_ASSOC);
            $mensaje = $output['mensaje'] ?? 'Error desconocido';

            if (strpos($mensaje, 'ÉXITO') !== false) {
                $response = ["success" => true, "message" => $mensaje];
            } else {
                throw new Exception($mensaje);
            }
        }
        else {
            throw new Exception("Acción no reconocida: " . $action);
        }
    }

} catch (Exception $e) {
    $response["success"] = false;
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
?>