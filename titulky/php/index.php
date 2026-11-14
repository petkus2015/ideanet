<?php
// Titulky: hostovanie aplikácie na PHP webhostingu (nahranie cez FTP).
// Rozpoznanie reči a export videa bežia v prehliadači návštevníka, server iba zobrazí stránku.

$forceHttps = true; // model sa v prehliadači sťahuje spoľahlivo len zo stránky cez https

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443);
$host = $_SERVER['HTTP_HOST'] ?? '';
$local = preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host); // lokálne skúšanie bez https
if ($forceHttps && !$https && !$local && preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$/', $host)) {
    header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

$file = __DIR__ . '/app.html';
if (!is_file($file)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Chýba súbor app.html. Nahraj ho do rovnakého priečinka ako index.php.';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-cache');
readfile($file);
