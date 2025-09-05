<?php
// -------------------------------
// 1. تفعيل عرض الأخطاء (للتنقيح فقط)
// -------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -------------------------------
// 2. بدء الجلسة
// -------------------------------
session_start();

// -------------------------------
// 3. تضمين ملف الاتصال
// -------------------------------
require_once 'includes/db.php'; // يجب أن يحتوي $conn

// -------------------------------
// 4. تعيين الترميز إلى UTF-8
// -------------------------------
header('Content-Type: text/html; charset=utf-8');
$conn->set_charset('utf8mb4');

// -------------------------------
// 5. متغيرات التحكم
// -------------------------------
$error = '';
$success = '';

// -------------------------------
// 6. تسجيل جديد
// -------------------------------
if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = 'customer';

    // التحقق من البيانات
    if (empty($fullname)) {
        $error = "الرجاء إدخال الاسم الكامل.";
    } elseif (empty($username)) {
        $error = "الرجاء إدخال اسم المستخدم.";
    } elseif (empty($email)) {
        $error = "الرجاء إدخال البريد الإلكتروني.";
    } elseif (empty($_POST['password'])) {
        $error = "الرجاء إدخال كلمة المرور.";
    } else {
        // التحقق من وجود المستخدم
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "اسم المستخدم أو البريد مسجل مسبقًا.";
            } else {
                // إدخال المستخدم
                $stmt_insert = $conn->prepare("INSERT INTO users (fullname, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("sssss", $fullname, $username, $email, $password, $role);
                    if ($stmt_insert->execute()) {
                        $success = "تم التسجيل بنجاح! يمكنك الآن تسجيل الدخول.";
                    } else {
                        $error = "حدث خطأ أثناء التسجيل.";
                    }
                    $stmt_insert->close(); // ✅ إغلاق آمن
                } else {
                    $error = "فشل إعداد الاستعلام: " . $conn->error;
                }
            }
            $stmt->close(); // ✅ إغلاق الاستعلام الأول
        } else {
            $error = "فشل إعداد الاستعلام: " . $conn->error;
        }
    }
}

// -------------------------------
// 7. تسجيل الدخول
// -------------------------------
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['fullname']; // ✅ يُستخدم لاحقًا

                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: dashboard_user.php");
                }
                exit;
            } else {
                $error = "كلمة المرور غير صحيحة.";
            }
        } else {
            $error = "اسم المستخدم غير موجود.";
        }
        $stmt->close(); // ✅ إغلاق آمن
    } else {
        $error = "فشل إعداد الاستعلام: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - المحفظة الرقمية</title>
    <!-- ✅ تأكد من عدم وجود مسافات زائدة -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .auth-container {
            max-width: 450px;
            margin: 80px auto;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
        }
        .btn-custom {
            padding: 12px;
            border-radius: 10px;
            width: 100%;
        }
        .btn-register {
            background: #28a745; color: white;
        }
        .btn-login {
            background: #0d6efd; color: white;
        }
        .logo {
            font-size: 3rem;
            text-align: center;
            margin-bottom: 20px;
            color: #6a11cb;
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="logo">
            <i class="fas fa-wallet"></i>
        </div>
        <h3 class="text-center mb-4 text-primary">نظام المحفظة الرقمية</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-4" id="authTabs">
            <li class="nav-item w-50 text-center">
                <a class="nav-link <?= !isset($_POST['login']) ? 'active' : '' ?>" data-bs-toggle="tab" href="#register">تسجيل جديد</a>
            </li>
            <li class="nav-item w-50 text-center">
                <a class="nav-link <?= isset($_POST['login']) ? 'active' : '' ?>" data-bs-toggle="tab" href="#login">تسجيل دخول</a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- تسجيل جديد -->
            <div class="tab-pane fade <?= !isset($_POST['login']) ? 'show active' : '' ?>" id="register">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">الاسم الكامل</label>
                        <input type="text" name="fullname" class="form-control" placeholder="أحمد محمد" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" class="form-control" placeholder="ahmed123" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" placeholder="ahmed@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" name="register" class="btn btn-custom btn-register">
                        <i class="fas fa-user-plus"></i> تسجيل جديد
                    </button>
                </form>
            </div>

            <!-- تسجيل دخول -->
            <div class="tab-pane fade <?= isset($_POST['login']) ? 'show active' : '' ?>" id="login">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" class="form-control" placeholder="ahmed123" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-custom btn-login">
                        <i class="fas fa-sign-in-alt"></i> تسجيل دخول
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ✅ تأكد من عدم وجود مسافات زائدة في نهاية الرابط -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>