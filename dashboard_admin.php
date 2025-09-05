<?php
// -------------------------------
// 1. تفعيل عرض الأخطاء
// -------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -------------------------------
// 2. التحقق من الجلسة
// -------------------------------
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// -------------------------------
// 3. تضمين اتصال قاعدة البيانات
// -------------------------------
require_once '../includes/db.php'; // يستخدم $conn

// -------------------------------
// 4. تحديد الصفحة
// -------------------------------
$page = $_GET['page'] ?? 'dashboard';
$skin = $_COOKIE['skin'] ?? 'light';
$body_class = $skin === 'dark' ? 'bg-dark text-light' : 'bg-light';
$card_class = $skin === 'dark' ? 'bg-secondary text-light' : 'bg-white';
$table_class = $skin === 'dark' ? 'table-dark' : '';
$navbar_class = $skin === 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-primary';

// -------------------------------
// 5. معالجة الموافقة أو الرفض (AJAX)
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)$_POST['id'];
    $note = $_POST['note'] ?? '';

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE wallet_transactions SET status = 'approved' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // جلب بيانات المعاملة
            $stmt2 = $conn->prepare("SELECT user_id, amount FROM wallet_transactions WHERE id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $result = $stmt2->get_result();
            if ($row = $result->fetch_assoc()) {
                // تحديث رصيد المحفظة
                $update = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                $update->bind_param("di", $row['amount'], $row['user_id']);
                $update->execute();
                $update->close();
            }
            $stmt2->close();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل التحديث']);
        }
        $stmt->close();
        exit;
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE wallet_transactions SET status = 'rejected', admin_note = ? WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("si", $note, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل التحديث']);
        }
        $stmt->close();
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم - المدير</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ✅ تم إزالة المسافات الزائدة -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background: #34495e;
            color: #ecf0f1;
            padding: 0;
        }
        .sidebar a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 12px 25px;
            display: block;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #2980b9;
            border-left: 3px solid #fff;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        .stat-card {
            text-align: center;
            padding: 20px;
            font-size: 1.1rem;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 10px 0;
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
        .img-thumbnail {
            cursor: pointer;
            width: 60px;
        }
    </style>
