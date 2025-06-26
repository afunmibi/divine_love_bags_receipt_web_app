<?php
require'config.php';

// --- PHP Logic for Fetching Recent Receipts ---
$recent_receipts = [];
$sql_recent = "SELECT id, item, quantity, price, subtotal, date FROM receipts ORDER BY date DESC LIMIT 5";
$result_recent = $conn->query($sql_recent);
if ($result_recent->num_rows > 0) {
    while ($row = $result_recent->fetch_assoc()) {
        $recent_receipts[] = $row;
    }
}

// --- PHP Logic for Sales Reports ---

// Helper function to fetch report data
function getReportData($conn, $period_condition) {
    $sql_revenue = "SELECT COALESCE(SUM(subtotal), 0) AS total_revenue FROM receipts WHERE $period_condition";
    $result_revenue = $conn->query($sql_revenue);
    $revenue_data = $result_revenue->fetch_assoc();

    $sql_count = "SELECT COUNT(id) AS items_sold FROM receipts WHERE $period_condition";
    $result_count = $conn->query($sql_count);
    $count_data = $result_count->fetch_assoc();

    return [
        'revenue' => $revenue_data['total_revenue'],
        'items_sold' => $count_data['items_sold']
    ];
}

// Current Week (Monday to Sunday)
$current_week_condition = "YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
$current_week_report = getReportData($conn, $current_week_condition);

// Previous Week (Monday to Sunday)
$previous_week_condition = "YEARWEEK(date, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)";
$previous_week_report = getReportData($conn, $previous_week_condition);

// Current Month
$current_month_condition = "MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
$current_month_report = getReportData($conn, $current_month_condition);

