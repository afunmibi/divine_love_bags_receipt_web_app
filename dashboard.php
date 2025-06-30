<?php
session_start();

// Redirect to index.php if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require 'config.php';

// Clean up expired CSRF tokens
$conn->query("DELETE FROM csrf_tokens WHERE expires_at < NOW()");

// Verify CSRF token exists in database for this user
$stmt = $conn->prepare("SELECT token FROM csrf_tokens WHERE user_id = ? AND expires_at > NOW()");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // Generate new token if none exists
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $stmt = $conn->prepare("INSERT INTO csrf_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $_SESSION['user_id'], $token, $expires_at);
    $stmt->execute();
    $_SESSION['csrf_token'] = $token;
}
$stmt->close();

// Fetch recent receipts
$recent_receipts = [];
$sql_recent = "SELECT id, item, quantity, price, subtotal, date FROM receipts ORDER BY date DESC LIMIT 5";
$result_recent = $conn->query($sql_recent);
if ($result_recent && $result_recent->num_rows > 0) {
    while ($row = $result_recent->fetch_assoc()) {
        $recent_receipts[] = $row;
    }
}

// Helper function for sales report data
function getReportData($conn, $period_condition) {
    $sql_revenue = "SELECT COALESCE(SUM(subtotal), 0) AS total_revenue FROM receipts WHERE $period_condition";
    $result_revenue = $conn->query($sql_revenue);
    $revenue = $result_revenue && $result_revenue->num_rows > 0 ? $result_revenue->fetch_assoc()['total_revenue'] : 0;

    $sql_count = "SELECT COUNT(id) AS items_sold FROM receipts WHERE $period_condition";
    $result_count = $conn->query($sql_count);
    $items_sold = $result_count && $result_count->num_rows > 0 ? $result_count->fetch_assoc()['items_sold'] : 0;

    return [
        'revenue' => $revenue,
        'items_sold' => $items_sold
    ];
}

// Sales report conditions
$current_week_condition = "YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
$previous_week_condition = "YEARWEEK(date, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)";
$current_month_condition = "MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
$previous_month_condition = "MONTH(date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";

