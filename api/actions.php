<?php
// api/actions.php - MMK Hub Action Dispatcher
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = get_db();

switch ($action) {

    case 'set_channel_mode':
        $mode = $_GET['mode'] ?? 'retail';
        $_SESSION['channel_mode'] = ($mode === 'wholesale') ? 'wholesale' : 'retail';
        echo json_encode(['success' => true, 'mode' => $_SESSION['channel_mode']]);
        exit;

    case 'set_demo_role':
        $role = $_GET['role'] ?? 'retail';
        $validRoles = ['retail', 'wholesale_pending', 'wholesale_approved', 'admin'];
        if (in_array($role, $validRoles)) {
            $_SESSION['demo_role'] = $role;
            if ($role === 'wholesale_approved') {
                $_SESSION['channel_mode'] = 'wholesale';
            } elseif ($role === 'retail') {
                $_SESSION['channel_mode'] = 'retail';
            }
        }
        $redirect = $_SERVER['HTTP_REFERER'] ?? '../index.php';
        header("Location: $redirect");
        exit;

    case 'create_order':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
            exit;
        }

        $orderCode = 'MMK-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $customerName = trim($input['customer_name'] ?? '');
        $customerPhone = trim($input['customer_phone'] ?? '');
        $customerEmail = trim($input['customer_email'] ?? '');
        $city = trim($input['city'] ?? 'Douala');
        $address = trim($input['delivery_address'] ?? '');
        $deliveryMethod = trim($input['delivery_method'] ?? 'standard');
        $deliveryFee = floatval($input['delivery_fee'] ?? 0);
        $orderType = trim($input['order_type'] ?? 'retail');
        $subtotal = floatval($input['subtotal'] ?? 0);
        $totalAmount = floatval($input['total_amount'] ?? 0);
        $depositAmount = floatval($input['deposit_amount'] ?? 0);
        $balanceAmount = floatval($input['balance_amount'] ?? 0);
        $paymentMethod = trim($input['payment_method'] ?? 'momo');
        $paymentRef = trim($input['payment_ref'] ?? '');
        $items = $input['items'] ?? [];

        if (empty($customerName) || empty($customerPhone) || empty($items)) {
            echo json_encode(['success' => false, 'error' => 'Missing required customer details or items']);
            exit;
        }

        $paymentStatus = 'pending';
        if ($paymentMethod === 'cod') {
            $paymentStatus = 'pending';
        } elseif (str_contains($paymentMethod, 'deposit')) {
            $paymentStatus = 'deposit_paid';
        } else {
            $paymentStatus = 'fully_paid';
        }

        try {
            $stmt = $db->prepare("INSERT INTO orders (
                order_code, customer_name, customer_phone, customer_email, city, delivery_address,
                delivery_method, delivery_fee, order_type, subtotal, total_amount, deposit_amount,
                balance_amount, payment_method, payment_status, fulfillment_status, payment_ref
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'received', ?)");

            $stmt->execute([
                $orderCode, $customerName, $customerPhone, $customerEmail, $city, $address,
                $deliveryMethod, $deliveryFee, $orderType, $subtotal, $totalAmount, $depositAmount,
                $balanceAmount, $paymentMethod, $paymentStatus, $paymentRef
            ]);

            $orderId = $db->lastInsertId();

            $stmtItem = $db->prepare("INSERT INTO order_items (
                order_id, product_id, product_name, unit_price, quantity, subtotal, is_service, service_date, service_slot
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($items as $item) {
                $stmtItem->execute([
                    $orderId,
                    $item['id'] ?? 0,
                    $item['name'] ?? 'Item',
                    $item['price'] ?? 0,
                    $item['qty'] ?? 1,
                    ($item['price'] ?? 0) * ($item['qty'] ?? 1),
                    $item['is_service'] ?? 0,
                    $item['service_date'] ?? null,
                    $item['service_slot'] ?? null
                ]);
            }

            echo json_encode([
                'success' => true,
                'order_code' => $orderCode,
                'order_id' => $orderId,
                'total' => $totalAmount,
                'deposit' => $depositAmount,
                'balance' => $balanceAmount
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    case 'book_service':
        $serviceId = intval($_POST['service_id'] ?? 0);
        $name = trim($_POST['customer_name'] ?? '');
        $phone = trim($_POST['customer_phone'] ?? '');
        $date = trim($_POST['service_date'] ?? '');
        $slot = trim($_POST['service_slot'] ?? 'Morning (09:00 - 12:00)');
        $tier = trim($_POST['tier'] ?? 'Standard');
        $address = trim($_POST['address'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (!$serviceId || !$name || !$phone || !$date || !$address) {
            echo json_encode(['success' => false, 'error' => 'Please fill all required booking fields']);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO service_bookings (
                service_id, customer_name, customer_phone, service_date, service_slot, tier, address, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$serviceId, $name, $phone, $date, $slot, $tier, $address, $notes]);

            echo json_encode(['success' => true, 'message' => 'Service booking scheduled successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    case 'request_quote':
        $name = trim($_POST['customer_name'] ?? '');
        $phone = trim($_POST['customer_phone'] ?? '');
        $email = trim($_POST['customer_email'] ?? '');
        $company = trim($_POST['company_name'] ?? '');
        $prodName = trim($_POST['product_name'] ?? '');
        $qty = intval($_POST['requested_qty'] ?? 1);
        $targetPrice = floatval($_POST['target_price'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if (!$name || !$phone || !$prodName || !$qty) {
            echo json_encode(['success' => false, 'error' => 'Please complete required quote fields']);
            exit;
        }

        $quoteCode = 'QUO-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

        try {
            $stmt = $db->prepare("INSERT INTO quotes (
                quote_code, customer_name, customer_phone, customer_email, company_name,
                product_name, requested_qty, target_price, message
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$quoteCode, $name, $phone, $email, $company, $prodName, $qty, $targetPrice, $message]);

            echo json_encode(['success' => true, 'quote_code' => $quoteCode]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    case 'register_wholesale':
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $company = trim($_POST['company_name'] ?? '');
        $taxId = trim($_POST['tax_id'] ?? '');
        $city = trim($_POST['city'] ?? 'Douala');
        $address = trim($_POST['address'] ?? '');

        if (!$name || !$phone || !$company || !$taxId) {
            echo json_encode(['success' => false, 'error' => 'All business registration fields are required']);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO users (
                name, phone, email, role, company_name, tax_id, city, address
            ) VALUES (?, ?, ?, 'wholesale_pending', ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $email, $company, $taxId, $city, $address]);

            $_SESSION['demo_role'] = 'wholesale_pending';
            echo json_encode(['success' => true, 'message' => 'Wholesale application submitted for owner review!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Phone number already registered or DB error']);
        }
        exit;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;
}
