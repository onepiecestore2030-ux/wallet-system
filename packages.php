<?php
// -------------------------------
// 1. تفعيل عرض الأخطاء
// -------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -------------------------------
// 2. بدء الجلسة
// -------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------
// 3. التحقق من الجلسة
// -------------------------------
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// -------------------------------
// 4. تضمين اتصال قاعدة البيانات
// -------------------------------
require_once 'includes/db.php';

// -------------------------------
// 5. دالة آمنة لإعادة الاتصال دائمًا
// -------------------------------
function getDBConnection() {
    global $host, $username, $password, $dbname;

    // ✅ أعد إنشاء الاتصال دائمًا (لا تعتمد على القديم)
    static $conn = null;

    if ($conn !== null) {
        $conn->close(); // أغلق القديم
    }

    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

// -------------------------------
// 6. جلب بيانات المستخدم والرصيد
// -------------------------------
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$balance = 0;

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $balance = $row['balance'];
}
$stmt->close();

// -------------------------------
// 7. تضمين API الميكروتيك
// -------------------------------
require_once 'includes/RouterosAPI.php';

// -------------------------------
// 8. إعدادات الاتصال بالميكروتيك
// -------------------------------
$API = new RouterosAPI();
$API->port = 8729;
$API->ssl = true;
$API->certless = true;
$API->timeout = 10;
$API->attempts = 3;

$mt_ip = '6b2c0554918c.sn.mynetname.net';
$mt_user = 'leedo';
$mt_pass = 'Wal_712334731';

$connected = false;
$hotspot_profiles = [];
$message = '';

// -------------------------------
// 9. الاتصال بالميكروتيك
// -------------------------------
if ($API->connect($mt_ip, $mt_user, $mt_pass)) {
    $connected = true;
    try {
        $hotspot_profiles = $API->comm('/ip/hotspot/user/profile/print');
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">
                        <strong>فشل في جلب الباقات:</strong> ' . htmlspecialchars($e->getMessage()) . '
                    </div>';
    }
} else {
    $message = '<div class="alert alert-danger">
                    <strong>فشل الاتصال بالميكروتيك:</strong><br>
                    ' . htmlspecialchars($API->error_str) . '<br>
                    تأكد من:<br>
                    - أن <strong>' . htmlspecialchars($mt_ip) . '</strong> قابل للوصول<br>
                    - أن المنفذ <strong>8729</strong> مفتوح<br>
                    - أن <strong>api-ssl</strong> مفعّل في الميكروتيك
                </div>';
}

// ✅ ✅ إعادة الاتصال بقاعدة البيانات بعد الاتصال بالميكروتيك
$conn = getDBConnection();

// -------------------------------
// 10. جلب باقات User Manager
// -------------------------------
$user_manager_packages = [];
$stmt = $conn->prepare("SELECT * FROM packages WHERE status = 'active' ORDER BY sort_order");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_manager_packages[] = $row;
    }
    $stmt->close();
}

// -------------------------------
// 11. معالجة شراء باقة Hotspot
// -------------------------------
if (isset($_POST['buy_hotspot']) && $connected) {
    $conn = getDBConnection(); // ✅ اتصال جديد
    $profile_name = trim($_POST['profile_name']);
    $selected_profile = null;

    foreach ($hotspot_profiles as $p) {
        if (isset($p['name']) && $p['name'] === $profile_name) {
            $selected_profile = $p;
            break;
        }
    }

    if (!$selected_profile) {
        $message = '<div class="alert alert-danger">الباقة غير موجودة.</div>';
    } else {
        $price = 10.00;
        if ($balance < $price) {
            $message = '<div class="alert alert-warning">رصيدك غير كافٍ لشراء هذه الباقة.</div>';
        } else {
            $username = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $password = '';

            $conn->begin_transaction();
            try {
                // خصم من الرصيد
                $stmt = $conn->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
                $stmt->bind_param("di", $price, $user_id);
                $stmt->execute();
                $stmt->close();

                // إنشاء المستخدم في الميكروتيك
                $API->comm('/ip/hotspot/user/add', [
                    'name'     => $username,
                    'password' => $password,
                    'profile'  => $selected_profile['name'],
                    'comment'  => "مستخدم بدون كلمة سر - العميل: $full_name"
                ]);

                // حفظ في المشتريات
                $stmt = $conn->prepare("INSERT INTO purchases (user_id, package_name, username, password, price, package_type, purchased_at) VALUES (?, ?, ?, ?, ?, 'hotspot', NOW())");
                $stmt->bind_param("isssd", $user_id, $selected_profile['name'], $username, $password, $price);
                $stmt->execute();
                $stmt->close();

                $conn->commit();

                $_SESSION['last_purchase'] = [
                    'username' => $username,
                    'password' => $password,
                    'package'  => $selected_profile['name'],
                    'price'    => $price,
                    'date'     => date('Y-m-d H:i:s')
                ];

                header("Location: purchase_success.php");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $message = '<div class="alert alert-danger">
                                <strong>خطأ:</strong> ' . htmlspecialchars($e->getMessage()) . '
                            </div>';
            }
        }
    }
}

