<?php
// -------------------------------
// إعدادات الاتصال بقاعدة البيانات PostgreSQL
// -------------------------------
$host = 'dpg-d2tkf0nfte5s73aba54g-a.oregon-postgres.render.com'; // استخدم الـ Host الكامل من Render
$dbname = 'wallet_db_69lv';
$user = 'wallet_db_69lv_user';
$password = '47QlOGPiPeNtR9zO97KTqiT1jGWgTKd8'; // ⚠️ غيرها إلى كلمة السر الحقيقية

// إنشاء اتصال
$connectionString = "host=$host dbname=$dbname user=$user password=$password port=5432";
$conn = pg_connect($connectionString);

if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات: " . pg_last_error());
}

echo "<h2>✅ الاتصال ناجح!</h2>";

// -------------------------------
// تعليمات SQL لـ PostgreSQL
// -------------------------------

$queries = [
    // جدول المستخدمين
    "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        fullname VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100),
        password VARCHAR(255) NOT NULL,
        otp VARCHAR(6),
        verified BOOLEAN DEFAULT FALSE,
        role VARCHAR(20) DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );",

    // جدول المحافظ
    "CREATE TABLE IF NOT EXISTS wallets (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL,
        balance DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );",

    // جدول المعاملات
    "CREATE TABLE IF NOT EXISTS transactions (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL,
        type VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT,
        reference_id INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );",

    // جدول الباقات
    "CREATE TABLE IF NOT EXISTS packages (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        sort_order INTEGER DEFAULT 0,
        status VARCHAR(10) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );",

    // جدول المشتريات
    "CREATE TABLE IF NOT EXISTS purchases (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL,
        package_name VARCHAR(100) NOT NULL,
        username VARCHAR(50),
        password VARCHAR(50),
        price DECIMAL(10,2) NOT NULL,
        package_type VARCHAR(20) DEFAULT 'hotspot',
        purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );",

    // جدول طلبات شحن المحفظة
    "CREATE TABLE IF NOT EXISTS wallet_transactions (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        transfer_type VARCHAR(10) NOT NULL,
        proof_image VARCHAR(255),
        status VARCHAR(10) DEFAULT 'pending',
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        admin_note TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );"
];

// -------------------------------
// تنفيذ الأوامر
// -------------------------------
foreach ($queries as $index => $query) {
    $result = pg_query($conn, $query);
    if ($result) {
        echo "<p>✅ تم تنفيذ الاستعلام " . ($index + 1) . "</p>";
    } else {
        echo "<p>❌ خطأ في الاستعلام " . ($index + 1) . ": " . pg_last_error($conn) . "</p>";
    }
}

// -------------------------------
// إدخال بيانات المستخدمين (مثل admin)
// -------------------------------
$users_data = [
    [1, 'محمد علي', 'moha', 'moha@gmail.com', '$2y$10$0mh9m8cX3qdDEJfLHgb1l.Oy/I.42sQ8IbHk5dj7pDaum1Izpxp9m', null, false, 'customer'],
    [2, 'احمد علي', 'ahmed', 'ahmed@gmail.com', '$2y$10$0taJ6IgTM1hFMuqLqei81ege3g8k0F6FR3sByWr4sueOcCUdlaRw6', null, false, 'customer'],
    [4, 'وليد محمد', 'leedo', 'admin@site.com', '$2y$10$T8GS0EC0MJECFK68J/kpQ.vTBt3PoHFxrcCWcLAbaqv6XjXr6XcP6', null, true, 'admin'],
    [5, 'محمد علي', 'moh', 'moh@gmail.com', '$2y$10$vUncz1P5ATCHsPE8d0ObleqCAcMgMwELywMlHpwWvzd01Rs586W1i', null, false, 'customer']
];

foreach ($users_data as $data) {
    $sql = "INSERT INTO users (id, fullname, username, email, password, otp, verified, role) 
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8) 
            ON CONFLICT (id) DO NOTHING;";
    $result = pg_query_params($conn, $sql, $data);
    if ($result) {
        echo "<p>✅ تم إدخال المستخدم: {$data[2]}</p>";
    } else {
        echo "<p>❌ خطأ في إدخال المستخدم {$data[2]}: " . pg_last_error($conn) . "</p>";
    }
}

// -------------------------------
// إدخال بيانات طلبات الشحن
// -------------------------------
$wallet_trans = [
    [1, 2, 5000.00, 'ajil', '', 'pending']
];

foreach ($wallet_trans as $data) {
    $sql = "INSERT INTO wallet_transactions (id, user_id, amount, transfer_type, proof_image, status) 
            VALUES ($1, $2, $3, $4, $5, $6) 
            ON CONFLICT (id) DO NOTHING;";
    $result = pg_query_params($conn, $sql, $data);
    if ($result) {
        echo "<p>✅ تم إدخال طلب شحن: #{$data[0]}</p>";
    } else {
        echo "<p>❌ خطأ في إدخال طلب الشحن: " . pg_last_error($conn) . "</p>";
    }
}

echo "<h3>🎉 تم إنشاء الجداول وإدخال البيانات بنجاح!</h3>";
?>
