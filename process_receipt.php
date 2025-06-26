<?php
require'config.php';

$response = ["success" => false, "message" => "An unknown error occurred."];
$last_insert_id = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Determine if it's a single item save or multiple items save
    if (isset($_POST['single_item']) && $_POST['single_item'] === 'true') {
        // Handle single item submission
        $item = $_POST['item'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $price = (float)($_POST['price'] ?? 0.00);
        $subtotal = (float)($_POST['subtotal'] ?? 0.00);

        if (empty($item) || $quantity <= 0 || $price < 0) {
            $response = ["success" => false, "message" => "Invalid input for single item."];
        } else {
            $sql = "INSERT INTO receipts (item, quantity, price, subtotal, date) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                $response = ["success" => false, "message" => "Failed to prepare statement for single item: " . $conn->error];
            } else {
                $stmt->bind_param("sidd", $item, $quantity, $price, $subtotal);
                if ($stmt->execute()) {
                    $last_insert_id = $conn->insert_id;
                    $response = ["success" => true, "message" => "Single item saved successfully.", "receipt_id" => $last_insert_id];
                } else {
                    $response = ["success" => false, "message" => "Failed to execute statement for single item: " . $stmt->error];
                }
                $stmt->close();
            }
        }
    } else {
        // Handle multiple items submission (from "Save & Generate All")
        $items = $_POST['item'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $prices = $_POST['price'] ?? [];

        if (empty($items)) {
            $response = ["success" => false, "message" => "No items provided for bulk save."];
        } else {
            $conn->begin_transaction();
            $all_inserted = true;

            $sql = "INSERT INTO receipts (item, quantity, price, subtotal, date) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                $response = ["success" => false, "message" => "Failed to prepare statement for bulk save: " . $conn->error];
                $all_inserted = false;
            } else {
                foreach ($items as $index => $item_name) {
                    $quantity = (int)($quantities[$index] ?? 0);
                    $price = (float)($prices[$index] ?? 0.00);
                    $subtotal = $quantity * $price;

                    if (empty($item_name) || $quantity <= 0 || $price < 0) {
                        $all_inserted = false;
                        $response = ["success" => false, "message" => "Invalid data found for one or more items."];
                        break;
                    }

                    $stmt->bind_param("sidd", $item_name, $quantity, $price, $subtotal);
                    if (!$stmt->execute()) {
                        $all_inserted = false;
                        $response = ["success" => false, "message" => "Failed to insert item '{$item_name}': " . $stmt->error];
                        break;
                    }
                    if ($index == 0) { // Get the ID of the first item for the batch receipt number
                        $last_insert_id = $conn->insert_id;
                    }
                }
                $stmt->close();
            }

            if ($all_inserted) {
                $conn->commit();
                $response = ["success" => true, "message" => "All items saved successfully.", "receipt_id" => $last_insert_id];
            } else {
                $conn->rollback();
            }
        }
    }
} else {
    $response = ["success" => false, "message" => "Invalid request method. Only POST is allowed."];
}

$conn->close();
echo json_encode($response);
?>