// -------------------------------
// 12. معالجة شراء باقة User Manager
// -------------------------------
if (isset($_POST['buy_user_manager'])) {
    $conn = getDBConnection(); // ✅ اتصال جديد
    $pkg_id = (int)$_POST['pkg_id'];
    $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $pkg_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $package = $result->fetch_assoc();
    $stmt->close();

    if (!$package) {
        $message = '<div class="alert alert-danger">الباقة غير موجودة.</div>';
    } else {
        if ($balance < $package['price']) {
            $message = '<div class="alert alert-warning">رصيدك غير كافٍ لشراء هذه الباقة.</div>';
        } else {
            $conn->begin_transaction();
            try {
                // خصم من الرصيد
                $stmt = $conn->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
                $stmt->bind_param("di", $package['price'], $user_id);
                $stmt->execute();
                $stmt->close();

                // حفظ في المشتريات
                $stmt = $conn->prepare("INSERT INTO purchases (user_id, package_name, username, password, price, package_type, purchased_at) VALUES (?, ?, NULL, NULL, ?, 'user_manager', NOW())");
                $stmt->bind_param("isd", $user_id, $package['name'], $package['price']);
                $stmt->execute();
                $stmt->close();

                $conn->commit();

                $_SESSION['last_purchase'] = [
                    'username' => '',
                    'password' => '',
                    'package'  => $package['name'],
                    'price'    => $package['price'],
                    'date'     => date('Y-m-d H:i:s')
                ];

                header("Location: purchase_success.php");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $message = '<div class="alert alert-danger">
                                <strong>خطأ:</strong> ' . htmlspecialchars($e->getMessage()) . '
                            </div>';
            }
        }
    }
}

// -------------------------------
// 13. جلب المشتريات مع فلاتر
// -------------------------------
$filter_date = $_GET['date'] ?? 'all';
$filter_type = $_GET['type'] ?? 'all';

$date_query = '';
if ($filter_date === 'today') {
    $date_query = " AND DATE(purchased_at) = CURDATE() ";
} elseif ($filter_date === 'week') {
    $date_query = " AND YEARWEEK(purchased_at) = YEARWEEK(CURDATE()) ";
} elseif ($filter_date === 'month') {
    $date_query = " AND YEAR(purchased_at) = YEAR(CURDATE()) AND MONTH(purchased_at) = MONTH(CURDATE()) ";
}

$type_query = '';
if ($filter_type === 'hotspot') {
    $type_query = " AND package_type = 'hotspot' ";
} elseif ($filter_type === 'user_manager') {
    $type_query = " AND package_type = 'user_manager' ";
}

