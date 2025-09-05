<?php
// -------------------------------
// إضافة مدير إلى النظام
// -------------------------------
require_once 'includes/db.php'; // أو اتصالك بقاعدة البيانات

$fullname = 'وليد محمد';
$username = 'leedo';
$email    = 'admin@site.com';
$password = 'Wal_712334731';
$role     = 'admin';

// تشفير كلمة المرور
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// التحقق من وجود المستخدم
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die("<h3 style='color:red'>❌ هذا المستخدم أو الإيميل مسجل مسبقًا.</h3>");
}

// إدخال المدير
$stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, role, verified, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
$stmt->bind_param("sssss", $fullname, $username, $email, $hashed_password, $role);

if ($stmt->execute()) {
    echo "<h3 style='color:green'>✅ تم إضافة المدير بنجاح!</h3>";
    echo "<p><strong>اسم المستخدم:</strong> leedo</p>";
    echo "<p><strong>كلمة المرور:</strong> Wal_712334731</p>";
    echo "<p><a href='index.php'>الذهاب إلى صفحة الدخول</a></p>";
} else {
    echo "<h3 style='color:red'>❌ خطأ: " . $stmt->error . "</h3>";
}

$stmt->close();
$conn->close();
?>