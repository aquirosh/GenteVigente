<?php
// backend/session-check.php - Verificar estado de sesión para AJAX
session_start();

header('Content-Type: application/json');

$response = ['active' => false];

if (isset($_SESSION['user_id'])) {
    // Opcional: Verificar que el usuario aún existe en la BD
    try {
        require 'db.php';
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND subscription_status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $response['active'] = true;
        }
    } catch (Exception $e) {
        // En caso de error de BD, mantener la sesión activa
        $response['active'] = true;
    }
}

echo json_encode($response);
?>