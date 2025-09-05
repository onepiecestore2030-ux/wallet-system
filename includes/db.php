<?php
// -------------------------------
// اتصال بقاعدة بيانات PostgreSQL
// -------------------------------
$host = 'dpg-d2tkf0nfte5s73aba54g-a.oregon-postgres.render.com';
$dbname = 'wallet_db_69lv';
$user = 'wallet_db_69lv_user';
$password = '47QlOGPiPeNtR9zO97KTqiT1jGWgTKd8'; // ⚠️ استبدل بالكلمة الحقيقية

// اتصال باستخدام pg_connect
$connectionString = "host=$host dbname=$dbname user=$user password=$password port=5432";
$conn = pg_connect($connectionString);

if (!$conn) {
    die("فشل الاتصال: " . pg_last_error());
}

echo "✅ اتصال ناجح بقاعدة البيانات!";
