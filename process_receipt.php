<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate CSRF token against database
$csrf_token = $_POST['csrf_token'] ?? '';
$stmt = $conn->prepare("SELECT id FROM csrf_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW()");
$stmt->bind_param("is", $_SESSION['user_id'], $csrf_token);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired CSRF token']);
    $stmt->close();
    $conn->close();
    exit;
}

// Delete CSRF token after validation to prevent reuse
$token_id = $result->fetch_assoc()['id'];
$stmt->close();
$stmt = $conn->prepare("DELETE FROM csrf_tokens WHERE id = ?");
$stmt->bind_param("i", $token_id);
$stmt->execute();
$stmt->close();

if (isset($_POST['single_item']) && $_POST['single_item'] === 'true') {
    $item = trim($_POST['item'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $subtotal = (float)($_POST['subtotal'] ?? 0);

    if (empty($item) || $quantity <= 0 || $price < 0 || $subtotal < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO receipts (item, quantity, price, subtotal, date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sidd", $item, $quantity, $price, $subtotal);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'receipt_id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' .'Failed to save receipt']);
    }
    $stmt->close();
} else {
    $items = $_POST['item'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];

    if (empty($items) || count($items) !== count($quantities) || count($items) !== count($prices)) {
        echo json_encode(['success' => false, 'message' => 'Invalid or incomplete data']);
        $conn->close();
        exit;
    }

    $receipt_id = time();
    $stmt = $conn->prepare("INSERT INTO receipts (item, quantity, price, subtotal, date) VALUES (?, ?, ?, ?, NOW())");
    $success = true;
    foreach ($items as $index => $item) {
        $item = trim($item);
        $quantity = (int)($quantities[$index] ?? 0);
        $price = (float)($prices[$index] ?? 0);
        $subtotal = $quantity * $price;

        if (empty($item) || $quantity <= 0 || $price < 0) {
            $success = false;
            break;
        }

        $stmt->bind_param("sidd", $item, $quantity, $price, $subtotal);
        if (!$stmt->execute()) {
            $success = false;
            break;
        }
    }

    if ($success) {
        echo json_encode(['success' => true, 'receipt_id' => $receipt_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save one or more items']);
    }
    $stmt->close();
}
$conn->close();
?>