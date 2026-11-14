<?php
// Titulky: hostovanie aplikácie na PHP webhostingu (nahranie cez FTP).
// Rozpoznanie reči a export videa bežia v prehliadači návštevníka, server iba zobrazí stránku.
// Diagnostika: otvor adresu s ?diag=1 (napr. https://tvojweb.sk/titulky/?diag=1)

$forceHttps = false; // zapni (true) len ak hosting nesmeruje na https sám; za proxy môže spôsobiť nekonečné presmerovanie

$file = __DIR__ . '/app.html';

if (isset($_GET['diag'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "PHP: " . PHP_VERSION . "\n";
    echo "app.html: " . (is_file($file) ? 'ok, ' . filesize($file) . " B" : 'CHÝBA') . "\n";
    echo "https (HTTPS): " . ($_SERVER['HTTPS'] ?? '-') . "\n";
    echo "X-Forwarded-Proto: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '-') . "\n";
    echo "Host: " . ($_SERVER['HTTP_HOST'] ?? '-') . "\n";
    echo "Priečinok: " . __DIR__ . "\n";
    exit;
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443);
$host = $_SERVER['HTTP_HOST'] ?? '';
$local = preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host);
if ($forceHttps && !$https && !$local && preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$/', $host)) {
    header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 302);
    exit;
}

if (!is_file($file)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Chýba súbor app.html. Nahraj ho do rovnakého priečinka ako index.php.';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache');
echo file_get_contents($file);