$current_week_report = getReportData($conn, $current_week_condition);
$previous_week_report = getReportData($conn, $previous_week_condition);
$current_month_report = getReportData($conn, $current_month_condition);
$previous_month_report = getReportData($conn, $previous_month_condition);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Divine-Love Bags Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e0f7ff 100%);
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .header {
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
            position: relative;
        }
        .header img {
            max-width: 80px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-family: 'Playfair Display', serif;
            color: #87CEFA;
            font-size: 2rem;
            margin: 0;
        }
        .store-info {
            font-size: 0.85rem;
            color: #333;
            text-align: center;
            margin: 10px 0;
        }
        .logout-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.85rem;
            padding: 5px 10px;
        }
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
        }
        .item-row {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #e0f7ff;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #87CEFA;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus {
            border-color: #5f9ea0;
            box-shadow: 0 0 8px rgba(135, 206, 250, 0.3);
        }
        .btn-primary {
            background: linear-gradient(90deg, #87CEFA, #5f9ea0);
            border: none;
            border-radius: 20px;
            padding: 8px 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(135, 206, 250, 0.5);
        }
        .btn-secondary {
            background: #FFD700;
            border: none;
            color: #333;
            border-radius: 20px;
            padding: 8px 16px;
            transition: transform 0.2s ease;
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            background: #ffeb3b;
        }
        .btn-danger {
            border-radius: 20px;
            padding: 8px 16px;
            transition: transform 0.2s ease;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        .btn-info {
            background: #20c997;
            border: none;
            border-radius: 20px;
            padding: 6px 12px;
            transition: transform 0.2s ease;
        }
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(32, 201, 151, 0.3);
        }
        .btn-icon {
            font-size: 0.9rem;
            padding: 6px 12px;
        }
        .total {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            text-align: right;
            margin: 15px 0;
        }
        .receipt-details, .sales-reports {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
        }
        .receipt-table th, .receipt-table td {
            padding: 12px;
            font-size: 0.9rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .receipt-table th {
            background: linear-gradient(90deg, #87CEFA, #5f9ea0);
            color: white;
            font-weight: 600;
        }
        .receipt-table td {
            background: #ffffff;
            word-wrap: break-word;
        }
        .receipt-table th:nth-child(1), .receipt-table td:nth-child(1) { width: 35%; }
        .receipt-table th:nth-child(2), .receipt-table td:nth-child(2) { width: 15%; }
        .receipt-table th:nth-child(3), .receipt-table td:nth-child(3) { width: 20%; }
        .receipt-table th:nth-child(4), .receipt-table td:nth-child(4) { width: 20%; }
        .receipt-table th:nth-child(5), .receipt-table td:nth-child(5) { width: 10%; }
        .receipt-table tr {
            transition: opacity 0.3s ease;
        }
        .receipt-table tr.new-row {
            animation: fadeInRow 0.5s ease;
        }
        @keyframes fadeInRow {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .report-card {
            background-color: #fff;
            border: 1px solid #e0f7ff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .report-card h5 {
            font-family: 'Playfair Display', serif;
            color: #5f9ea0;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        .report-card .report-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #87CEFA;
            margin-bottom: 5px;
        }
        .report-card .report-label {
            font-size: 0.85rem;
            color: #666;
        }
        .footer {
            font-size: 0.85rem;
            color: #FFD700;
            text-align: center;
            margin-top: 20px;
            font-style: italic;
        }
        .toast-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: #5f9ea0;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            opacity: 0;
            animation: fadeIn 0.5s ease forwards, fadeOut 0.5s ease 3s forwards;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            to { opacity: 0; display: none; }
        }
        @media (max-width: 576px) {
            .item-row .col-12 {
                margin-bottom: 10px;
            }
            .item-row .d-flex {
                justify-content: space-between;
            }
            .btn-lg {
                padding: 0.5rem 0.8rem;
                font-size: 0.9rem;
            }
            .form-control-lg {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            .receipt-table thead {
                display: none;
            }
            .receipt-table tr {
                display: block;
                margin-bottom: 15px;
                padding: 10px;
                border: 1px solid #e0f7ff;
                border-radius: 8px;
                background: #ffffff;
            }
            .receipt-table td {
                display: block;
                text-align: left;
                padding: 5px 10px;
                font-size: 0.95rem;
                border: none;
            }
            .receipt-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #5f9ea0;
                display: inline-block;
                width: 80px;
            }
            .receipt-table td:nth-child(5) {
                text-align: center;
                padding-top: 10px;
            }
            .receipt-table td:nth-child(5):before {
                content: none;
            }
            .receipt-table .btn-info {
                padding: 5px 10px;
                font-size: 0.85rem;
            }
            .report-card {
                padding: 10px;
            }
            .report-card h5 {
                font-size: 1rem;
            }
            .report-card .report-value {
                font-size: 1.4rem;
            }
            .report-card .report-label {
                font-size: 0.75rem;
            }
            .logout-btn {
                position: static;
                display: block;
                margin: 10px auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="assets/images/Divine-love-logo.jpg" alt="Divine-Love Bags Logo" class="img-fluid">
            <h1>Divine-Love Bags</h1>
            <div class="store-info">
                <p>25, Opeolu Street, Off SUBEB Ijeun Titun, Abeokuta, Ogun State</p>
                <p>Call/WhatsApp: 08132686523</p>
            </div>
            <a href="logout.php" class="btn btn-danger btn-sm logout-btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="form-section">
            <h3 class="mb-4" style="font-family: 'Playfair Display', serif; color: #333; text-align: center;">Create New Receipt</h3>
            <form id="receiptForm" method="POST" action="process_receipt.php" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div id="items">
                    <div class="item-row row g-3 align-items-end mb-3 p-3 border rounded">
                        <div class="col-12 col-md-5">
                            <label for="item0" class="form-label visually-hidden">Item Name</label>
                            <input type="text" class="form-control form-control-lg" name="item[]" placeholder="Item Name (e.g., School-Bag)" required>
                            <div class="invalid-feedback">Please enter an item name.</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="quantity0" class="form-label visually-hidden">Quantity</label>
                            <input type="number" class="form-control form-control-lg" name="quantity[]" min="1" value="1" placeholder="Qty" required>
                            <div class="invalid-feedback">Please enter a valid quantity (min 1).</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="price0" class="form-label visually-hidden">Price (₦)</label>
                            <input type="number" class="form-control form-control-lg" name="price[]" min="0" step="0.01" value="0.00" placeholder="Price (₦)" required>
                            <div class="invalid-feedback">Please enter a valid price (min 0).</div>
                        </div>
                        <div class="col-12 col-md-1 d-flex justify-content-end">
                            <button type="button" class="btn btn-primary btn-lg btn-icon generate-single-receipt flex-fill me-2" title="Generate Receipt for this Item">
                                <i class="fas fa-file-download"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-lg btn-icon remove-item flex-fill" title="Remove This Item">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-grid d-md-block">
                    <button type="button" class="btn btn-secondary my-3 w-100 w-md-auto" id="addItem">
                        <i class="fas fa-plus-circle me-2"></i>Add Item
                    </button>
                </div>

                <div class="total mt-3 py-2 px-3 border-top border-bottom rounded d-flex justify-content-between align-items-center">
                    <span class="fs-5 text-muted">Total:</span>
                    <p class="mb-0 fs-4 fw-bold" style="color: #5f9ea0;">₦<span id="total">0.00</span></p>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100 w-md-auto" id="saveAndGenerateAll">
                        <i class="fas fa-save me-2"></i>Save & Generate All Receipts
                    </button>
                </div>
            </form>
        </div>

        <div class="receipt-details">
            <h3 class="mb-3" style="font-family: 'Playfair Display', serif; color: #333;">Recent Receipts</h3>
            <div class="table-responsive">
                <table class="receipt-table" id="previewTable">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($recent_receipts)) {
                            foreach ($recent_receipts as $row) {
                                echo "<tr data-receipt-id='" . htmlspecialchars($row['id']) . "'>";
                                echo "<td data-label='Item'>" . htmlspecialchars($row['item']) . "</td>";
                                echo "<td data-label='Quantity'>" . htmlspecialchars($row['quantity']) . "</td>";
                                echo "<td data-label='Price'>₦" . htmlspecialchars(number_format($row['price'], 2)) . "</td>";
                                echo "<td data-label='Subtotal'>₦" . htmlspecialchars(number_format($row['subtotal'], 2)) . "</td>";
                                echo "<td data-label='Actions'><button type='button' class='btn btn-info btn-sm generate-fetched-pdf' data-item-name='" . htmlspecialchars($row['item']) . "' data-quantity='" . htmlspecialchars($row['quantity']) . "' data-price='" . htmlspecialchars($row['price']) . "' data-subtotal='" . htmlspecialchars($row['subtotal']) . "' title='Generate PDF for this transaction'><i class='fas fa-file-pdf'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' data-label='No Receipts'>No recent receipts.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sales-reports">
            <h3 class="mb-4" style="font-family: 'Playfair Display', serif; color: #333;">Sales Reports</h3>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <h5>Current Week</h5>
                        <p class="report-value">₦<?php echo number_format($current_week_report['revenue'], 2); ?></p>
                        <p class="report-label"><?php echo htmlspecialchars($current_week_report['items_sold']); ?> Sales Entries</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <h5>Previous Week</h5>
                        <p class="report-value">₦<?php echo number_format($previous_week_report['revenue'], 2); ?></p>
                        <p class="report-label"><?php echo htmlspecialchars($previous_week_report['items_sold']); ?> Sales Entries</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <h5>Current Month</h5>
                        <p class="report-value">₦<?php echo number_format($current_month_report['revenue'], 2); ?></p>
                        <p class="report-label"><?php echo htmlspecialchars($current_month_report['items_sold']); ?> Sales Entries</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <h5>Previous Month</h5>
                        <p class="report-value">₦<?php echo number_format($previous_month_report['revenue'], 2); ?></p>
                        <p class="report-label"><?php echo htmlspecialchars($previous_month_report['items_sold']); ?> Sales Entries</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for patronizing us. We are expecting you next time.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast-message';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.remove();
            }, 3500);
        }

        const itemsContainer = document.getElementById('items');
        const totalSpan = document.getElementById('total');

        itemsContainer.addEventListener('input', updateTotal);

        function updateTotal() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const quantityInput = row.querySelector('input[name="quantity[]"]');
                const priceInput = row.querySelector('input[name="price[]"]');
                const quantity = parseFloat(quantityInput?.value) || 0;
                const price = parseFloat(priceInput?.value) || 0;
                total += quantity * price;
            });
            totalSpan.textContent = total.toFixed(2);
        }

        updateTotal();

        let itemIndex = document.querySelectorAll('.item-row').length;
        document.getElementById('addItem').addEventListener('click', () => {
            const itemRow = document.createElement('div');
            itemRow.className = 'item-row row g-3 align-items-end mb-3 p-3 border rounded';
            itemRow.innerHTML = `
                <div class="col-12 col-md-5">
                    <label for="item${itemIndex}" class="form-label visually-hidden">Item Name</label>
                    <input type="text" class="form-control form-control-lg" name="item[]" placeholder="Item Name (e.g., Backpack)" required>
                    <div class="invalid-feedback">Please enter an item name.</div>
                </div>
                <div class="col-6 col-md-3">
                    <label for="quantity${itemIndex}" class="form-label visually-hidden">Quantity</label>
                    <input type="number" class="form-control form-control-lg" name="quantity[]" min="1" value="1" placeholder="Qty" required>
                    <div class="invalid-feedback">Please enter a valid quantity (min 1).</div>
                </div>
                <div class="col-6 col-md-3">
                    <label for="price${itemIndex}" class="form-label visually-hidden">Price (₦)</label>
                    <input type="number" class="form-control form-control-lg" name="price[]" min="0" step="0.01" value="0.00" placeholder="Price (₦)" required>
                    <div class="invalid-feedback">Please enter a valid price (min 0).</div>
                </div>
                <div class="col-12 col-md-1 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary btn-lg btn-icon generate-single-receipt flex-fill me-2" title="Generate Receipt for this Item">
                        <i class="fas fa-file-download"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-lg btn-icon remove-item flex-fill" title="Remove This Item">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            itemsContainer.appendChild(itemRow);
            itemIndex++;
            updateTotal();
        });

        itemsContainer.addEventListener('click', (e) => {
            const removeButton = e.target.closest('.remove-item');
            if (removeButton) {
                const rowToRemove = removeButton.closest('.item-row');
                if (document.querySelectorAll('.item-row').length > 1) {
                    rowToRemove.remove();
                    updateTotal();
                } else {
                    showToast("Cannot remove the last item.");
                }
            }

            const generateButton = e.target.closest('.generate-single-receipt');
            if (generateButton) {
                const row = generateButton.closest('.item-row');
                const itemInput = row.querySelector('input[name="item[]"]');
                const quantityInput = row.querySelector('input[name="quantity[]"]');
                const priceInput = row.querySelector('input[name="price[]"]');

                if (!itemInput.checkValidity() || !quantityInput.checkValidity() || !priceInput.checkValidity()) {
                    itemInput.reportValidity();
                    quantityInput.reportValidity();
                    priceInput.reportValidity();
                    showToast('Please fill in valid item, quantity, and price.');
                    return;
                }

                const item = itemInput.value;
                const quantity = parseFloat(quantityInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const subtotal = quantity * price;

                const formData = new FormData();
                formData.append('item', item);
                formData.append('quantity', quantity);
                formData.append('price', price);
                formData.append('subtotal', subtotal);
                formData.append('single_item', 'true');
                formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>');

                fetch('process_receipt.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`Item receipt saved and generated! Receipt No: ${data.receipt_id}`);
                        const itemsData = [{ name: item, quantity: quantity, price: price, subtotal: subtotal }];
                        generateReceiptPDF(itemsData, subtotal, data.receipt_id);

                        const previewTableBody = document.querySelector('#previewTable tbody');
                        const newRow = document.createElement('tr');
                        newRow.className = 'new-row';
                        newRow.setAttribute('data-receipt-id', data.receipt_id);
                        newRow.innerHTML = `
                            <td data-label="Item">${item}</td>
                            <td data-label="Quantity">${quantity}</td>
                            <td data-label="Price">₦${price.toFixed(2)}</td>
                            <td data-label="Subtotal">₦${subtotal.toFixed(2)}</td>
                            <td data-label="Actions"><button type="button" class="btn btn-info btn-sm generate-fetched-pdf" data-item-name="${item}" data-quantity="${quantity}" data-price="${price}" data-subtotal="${subtotal}" title="Generate PDF for this transaction"><i class="fas fa-file-pdf"></i></button></td>
                        `;
                        previewTableBody.prepend(newRow);
                        while (previewTableBody.children.length > 5) {
                            previewTableBody.removeChild(previewTableBody.lastChild);
                        }
                    } else {
                        showToast(`Error saving item: ${data.message || 'Unknown error'}`);
                    }
                })
                .catch(error => {
                    console.error('Error saving single item:', error);
                    showToast('An error occurred while saving the item.');
                });
            }
        });

        document.getElementById('receiptForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            if (!form.checkValidity()) {
                e.stopPropagation();
                form.classList.add('was-validated');
                showToast('Please correct the highlighted errors in the form.');
                return;
            }
            form.classList.add('was-validated');

            const itemsDataForPdf = [];
            let totalAmountForPdf = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const item = row.querySelector('input[name="item[]"]').value;
                const quantity = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
                const price = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
                const subtotal = quantity * price;
                itemsDataForPdf.push({ name: item, quantity: quantity, price: price, subtotal: subtotal });
                totalAmountForPdf += subtotal;
            });

            const formData = new FormData(form);
            formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>');

            try {
                const response = await fetch('process_receipt.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    showToast(`All items saved and PDF generated! Receipt No: ${data.receipt_id}`);
                    generateReceiptPDF(itemsDataForPdf, totalAmountForPdf, data.receipt_id);
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showToast(`Error saving all items: ${data.message || 'Unknown error'}`);
                }
            } catch (error) {
                console.error('Error saving all items:', error);
                showToast('An error occurred while saving all items.');
            }
        });

        document.getElementById('previewTable').addEventListener('click', (e) => {
            const generateButton = e.target.closest('.generate-fetched-pdf');
            if (generateButton) {
                const item = generateButton.dataset.itemName;
                const quantity = parseFloat(generateButton.dataset.quantity);
                const price = parseFloat(generateButton.dataset.price);
                const subtotal = parseFloat(generateButton.dataset.subtotal);
                const receiptId = generateButton.closest('tr').dataset.receiptId;
                generateReceiptPDF([{ name: item, quantity: quantity, price: price, subtotal: subtotal }], subtotal, receiptId);
                showToast(`PDF generated for Receipt No: ${receiptId}`);
            }
        });

        function generateReceiptPDF(itemsData, totalAmount, receiptId) {
            if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
                console.error('jsPDF library not loaded.');
                showToast('PDF generation failed: Library not loaded.');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            let yOffset = 15;

            const primaryColor = [135, 206, 250];
            const accentColor = [95, 158, 160];
            const textColor = [50, 50, 50];
            const lightTextColor = [150, 150, 150];
            const white = [255, 255, 255];

            try {
                doc.addImage('assets/images/Divine-love-logo.jpg', 'JPEG', 20, 15, 20, 20);
            } catch (e) {
                console.warn('Logo not found or could not be loaded.', e);
                doc.setFontSize(8);
                doc.setTextColor(lightTextColor[0], lightTextColor[1], lightTextColor[2]);
                doc.text('Divine-Love Bags', 20, 25);
            }

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(24);
            doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
            doc.text('Divine-Love Bags', doc.internal.pageSize.width / 2, yOffset, { align: 'center' });
            yOffset += 10;

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.setTextColor(lightTextColor[0], lightTextColor[1], lightTextColor[2]);
            doc.text('25, Opeolu Street, Off SUBEB Ijeun Titun, Abeokuta, Ogun State', doc.internal.pageSize.width / 2, yOffset, { align: 'center' });
            yOffset += 4;
            doc.text('Call/WhatsApp: 08132686523', doc.internal.pageSize.width / 2, yOffset, { align: 'center' });
            yOffset += 12;

            doc.setDrawColor(accentColor[0], accentColor[1], accentColor[2]);
            doc.setLineWidth(0.5);
            doc.line(20, yOffset, doc.internal.pageSize.width - 20, yOffset);
            yOffset += 10;

            doc.setFontSize(10);
            doc.setTextColor(textColor[0], textColor[1], textColor[2]);
            doc.text(`Date: ${new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' })}`, 20, yOffset);
            doc.text(`Receipt No: ${receiptId}`, doc.internal.pageSize.width - 20, yOffset, { align: 'right' });
            yOffset += 15;

            doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
            doc.rect(15, yOffset - 2, doc.internal.pageSize.width - 30, 8, 'F');
            doc.setTextColor(white[0], white[1], white[2]);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('Item', 20, yOffset + 3);
            doc.text('Qty', 85, yOffset + 3);
            doc.text('Price (₦)', 125, yOffset + 3);
            doc.text('Subtotal (₦)', doc.internal.pageSize.width - 20, yOffset + 3, { align: 'right' });
            yOffset += 8;

            doc.setTextColor(textColor[0], textColor[1], textColor[2]);
            doc.setFontSize(9);
            doc.setFont('helvetica', 'normal');
            let rowCounter = 0;
            itemsData.forEach(item => {
                if (yOffset > doc.internal.pageSize.height - 40) {
                    doc.addPage();
                    yOffset = 15;
                    doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                    doc.rect(15, yOffset - 2, doc.internal.pageSize.width - 30, 8, 'F');
                    doc.setTextColor(white[0], white[1], white[2]);
                    doc.setFontSize(10);
                    doc.setFont('helvetica', 'bold');
                    doc.text('Item', 20, yOffset + 3);
                    doc.text('Qty', 85, yOffset + 3);
                    doc.text('Price (₦)', 125, yOffset + 3);
                    doc.text('Subtotal (₦)', doc.internal.pageSize.width - 20, yOffset + 3, { align: 'right' });
                    yOffset += 8;
                    doc.setTextColor(textColor[0], textColor[1], textColor[2]);
                    doc.setFontSize(9);
                    doc.setFont('helvetica', 'normal');
                }

                doc.text(item.name.substring(0, 30), 20, yOffset + 4);
                doc.text(item.quantity.toString(), 85, yOffset + 4);
                doc.text(item.price.toFixed(2), 125, yOffset + 4);
                doc.text(item.subtotal.toFixed(2), doc.internal.pageSize.width - 20, yOffset + 4, { align: 'right' });

                if (rowCounter < itemsData.length - 1) {
                    doc.setDrawColor(230, 230, 230);
                    doc.setLineWidth(0.2);
                    doc.line(15, yOffset + 8, doc.internal.pageSize.width - 15, yOffset + 8);
                }

                yOffset += 10;
                rowCounter++;
            });

            yOffset += 10;
            doc.setDrawColor(accentColor[0], accentColor[1], accentColor[2]);
            doc.setLineWidth(0.8);
            doc.line(doc.internal.pageSize.width - 70, yOffset, doc.internal.pageSize.width - 20, yOffset);
            yOffset += 5;

            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('Total:', doc.internal.pageSize.width - 60, yOffset, { align: 'right' });
            doc.text(`₦${totalAmount.toFixed(2)}`, doc.internal.pageSize.width - 20, yOffset, { align: 'right' });
            yOffset += 15;

            doc.setFontSize(10);
            doc.setFont('helvetica', 'italic');
            doc.setTextColor(accentColor[0], accentColor[1], accentColor[2]);
            doc.text('Thank you for patronizing us. We are expecting you next time.', doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 20, { align: 'center' });

            // Watermark on each page
            for (let i = 1; i <= doc.internal.pages.length; i++) {
                doc.setPage(i);
                doc.setTextColor(248, 252, 255); // Lighter color for increased transparency
                doc.setFontSize(4); // 20% of original 20
                doc.setFont('helvetica', 'bold');
                // Note: jsPDF 2.5.1 does not support setAlpha for text; using lighter color instead
                doc.text('DIVINE-LOVE BAGS', doc.internal.pageSize.width / 2, doc.internal.pageSize.height / 2, { align: 'center', angle: 30 });
            }
            doc.setTextColor(textColor[0], textColor[1], textColor[2]); // Reset text color

            doc.save(`Divine-Love-Bags-Receipt-${receiptId}.pdf`);
        }

        (function () {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
