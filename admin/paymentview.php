<?php
session_start();
include("config.php");

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("location:index.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Process payment actions
$message = '';
$message_type = '';

if (isset($_POST['refund_payment'])) {
    $payment_id = $_POST['payment_id'];
    $reason = $_POST['reason'] ?? 'Customer request';
    
    // Get payment details
    $payment_query = mysqli_query($con, "SELECT * FROM payments WHERE id = '$payment_id'");
    $payment = mysqli_fetch_assoc($payment_query);
    
    if ($payment && $payment['status'] === 'completed') {
        if ($payment['gateway'] === 'stripe') {
            require_once __DIR__ . '/../includes/payments/StripePayment.php';
            $stripe = new StripePayment();
            $refund = $stripe->refundPayment($payment['payment_intent_id'], $payment['amount']);
            
            if ($refund['success']) {
                // Update payment status
                mysqli_query($con, "UPDATE payments SET status = 'refunded' WHERE id = '$payment_id'");
                
                // Record refund
                mysqli_query($con, "INSERT INTO refunds (payment_id, user_id, gateway, refund_id, amount, reason, status) 
                    VALUES ('$payment_id', '" . $payment['user_id'] . "', 'stripe', '" . $refund['refund_id'] . "', '" . $payment['amount'] . "', '$reason', 'completed')");
                
                $message = 'Refund processed successfully!';
                $message_type = 'success';
            } else {
                $message = 'Refund failed: ' . $refund['error'];
                $message_type = 'danger';
            }
        } else {
            $message = 'M-Pesa refunds require manual processing.';
            $message_type = 'warning';
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$gateway_filter = $_GET['gateway'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT p.*, u.name as user_name, u.email as user_email 
          FROM payments p 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE 1=1";

if ($status_filter) {
    $query .= " AND p.status = '$status_filter'";
}
if ($gateway_filter) {
    $query .= " AND p.gateway = '$gateway_filter'";
}
if ($date_from) {
    $query .= " AND DATE(p.created_at) >= '$date_from'";
}
if ($date_to) {
    $query .= " AND DATE(p.created_at) <= '$date_to'";
}

$query .= " ORDER BY p.created_at DESC";
$payments = mysqli_query($con, $query);

// Get statistics
$stats_query = mysqli_query($con, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN gateway = 'mpesa' THEN 1 ELSE 0 END) as mpesa_count,
        SUM(CASE WHEN gateway = 'stripe' THEN 1 ELSE 0 END) as stripe_count
    FROM payments
");
$stats = mysqli_fetch_assoc($stats_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Management - Home Park Real Estate Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        .stats-card {
            padding: 20px;
            border-radius: 8px;
            color: white;
            margin-bottom: 20px;
        }
        .stats-card.revenue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stats-card.pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stats-card.completed { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stats-card.mpesa { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
    </style>
</head>
<body>

<?php include('header.php'); ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">Payment Management</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card revenue">
                        <h4>Total Revenue</h4>
                        <h2>KSh <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></h2>
                        <p><?php echo $stats['total']; ?> Total Transactions</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card pending">
                        <h4>Pending Payments</h4>
                        <h2><?php echo $stats['pending']; ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card completed">
                        <h4>Completed</h4>
                        <h2><?php echo $stats['completed']; ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card mpesa">
                        <h4>By Gateway</h4>
                        <p>M-Pesa: <?php echo $stats['mpesa_count']; ?></p>
                        <p>Stripe: <?php echo $stats['stripe_count']; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-inline">
                        <div class="form-group mr-3">
                            <label>Status:</label>
                            <select name="status" class="form-control ml-2">
                                <option value="">All</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="form-group mr-3">
                            <label>Gateway:</label>
                            <select name="gateway" class="form-control ml-2">
                                <option value="">All</option>
                                <option value="mpesa" <?php echo $gateway_filter === 'mpesa' ? 'selected' : ''; ?>>M-Pesa</option>
                                <option value="stripe" <?php echo $gateway_filter === 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                            </select>
                        </div>
                        <div class="form-group mr-3">
                            <label>From:</label>
                            <input type="date" name="date_from" class="form-control ml-2" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="form-group mr-3">
                            <label>To:</label>
                            <input type="date" name="date_to" class="form-control ml-2" value="<?php echo $date_to; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="paymentview.php" class="btn btn-secondary ml-2">Reset</a>
                    </form>
                </div>
            </div>
            
            <!-- Payments Table -->
            <div class="card">
                <div class="card-header">
                    <h4>All Payments</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Gateway</th>
                                    <th>Amount</th>
                                    <th>Transaction ID</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($payment = mysqli_fetch_assoc($payments)): ?>
                                <tr>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td>
                                        <?php echo $payment['user_name']; ?><br>
                                        <small><?php echo $payment['user_email']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $payment['gateway'] === 'mpesa' ? 'success' : 'primary'; ?>">
                                            <?php echo strtoupper($payment['gateway']); ?>
                                        </span>
                                    </td>
                                    <td>KSh <?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo $payment['transaction_id'] ?: $payment['checkout_request_id'] ?: $payment['payment_intent_id']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $payment['status'] === 'completed' ? 'success' : 
                                                ($payment['status'] === 'pending' ? 'warning' : 
                                                ($payment['status'] === 'failed' ? 'danger' : 'info')); 
                                        ?>">
                                            <?php echo strtoupper($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y, H:i', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#viewModal<?php echo $payment['id']; ?>">
                                            View
                                        </button>
                                        <?php if ($payment['status'] === 'completed'): ?>
                                        <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#refundModal<?php echo $payment['id']; ?>">
                                            Refund
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?php echo $payment['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5>Payment Details</h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Payment ID:</strong> <?php echo $payment['id']; ?></p>
                                                <p><strong>User:</strong> <?php echo $payment['user_name']; ?> (<?php echo $payment['user_email']; ?>)</p>
                                                <p><strong>Gateway:</strong> <?php echo strtoupper($payment['gateway']); ?></p>
                                                <p><strong>Amount:</strong> KSh <?php echo number_format($payment['amount'], 2); ?></p>
                                                <p><strong>Currency:</strong> <?php echo $payment['currency']; ?></p>
                                                <p><strong>Status:</strong> <?php echo strtoupper($payment['status']); ?></p>
                                                <p><strong>Transaction ID:</strong> <?php echo $payment['transaction_id']; ?></p>
                                                <p><strong>Checkout Request ID:</strong> <?php echo $payment['checkout_request_id']; ?></p>
                                                <p><strong>Payment Intent ID:</strong> <?php echo $payment['payment_intent_id']; ?></p>
                                                <p><strong>Reference:</strong> <?php echo $payment['reference']; ?></p>
                                                <p><strong>Date:</strong> <?php echo $payment['created_at']; ?></p>
                                                <?php if ($payment['receipt_url']): ?>
                                                <p><a href="<?php echo $payment['receipt_url']; ?>" target="_blank" class="btn btn-sm btn-primary">View Receipt</a></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Refund Modal -->
                                <div class="modal fade" id="refundModal<?php echo $payment['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5>Process Refund</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to refund this payment?</p>
                                                    <p><strong>Amount:</strong> KSh <?php echo number_format($payment['amount'], 2); ?></p>
                                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                    <div class="form-group">
                                                        <label>Reason:</label>
                                                        <textarea name="reason" class="form-control" rows="3" placeholder="Enter refund reason"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="refund_payment" class="btn btn-danger">Process Refund</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/jquery-3.2.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>

</body>
</html>
