<?php
// -------------------------------
// 1. تفعيل عرض الأخطاء
// -------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -------------------------------
// 2. بدء الجلسة والتحقق
// -------------------------------
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: index.php");
    exit;
}

// -------------------------------
// 3. تضمين ملف الاتصال
// -------------------------------
require_once 'includes/db.php'; // يجب أن يحتوي $conn

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

// -------------------------------
// 4. جلب رصيد المحفظة
// -------------------------------
$balance = '0.00';
$sql = "SELECT balance FROM wallets WHERE user_id = $1";
$result = pg_query_params($conn, $sql, [$user_id]);

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    $balance = number_format($row['balance'], 2);
}

// -------------------------------
// 5. معالجة طلب إضافة رصيد
// -------------------------------
$deposit_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_deposit'])) {
    $amount = floatval($_POST['amount']);
    $payment_type = $_POST['payment_type'] ?? '';
    $proof_image = '';

    if ($amount <= 0) {
        $deposit_message = '<div class="alert alert-danger">المبلغ يجب أن يكون أكبر من صفر.</div>';
    } elseif ($payment_type !== 'bank' && $payment_type !== 'ajil') {
        $deposit_message = '<div class="alert alert-danger">نوع الدفع غير صحيح.</div>';
    } else {
        $transfer_type = $payment_type === 'bank' ? 'bank' : 'ajil';

        // رفع صورة الإيصال (للحوالة البنكية فقط)
        if ($payment_type === 'bank') {
            if (empty($_FILES['proof_image']['name'])) {
                $deposit_message = '<div class="alert alert-danger">يرجى رفع صورة الإيصال للحوالة البنكية.</div>';
            } else {
                $uploadDir = 'uploads/deposits/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileName = uniqid('deposit_') . '_' . basename($_FILES['proof_image']['name']);
                $targetPath = $uploadDir . $fileName;
                $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

                if (!in_array($imageFileType, $allowedTypes)) {
                    $deposit_message = '<div class="alert alert-danger">يسمح فقط بالملفات: JPG, JPEG, PNG, GIF, PDF.</div>';
                } elseif ($_FILES['proof_image']['size'] > 5000000) {
                    $deposit_message = '<div class="alert alert-danger">الملف كبير جداً. الحد الأقصى 5 ميغابايت.</div>';
                } elseif (move_uploaded_file($_FILES['proof_image']['tmp_name'], $targetPath)) {
                    $proof_image = $targetPath;
                } else {
                    $deposit_message = '<div class="alert alert-danger">فشل في رفع الصورة.</div>';
                }
            }
        }

        // حفظ الطلب في قاعدة البيانات
        if (!$deposit_message) {
            $sql = "INSERT INTO wallet_transactions (user_id, amount, transfer_type, proof_image, status, requested_at) VALUES ($1, $2, $3, $4, 'pending', NOW())";
            $result = pg_query_params($conn, $sql, [$user_id, $amount, $transfer_type, $proof_image]);

            if ($result) {
                $deposit_message = '<div class="alert alert-success">تم إرسال طلبك بنجاح! سيتم مراجعته من قبل الإدارة.</div>';
            } else {
                $deposit_message = '<div class="alert alert-danger">خطأ في حفظ الطلب. حاول لاحقاً.</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>محفظتك - <?= htmlspecialchars($full_name) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard {
            max-width: 1000px;
            margin: 30px auto;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .wallet-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 20px;
        }
        .wallet-card h5 {
            margin: 0;
            opacity: 0.9;
            font-weight: 500;
        }
        .wallet-card .balance {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }
        .section {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .section:last-child {
            border-bottom: none;
        }
        .btn-custom {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
        }
        .table th {
            background: #f1f3f5;
            text-align: center;
        }
        .status-pending {
            background: #fff3cd; color: #856404;
        }
        .status-approved {
            background: #d4edda; color: #155724;
        }
        .status-rejected {
            background: #f8d7da; color: #721c24;
        }
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <div class="header">
            <h2><i class="fas fa-wallet"></i> محفظتك الرقمية</h2>
            <p>مرحباً، <?= htmlspecialchars($full_name) ?> | <a href="logout.php" class="text-white">تسجيل خروج</a></p>
        </div>

        <!-- Wallet Balance -->
        <div class="wallet-card">
            <h5>رصيدك الحالي</h5>
            <div class="balance"><?= $balance ?> <small>ر.س</small></div>
            <p>يمكنك إضافة رصيد لزيادة ميزانيتك للشراء.</p>
        </div>

        <!-- Add Deposit Section -->
        <div class="section">
            <h4><i class="fas fa-plus-circle text-primary"></i> إضافة رصيد</h4>
            <?= $deposit_message ?>

            <form method="POST" enctype="multipart/form-data" id="depositForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">المبلغ (ر.س)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="1" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">نوع الدفع</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_type" value="bank" id="bank" checked onchange="toggleProofField()">
                            <label class="form-check-label" for="bank">
                                <strong>حوالة بنكية</strong> - يُطلب إرفاق صورة الإيصال
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_type" value="ajil" id="ajil" onchange="toggleProofField()">
                            <label class="form-check-label" for="ajil">
                                <strong>دفع آجل</strong> - لا يتطلب إثبات دفع
                            </label>
                        </div>
                    </div>

                    <!-- حقل رفع الصورة -->
                    <div class="col-12" id="proofSection">
                        <label class="form-label">صورة الإيصال (JPG, PNG, PDF)</label>
                        <input type="file" name="proof_image" class="form-control" accept="image/*,application/pdf">
                        <small class="text-muted">يُسمح بملفات حتى 5 ميغابايت</small>
                    </div>

                    <div class="col-12">
                        <button type="submit" name="add_deposit" class="btn btn-custom">
                            <i class="fas fa-paper-plane"></i> إرسال الطلب
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Transaction History -->
        <div class="section">
            <h4><i class="fas fa-history text-info"></i> سجل طلبات الإيداع</h4>

            <!-- Filters -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" id="filter_from" class="form-control" onchange="filterTransactions()">
                </div>
                <div class="col-md-3">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" id="filter_to" class="form-control" onchange="filterTransactions()">
                </div>
                <div class="col-md-3">
                    <label class="form-label">نوع الحوالة</label>
                    <select id="filter_type" class="form-control" onchange="filterTransactions()">
                        <option value="">الكل</option>
                        <option value="bank">حوالة بنكية</option>
                        <option value="ajil">دفع آجل</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button onclick="exportToExcel()" class="btn btn-success w-100">
                        <i class="fas fa-file-excel"></i> تصدير إلى Excel
                    </button>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center" id="transactionsTable">
                    <thead class="table-light">
                        <tr>
                            <th>المبلغ (ر.س)</th>
                            <th>نوع الحوالة</th>
                            <th>الحالة</th>
                            <th>التاريخ والوقت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT amount, transfer_type, status, requested_at FROM wallet_transactions WHERE user_id = $1 ORDER BY requested_at DESC";
                        $result = pg_query_params($conn, $sql, [$user_id]);

                        if (!$result) {
                            echo '<tr><td colspan="4" class="text-danger">خطأ في تحميل السجل.</td></tr>';
                        } elseif (pg_num_rows($result) === 0) {
                            echo '<tr><td colspan="4" class="text-muted">لا توجد طلبات حتى الآن.</td></tr>';
                        } else {
                            $total_amount = 0;
                            while ($t = pg_fetch_assoc($result)) {
                                $type_label = $t['transfer_type'] === 'bank' ? 'حوالة بنكية' : 'دفع آجل';
                                $status_class = '';
                                if ($t['status'] === 'pending') $status_class = 'status-pending';
                                elseif ($t['status'] === 'approved') $status_class = 'status-approved';
                                elseif ($t['status'] === 'rejected') $status_class = 'status-rejected';

                                $total_amount += $t['amount'];

                                echo "<tr data-date='" . date('Y-m-d', strtotime($t['requested_at'])) . "' data-type='{$t['transfer_type']}'>
                                    <td>{$t['amount']}</td>
                                    <td>{$type_label}</td>
                                    <td><span class='badge-status {$status_class}'>{$t['status']}</span></td>
                                    <td>" . date('Y-m-d H:i', strtotime($t['requested_at'])) . "</td>
                                </tr>";
                            }

                            // صف الإجمالي
                            echo "<tr class='fw-bold text-primary'>
                                <td colspan='1'></td>
                                <td colspan='3'>الإجمالي: <span id='totalDisplay'>{$total_amount}</span> ر.س</td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function toggleProofField() {
            const bank = document.getElementById('bank');
            const proofSection = document.getElementById('proofSection');
            const fileInput = document.querySelector('input[name="proof_image"]');
            if (bank.checked) {
                proofSection.style.display = 'block';
                fileInput.setAttribute('required', '');
            } else {
                proofSection.style.display = 'none';
                fileInput.removeAttribute('required');
            }
        }

        function filterTransactions() {
            const from = document.getElementById('filter_from').value;
            const to = document.getElementById('filter_to').value;
            const type = document.getElementById('filter_type').value;
            const rows = document.querySelectorAll('#transactionsTable tbody tr');
            let total = 0;

            rows.forEach(row => {
                if (row.querySelector('#totalDisplay')) return;

                const dateStr = row.getAttribute('data-date');
                const rowType = row.getAttribute('data-type');

                const showDate = (!from || dateStr >= from) && (!to || dateStr <= to);
                const showType = !type || rowType === type;

                if (showDate && showType) {
                    row.style.display = '';
                    const amount = parseFloat(row.cells[0].textContent);
                    if (!isNaN(amount)) total += amount;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('totalDisplay').textContent = total.toFixed(2);
        }

        function exportToExcel() {
            const from = document.getElementById('filter_from').value || 'غير محدد';
            const to = document.getElementById('filter_to').value || 'غير محدد';
            const type = document.getElementById('filter_type').value;
            let typeLabel = 'الكل';
            if (type === 'bank') typeLabel = 'حوالة بنكية';
            else if (type === 'ajil') typeLabel = 'دفع آجل';

            const rows = document.querySelectorAll('#transactionsTable tbody tr');
            const data = [['المبلغ (ر.س)', 'نوع الحوالة', 'الحالة', 'التاريخ والوقت']];
            let total = 0;

            rows.forEach(row => {
                if (row.style.display === 'none' || row.querySelector('#totalDisplay')) return;
                const cells = row.cells;
                const amount = parseFloat(cells[0].textContent);
                if (!isNaN(amount)) total += amount;
                data.push([cells[0].textContent, cells[1].textContent, cells[2].textContent, cells[3].textContent]);
            });

            data.push(['', '', 'الإجمالي', total.toFixed(2) + ' ر.س']);

            let csv = 'نوع الحوالة:,' + typeLabel + '\n';
            csv += 'من تاريخ:,' + from + ',إلى تاريخ:,' + to + '\n\n';
            data.forEach(row => csv += row.join(',') + '\n');

            const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'سجل_الحوالات_' + new Date().toISOString().slice(0, 10) + '.csv';
            a.click();
            URL.revokeObjectURL(url);
        }

        document.addEventListener('DOMContentLoaded', toggleProofField);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