// Previous Month
$previous_month_condition = "MONTH(date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
$previous_month_report = getReportData($conn, $previous_month_condition);

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Divine-Love Bags Receipt Generator</title>
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
        }
        .header img {
            max-width: 80px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-family: 'Playfair Display', serif;
            color: #87CEFA; /* Sky Blue */
            font-size: 2rem;
            margin: 0;
        }
        .store-info {
            font-size: 0.85rem;
            color: #333;
            text-align: center;
            margin: 10px 0;
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
            border: 1px solid #87CEFA; /* Sky Blue */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus {
            border-color: #5f9ea0; /* Cadet Blue */
            box-shadow: 0 0 8px rgba(135, 206, 250, 0.3);
        }
        .btn-primary {
            background: linear-gradient(90deg, #87CEFA, #5f9ea0); /* Sky Blue to Cadet Blue */
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
            background: #FFD700; /* Gold */
            border: none;
            color: #333;
            border-radius: 20px;
            padding: 8px 16px;
            transition: transform 0.2s ease;
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            background: #ffeb3b; /* Lighter Gold */
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
        .btn-info { /* For fetched PDF button */
            background: #20c997; /* Teal */
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
        .receipt-details, .sales-reports { /* Added sales-reports */
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .receipt-details table {
            border: none;
            border-radius: 10px;
            width: 100%;
            margin-bottom: 0;
        }
        .receipt-details th, .receipt-details td {
            border: none;
            padding: 12px;
            font-size: 0.9rem;
        }
        .receipt-details th {
            background: linear-gradient(90deg, #87CEFA, #5f9ea0); /* Sky Blue to Cadet Blue */
            color: white;
            font-weight: 600;
        }
        .receipt-details td {
            background: #ffffff;
        }
        /* New styles for sales reports */
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
            color: #5f9ea0; /* Cadet Blue */
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        .report-card .report-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #87CEFA; /* Sky Blue */
            margin-bottom: 5px;
        }
        .report-card .report-label {
            font-size: 0.85rem;
            color: #666;
        }
        .footer {
            font-size: 0.85rem;
            color: #FFD700; /* Gold */
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
        /* Mobile specific adjustments for form */
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
        </div>

        <div class="form-section">
            <h3 class="mb-4" style="font-family: 'Playfair Display', serif; color: #333; text-align: center;">Create New Receipt</h3>
            <form id="receiptForm" method="POST" action="process_receipt.php" class="needs-validation" novalidate>
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
                            <div class="invalid-feedback">Please enter a valid price</div>
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
                <table class="table" id="previewTable">
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
                                // Add data-receipt-id attribute for JavaScript to easily retrieve the ID
                                echo "<tr data-receipt-id='" . htmlspecialchars($row['id']) . "'>";
                                echo "<td>" . htmlspecialchars($row['item']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                                echo "<td>₦" . htmlspecialchars(number_format($row['price'], 2)) . "</td>";
                                echo "<td>₦" . htmlspecialchars(number_format($row['subtotal'], 2)) . "</td>";
                                // Add the "Generate PDF" button for each fetched transaction
                                echo "<td><button type='button' class='btn btn-info btn-sm generate-fetched-pdf' data-item-name='" . htmlspecialchars($row['item']) . "' data-quantity='" . htmlspecialchars($row['quantity']) . "' data-price='" . htmlspecialchars($row['price']) . "' data-subtotal='" . htmlspecialchars($row['subtotal']) . "' title='Generate PDF for this transaction'><i class='fas fa-file-pdf'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No recent receipts.</td></tr>"; // colspan adjusted for the 5 columns
                        }
                        ?>
                    </tbody>
                </table>
            </div> </div>

        <div class="sales-reports">
            <h3 class="mb-4" style="font-family: 'Playfair Display', serif; color: #333;">Sales Reports</h3>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <h5>Current Week</h5>
                        <p class="report-value">₦<?php echo number_format($current_week_report['revenue'], 2); ?></p>
                        <p class="report-label"><?php echo $current_week_report['items_sold']; ?> Sales Entries</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <h5>Previous Week</h5>
                        <p class="report-value">₦<?php echo number_format($previous_week_report['revenue'], 2); ?></p>
                        <p class="report-label"><?php echo $previous_week_report['items_sold']; ?> Sales Entries</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <h5>Current Month</h5>
                        <p class="report-value">₦<?php echo number_format($current_month_report['revenue'], 2); ?></p>
                        <p class="report-label"><?php echo $current_month_report['items_sold']; ?> Sales Entries</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="report-card">
                        <h5>Previous Month</h5>
                        <p class="report-value">₦<?php echo number_format($previous_month_report['revenue'], 2); ?></p>
                        <p class="report-label"><?php echo $previous_month_report['items_sold']; ?> Sales Entries</p>
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
        /**
         * Displays a transient toast message on the screen.
         * @param {string} message - The message to display.
         */
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast-message';
            toast.textContent = message;
            document.body.appendChild(toast);
            // Set a timeout to remove the toast after its animation duration
            setTimeout(() => {
                toast.remove();
            }, 3500); // Remove after 3.5 seconds (animation duration is 3s)
        }

        const itemsContainer = document.getElementById('items');
        const totalSpan = document.getElementById('total');

        // Event listener for input changes within the items container to update the total in real-time.
        itemsContainer.addEventListener('input', updateTotal);

        /**
         * Calculates and updates the total amount based on current item quantities and prices in the form.
         */
        function updateTotal() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const quantityInput = row.querySelector('input[name="quantity[]"]');
                const priceInput = row.querySelector('input[name="price[]"]');

                // Use optional chaining (?) for robustness in case inputs are momentarily missing or null
                const quantity = parseFloat(quantityInput?.value) || 0;
                const price = parseFloat(priceInput?.value) || 0;
                total += quantity * price;
            });
            totalSpan.textContent = total.toFixed(2);
        }

        updateTotal(); // Call once on page load to set the initial total

        // Add new item row to the form dynamically
        let itemIndex = document.querySelectorAll('.item-row').length; // Tracks the current index for new items
        document.getElementById('addItem').addEventListener('click', () => {
            const itemRow = document.createElement('div');
            // Apply Bootstrap classes for responsive layout and styling
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
                    <div class="invalid-feedback">Please enter a valid price</div>
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
            itemIndex++; // Increment for the next item
            updateTotal(); // Recalculate total after adding a new item
        });

        // Event delegation for removing item rows. Listens on the parent container.
        itemsContainer.addEventListener('click', (e) => {
            const removeButton = e.target.closest('.remove-item');
            if (removeButton) {
                const rowToRemove = removeButton.closest('.item-row');
                // Prevent removing the last item row to ensure the form always has an input
                if (document.querySelectorAll('.item-row').length > 1) {
                    rowToRemove.remove();
                    updateTotal(); // Recalculate total after removing an item
                } else {
                    showToast("Cannot remove the last item.");
                }
            }
        });

        /**
         * Generates a PDF receipt using jsPDF.
         * Incorporates company details, itemized list, total, and a footer.
         * @param {Array<Object>} itemsData - An array of item objects, each with {name, quantity, price, subtotal}.
         * @param {number} totalAmount - The calculated total amount for the entire receipt.
         * @param {string} receiptId - The unique ID for the receipt, used in the PDF filename and content.
         */
        function generateReceiptPDF(itemsData, totalAmount, receiptId) {
            // Ensure jsPDF library is loaded before attempting to use it
            if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
                console.error('jsPDF library not loaded. Cannot generate PDF.');
                showToast('PDF generation failed: Library not loaded. Please try again.');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            let yOffset = 15; // Starting Y-position for top margin

            // Define a consistent color palette using values from your CSS
            const primaryColor = [135, 206, 250]; // Sky Blue
            const accentColor = [95, 158, 160];  // Cadet Blue
            const textColor = [50, 50, 50];      // Dark grey for main text
            const lightTextColor = [150, 150, 150]; // Lighter grey for subtle text
            const white = [255, 255, 255];       // White for contrast

            // --- Logo Section ---
            const logoPath = 'assets/images/Divine-love-logo.jpg';
            const logoWidth = 20; // in mm
            const logoHeight = 20; // in mm
            try {
                // Attempt to add the logo. If the path is incorrect or the image is inaccessible,
                // a warning is logged, and text is used as a fallback.
                doc.addImage(logoPath, 'JPEG', 20, 15, logoWidth, logoHeight);
            } catch (e) {
                console.warn(`Logo not found or could not be loaded from "${logoPath}". Ensure the path is correct and the image is accessible.`, e);
                doc.setFontSize(8);
                doc.setTextColor(lightTextColor[0], lightTextColor[1], lightTextColor[2]);
                doc.text('Divine-Love Bags', 20, 25); // Placeholder text if logo fails
            }

            // --- Company Header ---
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(24);
            doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
            // Center the title horizontally
            doc.text('Divine-Love Bags', doc.internal.pageSize.width / 2, yOffset, { align: 'center' });
            yOffset += 10;

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9); // Smaller font for contact information
            doc.setTextColor(lightTextColor[0], lightTextColor[1], lightTextColor[2]);
            doc.text('25, Opeolu Street, Off SUBEB Ijeun Titun, Abeokuta, Ogun State', doc.internal.pageSize.width / 2, yOffset, { align: 'center' });
            yOffset += 4;
            doc.text('Call/WhatsApp: 08132686523', doc.internal.pageSize.width / 2, yOffset, { align: 'center' });
            yOffset += 12; // More vertical space after contact info

            // Subtle separator line under the header
            doc.setDrawColor(accentColor[0], accentColor[1], accentColor[2]);
            doc.setLineWidth(0.5);
            doc.line(20, yOffset, doc.internal.pageSize.width - 20, yOffset); // Draws a line across the page
            yOffset += 10; // Space after the line

            // --- Receipt Details (Date and Receipt Number) ---
            doc.setFontSize(10);
            doc.setTextColor(textColor[0], textColor[1], textColor[2]);
            // Format date as DD/MM/YYYY, suitable for Nigeria (en-GB locale)
            doc.text(`Date: ${new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' })}`, 20, yOffset);
            // Align receipt number to the right
            doc.text(`Receipt No: ${receiptId}`, doc.internal.pageSize.width - 20, yOffset, { align: 'right' });
            yOffset += 15; // Space before items table

            // --- Items Table Header ---
            doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]); // Header background color
            doc.rect(15, yOffset - 2, doc.internal.pageSize.width - 30, 8, 'F'); // Filled rectangle for header row
            doc.setTextColor(white[0], white[1], white[2]); // White text for header
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('Item', 20, yOffset + 3);
            doc.text('Qty', 85, yOffset + 3);
            doc.text('Price (₦)', 125, yOffset + 3);
            doc.text('Subtotal (₦)', doc.internal.pageSize.width - 20, yOffset + 3, { align: 'right' });
            yOffset += 8; // Adjust yOffset after header row

            // --- Items Table Rows ---
            doc.setTextColor(textColor[0], textColor[1], textColor[2]);
            doc.setFontSize(9);
            doc.setFont('helvetica', 'normal');
            let rowCounter = 0;
            itemsData.forEach(item => {
                // Check for page overflow before drawing the row, add new page if needed
                if (yOffset > doc.internal.pageSize.height - 40) { // Keep space for footer and prevent content cutoff
                    doc.addPage();
                    yOffset = 15; // Reset y for new page

                    // Re-add table header on the new page for continuity
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

                // Draw item details for the current row
                doc.text(item.name.substring(0, 30), 20, yOffset + 4); // Truncate long names, add padding
                doc.text(item.quantity.toString(), 85, yOffset + 4);
                doc.text(item.price.toFixed(2), 125, yOffset + 4);
                doc.text(item.subtotal.toFixed(2), doc.internal.pageSize.width - 20, yOffset + 4, { align: 'right' });

                // Add subtle line separator for each item row (except after the very last one)
                if (rowCounter < itemsData.length - 1) {
                    doc.setDrawColor(230, 230, 230); // Very light grey line
                    doc.setLineWidth(0.2);
                    doc.line(15, yOffset + 8, doc.internal.pageSize.width - 15, yOffset + 8);
                }

                yOffset += 10; // Space between item rows
                rowCounter++;
            });

            // --- Total Amount Section ---
            yOffset += 10; // Extra space before the total line
            doc.setDrawColor(accentColor[0], accentColor[1], accentColor[2]);
            doc.setLineWidth(0.8); // Thicker line before total for emphasis
            // Draw a shorter line that aligns to the right, just above the total text
            doc.line(doc.internal.pageSize.width - 70, yOffset, doc.internal.pageSize.width - 20, yOffset);
            yOffset += 5;

            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('Total:', doc.internal.pageSize.width - 60, yOffset, { align: 'right' });
            doc.text(`₦${totalAmount.toFixed(2)}`, doc.internal.pageSize.width - 20, yOffset, { align: 'right' });
            yOffset += 15;

            // --- Footer Message ---
            doc.setFontSize(10);
            doc.setFont('helvetica', 'italic');
            doc.setTextColor(accentColor[0], accentColor[1], accentColor[2]); // Use accent color for footer text
            doc.text('Thank you for patronizing us. We are expecting you next time.', doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 20, { align: 'center' }); // Position near bottom center

            // --- Watermark (Subtle Background Text) ---
            // This loop ensures the watermark is applied to all pages, positioned behind other content.
            for (let i = 1; i <= doc.internal.pages.length; i++) {
                doc.setPage(i);
                doc.setTextColor(230, 245, 255); // Very light blue for a subtle watermark effect
                doc.setFontSize(60);
                doc.setFont('helvetica', 'bold');
                // Centered and rotated watermark text
                doc.text('DIVINE-LOVE BAGS', doc.internal.pageSize.width / 2, doc.internal.pageSize.height / 2, { align: 'center', angle: 45 });
            }
            doc.setTextColor(textColor[0], textColor[1], textColor[2]); // Reset text color for foreground content

            // Save the generated PDF with a unique filename
            doc.save(`Divine-Love-Bags-Receipt-${receiptId}.pdf`);
        }

        // Event listener for "Generate Single Receipt" button clicks using event delegation on the items container.
        itemsContainer.addEventListener('click', async (e) => {
            const generateButton = e.target.closest('.generate-single-receipt');
            if (generateButton) {
                const row = generateButton.closest('.item-row');
                const itemInput = row.querySelector('input[name="item[]"]');
                const quantityInput = row.querySelector('input[name="quantity[]"]');
                const priceInput = row.querySelector('input[name="price[]"]');

                // Perform client-side validation for the specific item row
                if (!itemInput.checkValidity() || !quantityInput.checkValidity() || !priceInput.checkValidity()) {
                    // Manually report validity for inputs to show Bootstrap's validation messages
                    itemInput.reportValidity();
                    quantityInput.reportValidity();
                    priceInput.reportValidity();
                    showToast('Please fill in valid item, quantity, and price for the item you want to generate.');
                    return; // Stop execution if validation fails
                }

                // Extract data from the current row
                const item = itemInput.value;
                const quantity = parseFloat(quantityInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const subtotal = quantity * price;

                // Prepare FormData for AJAX submission
                const formData = new FormData();
                formData.append('item', item);
                formData.append('quantity', quantity);
                formData.append('price', price);
                formData.append('subtotal', subtotal);
                formData.append('single_item', 'true'); // Indicate to PHP that it's a single item save

                try {
                    const response = await fetch('process_receipt.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json(); // Parse JSON response from PHP

                    if (data.success) {
                        showToast(`Item receipt saved and generated! Receipt No: ${data.receipt_id}`);

                        // Generate PDF immediately after successful database save
                        generateReceiptPDF([{ name: item, quantity: quantity, price: price, subtotal: subtotal }], subtotal, data.receipt_id);

                        // Dynamically update the "Recent Receipts" table
                        const previewTableBody = document.querySelector('#previewTable tbody');
                        const newRow = document.createElement('tr');
                        newRow.setAttribute('data-receipt-id', data.receipt_id); // Attach the receipt ID to the row
                        newRow.innerHTML = `
                            <td>${item}</td>
                            <td>${quantity}</td>
                            <td>₦${price.toFixed(2)}</td>
                            <td>₦${subtotal.toFixed(2)}</td>
                            <td><button type='button' class='btn btn-info btn-sm generate-fetched-pdf' data-item-name='${item}' data-quantity='${quantity}' data-price='${price}' data-subtotal='${subtotal}' title='Generate PDF for this transaction'><i class='fas fa-file-pdf'></i></button></td>
                        `;
                        previewTableBody.prepend(newRow); // Add the new row to the top of the table

                        // Keep only the last 5 rows to maintain the display limit
                        while(previewTableBody.children.length > 5) {
                            previewTableBody.removeChild(previewTableBody.lastChild);
                        }

                    } else {
                        showToast(`Error saving item: ${data.message || 'Unknown error occurred.'}`);
                    }
                } catch (error) {
                    console.error('Error saving single item:', error);
                    showToast('An error occurred while saving the item. Please check your internet connection.');
                }
            }
        });

        // Event listener for the main form submission (Save & Generate All Receipts)
        document.getElementById('receiptForm').addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevent default browser form submission

            const form = e.target;
            // Trigger Bootstrap's client-side validation for the entire form
            if (!form.checkValidity()) {
                e.stopPropagation(); // Stop event propagation if form is invalid
                form.classList.add('was-validated'); // Add class to show validation feedback
                showToast('Please correct the highlighted errors in the form before proceeding.');
                return; // Exit the function if validation fails
            }
            form.classList.add('was-validated'); // Add validated class even if valid to be consistent

            // --- CRITICAL STEP: Capture data needed for PDF *before* asynchronous operations ---
            // This ensures the PDF is generated with the exact data submitted by the user,
            // even if inputs are changed immediately after submission.
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
            // --- END CRITICAL STEP ---

            const formData = new FormData(form); // Collect all form data for submission to PHP

            try {
                const response = await fetch('process_receipt.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json(); // Parse the JSON response

                if (data.success) {
                    showToast(`All items saved and PDF generated! Receipt No: ${data.receipt_id}`);
                    // Call PDF generation here, immediately after successful database save
                    generateReceiptPDF(itemsDataForPdf, totalAmountForPdf, data.receipt_id);

                    // Delay page reload to allow PDF download to initiate in the browser
                    setTimeout(() => {
                        location.reload(); // Reload the page to refresh the "Recent Receipts" table and reports
                    }, 2000); // Increased delay for better user experience with downloads

                } else {
                    showToast(`Error saving all items: ${data.message || 'Unknown error occurred.'}`);
                }
            } catch (error) {
                console.error('Error saving all items:', error);
                showToast('An error occurred while saving all items. Please check your internet connection.');
            }
        });

        // Event listener for "Generate PDF" buttons on fetched transactions in the recent receipts table.
        // Uses event delegation on the #previewTable.
        document.getElementById('previewTable').addEventListener('click', (e) => {
            const generateButton = e.target.closest('.generate-fetched-pdf');
            if (generateButton) {
                // Retrieve data from data attributes on the button/row
                const item = generateButton.dataset.itemName;
                const quantity = parseFloat(generateButton.dataset.quantity);
                const price = parseFloat(generateButton.dataset.price);
                const subtotal = parseFloat(generateButton.dataset.subtotal);
                // Get the receipt ID from the parent table row's data attribute
                const receiptId = generateButton.closest('tr').dataset.receiptId;

                const itemsData = [{ name: item, quantity: quantity, price: price, subtotal: subtotal }];
                generateReceiptPDF(itemsData, subtotal, receiptId); // Pass the correct receipt ID
                showToast(`PDF generated for Receipt No: ${receiptId}`);
            }
        });

        // Bootstrap's built-in client-side validation logic.
        // This makes sure Bootstrap's validation feedback (invalid-feedback, was-validated classes) works.
        (function () {
          'use strict'
          const forms = document.querySelectorAll('.needs-validation')
          Array.prototype.slice.call(forms)
            .forEach(function (form) {
              form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                  event.preventDefault()
                  event.stopPropagation()
                }
                form.classList.add('was-validated')
              }, false)
            })
        })()
    </script>
</body>
</html>