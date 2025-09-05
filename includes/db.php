<?php
$host = $_ENV['DB_HOST'] ?? 'dpg-d2tkf0nfte5s73aba54g-a.oregon-postgres.render.com';
$dbname = $_ENV['DB_NAME'] ?? 'wallet_db_69lv';
$user = $_ENV['DB_USER'] ?? 'wallet_db_69lv_user';
$password = $_ENV['DB_PASS'] ?? '47QlOGPiPeNtR9zO97KTqiT1jGWgTKd8';

$connectionString = "host=$host dbname=$dbname user=$user password=$password port=5432";
$conn = pg_connect($connectionString);

if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات");
}
// لا تكتب أي شيء بعد هذا السطر
// لا تكتب ?> في النهاية
