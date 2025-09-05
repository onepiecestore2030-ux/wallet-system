<?php
// -------------------------------
// اتصال قاعدة البيانات
// -------------------------------
$host = '138.197.183.148';
$dbname = 'jcqbcaejsj';
$username = 'jcqbcaejsj';
$password = 'P4UgK7tzuK'; // ⚠️ غيّرها إذا كانت مختلفة

$conn = new mysqli($host, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// تعيين الترميز
$conn->set_charset('utf8mb4');
?>