</head>
<body class="<?= $body_class ?>">

    <!-- Header -->
    <nav class="navbar <?= $navbar_class ?> sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand text-white">لوحة التحكم</span>
            <div class="d-flex">
                <button class="btn btn-sm btn-outline-light me-2" onclick="toggleSkin()">
                    <i class="fas fa-moon"></i> <span id="skinText"><?= $skin === 'dark' ? 'فاتح' : 'داكن' ?></span>
                </button>
                <a href="../logout.php" class="btn btn-sm btn-outline-light">تسجيل خروج</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> التقارير
                </a>
                <a href="?page=transactions" class="<?= $page === 'transactions' ? 'active' : '' ?>">
                    <i class="fas fa-exchange-alt"></i> إدارة الطلبات
                </a>
                <a href="?page=users" class="<?= $page === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> إدارة المستخدمين
                </a>
                <a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> الضبط
                </a>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 mt-4">

                <!-- التقارير -->
                <?php if ($page === 'dashboard'): ?>
                    <h3><i class="fas fa-chart-line text-info"></i> لوحة التقارير</h3>
                    <div class="row">
                        <?php
                        // إجمالي الطلبات
                        $stmt = $conn->query("SELECT COUNT(*) as total FROM wallet_transactions");
                        $total = $stmt->fetch_assoc()['total'];

                        // طلبات معلقة
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM wallet_transactions WHERE status = 'pending'");
                        $pending = $stmt->fetch_assoc()['count'];

                        // المبالغ المعتمدة
                        $stmt = $conn->query("SELECT SUM(amount) as sum FROM wallet_transactions WHERE status = 'approved'");
                        $approved_sum = $stmt->fetch_assoc()['sum'] ?? 0;

                        // عدد العملاء
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
                        $customers = $stmt->fetch_assoc()['count'];
                        ?>

                        <div class="col-md-6 col-lg-3">
                            <div class="card <?= $card_class ?>">
                                <div class="stat-card">
                                    <i class="fas fa-exchange-alt text-primary"></i>
                                    <div class="stat-value"><?= $total ?></div>
                                    <div>إجمالي الطلبات</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card <?= $card_class ?>">
                                <div class="stat-card">
                                    <i class="fas fa-clock text-warning"></i>
                                    <div class="stat-value"><?= $pending ?></div>
                                    <div>طلبات معلقة</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card <?= $card_class ?>">
                                <div class="stat-card">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <div class="stat-value"><?= number_format($approved_sum, 2) ?> ر.س</div>
                                    <div>المبالغ المعتمدة</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card <?= $card_class ?>">
                                <div class="stat-card">
                                    <i class="fas fa-user-friends text-info"></i>
                                    <div class="stat-value"><?= $customers ?></div>
                                    <div>العملاء</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card <?= $card_class ?>">
                                <div class="card-body">
                                    <h5>توزيع الحالة</h5>
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card <?= $card_class ?>">
                                <div class="card-body">
                                    <h5>نوع الحوالة</h5>
                                    <canvas id="typeChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        // مخطط الحالة
                        new Chart(document.getElementById('statusChart'), {
                            type: 'pie',
                            data: {
                                labels: ['معلق', 'موافق عليه', 'مرفوض'],
                                datasets: [{
                                    data: [<?= $pending ?>, <?= $conn->query("SELECT COUNT(*) FROM wallet_transactions WHERE status='approved'")->fetch_assoc()['COUNT(*)'] ?>, <?= $conn->query("SELECT COUNT(*) FROM wallet_transactions WHERE status='rejected'")->fetch_assoc()['COUNT(*)'] ?>],
                                    backgroundColor: ['#ffc107', '#28a745', '#dc3545']
                                }]
                            }
                        });

                        // مخطط النوع
                        fetch('?page=api&type=dash')
                            .then(r => r.json())
                            .then(data => {
                                new Chart(document.getElementById('typeChart'), {
                                    type: 'doughnut',
                                    data: {
                                        labels: ['حوالة بنكية', 'دفع آجل'],
                                        datasets: [{
                                            data: [
                                                <?= $conn->query("SELECT COUNT(*) FROM wallet_transactions WHERE transfer_type='bank'")->fetch_assoc()['COUNT(*)'] ?>,
                                                <?= $conn->query("SELECT COUNT(*) FROM wallet_transactions WHERE transfer_type='ajil'")->fetch_assoc()['COUNT(*)'] ?>
                                            ],
                                            backgroundColor: ['#007bff', '#6f42c1']
                                        }]
                                    }
                                });
                            });
                    </script>

                <!-- إدارة الطلبات -->
                <?php elseif ($page === 'transactions'): ?>
                    <h3><i class="fas fa-exchange-alt text-primary"></i> إدارة الطلبات</h3>
                    <div class="card <?= $card_class ?>">
                        <div class="card-body">
                            <table class="table table-hover text-center <?= $table_class ?>">
                                <thead>
                                    <tr>
                                        <th>العميل</th>
                                        <th>المبلغ</th>
                                        <th>نوع الحوالة</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT 
                                                wt.id, 
                                                wt.amount, 
                                                wt.transfer_type, 
                                                wt.status, 
                                                wt.requested_at, 
                                                wt.proof_image,
                                                u.full_name AS customer_name
                                            FROM wallet_transactions wt
                                            JOIN users u ON wt.user_id = u.id
                                            ORDER BY wt.requested_at DESC";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows === 0): ?>
                                        <tr><td colspan="6" class="text-muted">لا توجد طلبات.</td></tr>
                                    <?php else:
                                        while ($t = $result->fetch_assoc()):
                                            $type_label = $t['transfer_type'] === 'bank' ? 'حوالة بنكية' : 'دفع آجل';
                                            $status_class = $t['status'] === 'pending' ? 'status-pending' : ($t['status'] === 'approved' ? 'status-approved' : 'status-rejected');
                                            $status_text = $t['status'] === 'pending' ? 'معلق' : ($t['status'] === 'approved' ? 'موافق عليه' : 'مرفوض');
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['customer_name']) ?></td>
                                            <td><?= number_format($t['amount'], 2) ?></td>
                                            <td><?= $type_label ?></td>
                                            <td><span class="badge-status <?= $status_class ?>"><?= $status_text ?></span></td>
                                            <td><?= date('Y-m-d H:i', strtotime($t['requested_at'])) ?></td>
                                            <td>
                                                <?php if ($t['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="approve(<?= $t['id'] ?>)">موافقة</button>
                                                    <button class="btn btn-sm btn-danger" onclick="reject(<?= $t['id'] ?>)">رفض</button>
                                                <?php else: ?>
                                                    <small class="text-muted">مُعالَج</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <script>
                        function approve(id) {
                            if (confirm('هل أنت متأكد من الموافقة على هذا الطلب؟')) {
                                fetch('', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: `action=approve&id=${id}`
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('تمت الموافقة!');
                                        location.reload();
                                    } else {
                                        alert('خطأ: ' + (data.message || 'فشل الاتصال'));
                                    }
                                })
                                .catch(err => alert('خطأ: ' + err.message));
                            }
                        }

                        function reject(id) {
                            const note = prompt('سبب الرفض:');
                            fetch('', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `action=reject&id=${id}&note=${encodeURIComponent(note || 'تم الرفض')}`
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    alert('تم الرفض!');
                                    location.reload();
                                } else {
                                    alert('خطأ: ' + (data.message || 'فشل الاتصال'));
                                }
                            })
                            .catch(err => alert('خطأ: ' + err.message));
                        }
                    </script>

                <?php elseif ($page === 'users'): ?>
                    <h3><i class="fas fa-users text-success"></i> إدارة المستخدمين</h3>
                    <div class="card <?= $card_class ?>">
                        <div class="card-body">
                            <p>قريبًا...</p>
                        </div>
                    </div>

                <?php elseif ($page === 'settings'): ?>
                    <h3><i class="fas fa-cog text-warning"></i> الإعدادات</h3>
                    <div class="card <?= $card_class ?>">
                        <div class="card-body">
                            <h5>السمة</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="skin" value="light" id="skin1" <?= $skin !== 'dark' ? 'checked' : '' ?> onchange="setSkin(this.value)">
                                <label class="form-check-label" for="skin1">فاتح</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="skin" value="dark" id="skin2" <?= $skin === 'dark' ? 'checked' : '' ?> onchange="setSkin(this.value)">
                                <label class="form-check-label" for="skin2">داكن</label>
                            </div>
                        </div>
                    </div>

                    <script>
                        function toggleSkin() {
                            const current = document.cookie.match(/skin=([^;]+)/);
                            const newSkin = (current && current[1] === 'dark') ? 'light' : 'dark';
                            document.cookie = "skin=" + newSkin + "; path=/; max-age=31536000";
                            location.reload();
                        }

                        function setSkin(value) {
                            document.cookie = "skin=" + value + "; path=/; max-age=31536000";
                            location.reload();
                        }
                    </script>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ✅ تم إزالة المسافة الزائدة -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>