$purchases = [];
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT package_name, username, price, purchased_at, package_type 
    FROM purchases 
    WHERE user_id = ? $date_query $type_query
    ORDER BY purchased_at DESC 
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $purchases[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الباقات - المحفظة الرقمية</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 15px 15px;
        }
        .balance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            margin: 20px;
        }
        .package-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin: 15px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .btn-buy {
            background: #0d6efd;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            width: 100%;
        }
        .section-title {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin: 30px 20px 20px;
            color: #333;
        }
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #666;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 3px solid #0d6efd;
        }
        .alert {
            border-radius: 10px;
        }
        .filter-section {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            margin: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <div class="header">
        <h2><i class="fas fa-box-open"></i> باقات الإنترنت</h2>
        <p>مرحباً، <?= htmlspecialchars($full_name) ?> | <a href="dashboard_user.php" class="text-white">العودة</a></p>
    </div>

    <div class="container-fluid">
        <!-- رصيد العميل -->
        <div class="balance-card">
            <h5>رصيدك الحالي</h5>
            <div class="fs-3"><?= number_format($balance, 2) ?> <small>ر.س</small></div>
        </div>

        <!-- رسالة الحالة -->
        <?= $message ?>

        <!-- تبويبات الباقات -->
        <ul class="nav nav-tabs mb-4" id="packageTabs">
            <li class="nav-item w-50 text-center">
                <a class="nav-link active" data-bs-toggle="tab" href="#hotspot">باقات Hotspot</a>
            </li>
            <li class="nav-item w-50 text-center">
                <a class="nav-link" data-bs-toggle="tab" href="#user_manager">باقات User Manager</a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- باقات Hotspot -->
            <div class="tab-pane fade show active" id="hotspot">
                <h4 class="section-title">اختر باقة لشراء</h4>
                <div class="row">
                    <?php if (!$connected): ?>
                        <div class="col-12 text-center text-muted">
                            لا يمكن تحميل الباقات.<br>
                            تأكد من اتصال الميكروتيك عبر الإنترنت.
                        </div>
                    <?php elseif (empty($hotspot_profiles)): ?>
                        <div class="col-12 text-center text-muted">لا توجد باقات Hotspot.</div>
                    <?php else: ?>
                        <?php foreach ($hotspot_profiles as $p): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="package-card">
                                    <h5><?= htmlspecialchars($p['name']) ?></h5>
                                    <p><strong>المدة:</strong> <?= htmlspecialchars($p['validity'] ?? 'غير محدد') ?></p>
                                    <p><strong>السعر:</strong> 
                                        <span class="text-danger">10.00 ر.س</span>
                                    </p>
                                    <form method="POST">
                                        <input type="hidden" name="profile_name" value="<?= htmlspecialchars($p['name']) ?>">
                                        <button type="submit" name="buy_hotspot" class="btn-buy">شراء</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- باقات User Manager -->
            <div class="tab-pane fade" id="user_manager">
                <h4 class="section-title">باقات User Manager</h4>
                <div class="row">
                    <?php if (empty($user_manager_packages)): ?>
                        <div class="col-12 text-center text-muted">لا توجد باقات.</div>
                    <?php else: ?>
                        <?php foreach ($user_manager_packages as $pkg): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="package-card">
                                    <h5><?= htmlspecialchars($pkg['name']) ?></h5>
                                    <p><?= htmlspecialchars($pkg['description']) ?></p>
                                    <p><strong>السعر:</strong> 
                                        <span class="text-danger"><?= number_format($pkg['price'], 2) ?> ر.س</span>
                                    </p>
                                    <form method="POST">
                                        <input type="hidden" name="pkg_id" value="<?= $pkg['id'] ?>">
                                        <button type="submit" name="buy_user_manager" class="btn-buy">شراء</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- فلاتر المشتريات -->
        <div class="filter-section">
            <h5><i class="fas fa-filter"></i> فلترة المشتريات</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">حسب التاريخ</label>
                    <select name="date" class="form-control">
                        <option value="all" <?= $filter_date === 'all' ? 'selected' : '' ?>>الكل</option>
                        <option value="today" <?= $filter_date === 'today' ? 'selected' : '' ?>>اليوم</option>
                        <option value="week" <?= $filter_date === 'week' ? 'selected' : '' ?>>هذا الأسبوع</option>
                        <option value="month" <?= $filter_date === 'month' ? 'selected' : '' ?>>هذا الشهر</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نوع الباقة</label>
                    <select name="type" class="form-control">
                        <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>الكل</option>
                        <option value="hotspot" <?= $filter_type === 'hotspot' ? 'selected' : '' ?>>Hotspot</option>
                        <option value="user_manager" <?= $filter_type === 'user_manager' ? 'selected' : '' ?>>User Manager</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">تصفية</button>
                    <a href="packages.php" class="btn btn-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <!-- سجل المشتريات -->
        <h4 class="section-title">سجل المشتريات</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>الباقة</th>
                        <th>اسم المستخدم</th>
                        <th>النوع</th>
                        <th>السعر (ر.س)</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchases)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">لا توجد مشتريات مطابقة.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($purchases as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['package_name']) ?></td>
                                <td><?= $p['username'] ? '<code>' . htmlspecialchars($p['username']) . '</code>' : '—' ?></td>
                                <td><?= $p['package_type'] == 'hotspot' ? 'Hotspot' : 'User Manager' ?></td>
                                <td><?= number_format($p['price'], 2) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($p['purchased_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>