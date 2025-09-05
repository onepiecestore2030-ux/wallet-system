<?php
// -------------------------------
// اتصال قاعدة البيانات
// -------------------------------
$host = 'dpg-d2tkf0nfte5s73aba54g-a';
$dbname = 'wallet_db_69lv';
$username = 'wallet_db_69lv_user';
$password = '47QlOGPiPeNtR9zO97KTqiT1jGWgTKd8'; // ⚠️ غيّرها إذا كانت مختلفة

$conn = new mysqli($host, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// تعيين الترميز
$conn->set_charset('utf8mb4');

?>
