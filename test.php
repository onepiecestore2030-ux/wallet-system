<?php
$token = 'liho0pkzrtylk9j2';
$instance_id = 'instance131957';
$to = '+967779349001'; // غيره برقمك
$body = 'تجربة إرسال رسالة من Ultramsg';

$url = "https://api.ultramsg.com/$instance_id/messages/chat";

$data = [
    'token' => $token,
    'to' => $to,
    'body' => $body
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // فقط للتجربة

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<h3 style='color:red;'>خطأ في cURL: $error</h3>";
} else {
    $result = json_decode($response, true);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
}
?>