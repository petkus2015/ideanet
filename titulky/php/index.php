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
    echo "Zápis do data/: " . (is_writable(is_dir(__DIR__ . '/data') ? __DIR__ . '/data' : __DIR__) ? 'ok' : 'NIE (nastav práva na zápis)') . "\n";
    echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
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

// ---------- spoločné fonty ----------
// Nahraný font sa uloží na server a je dostupný pre všetkých. Prijíma sa len skutočný súbor fontu
// (TTF, OTF, WOFF, WOFF2 overený podľa obsahu), najviac jeden za týždeň z jednej IP adresy.
// Súbory sú v priečinku data/, ktorý je zvonka zakázaný; fonty sa posielajú len cez ?font=ID.
$dataDir = __DIR__ . '/data';
$fontMaxBytes = 10 * 1024 * 1024; // najväčší povolený font
$fontLimitDays = 7;               // jeden font za týždeň z jednej IP
$fontMaxCount = 500;              // poistka proti zaplneniu disku
$reservedNames = ['inter', 'montserrat', 'poppins', 'bebas neue', 'roboto slab', 'arial', 'georgia'];

function jsonOut($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function dataInit($d) {
    if (!is_dir($d . '/fonts') && !@mkdir($d . '/fonts', 0755, true)) jsonOut(['error' => 'Server nevie vytvoriť priečinok data/. Nastav mu práva na zápis.'], 500);
    $deny = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n";
    if (!is_file($d . '/.htaccess')) @file_put_contents($d . '/.htaccess', $deny);
    if (!is_file($d . '/index.html')) @file_put_contents($d . '/index.html', '');
    if (!is_file($d . '/fonts/index.html')) @file_put_contents($d . '/fonts/index.html', '');
}
// Súkromné súbory (.php) začínajú riadkom s príkazom exit, takže ich obsah z webu neuvidno ani bez .htaccess.
function readJson($f) { $s = @file_get_contents($f); if ($s && substr($s, 0, 5) === '<?php') $s = substr($s, strpos($s, "\n") + 1); $v = $s ? json_decode($s, true) : null; return is_array($v) ? $v : []; }
function writeJson($f, $v) { $t = $f . '.tmp'; $j = json_encode($v, JSON_UNESCAPED_UNICODE); file_put_contents($t, substr($f, -4) === '.php' ? "<?php exit; ?>\n" . $j : $j); rename($t, $f); }
function ipKey($d) { // IP sa neukladá, len jej odtlačok so soľou tohto servera
    $saltFile = $d . '/salt.php';
    $salt = readJson($saltFile)['salt'] ?? '';
    if (!$salt) { $salt = bin2hex(random_bytes(16)); writeJson($saltFile, ['salt' => $salt]); }
    return hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', $salt);
}
function nextUploadAt($d, $days) {
    $limits = readJson($d . '/limits.php'); $last = $limits[ipKey($d)] ?? 0;
    $next = $last + $days * 86400;
    return $next > time() ? $next : 0;
}
function fontType($head) { // druh fontu podľa prvých bajtov súboru, nie podľa názvu
    $m = substr($head, 0, 4);
    if ($m === "\x00\x01\x00\x00" || $m === 'true') return ['ttf', 'font/ttf'];
    if ($m === 'OTTO') return ['otf', 'font/otf'];
    if ($m === 'wOFF') return ['woff', 'font/woff'];
    if ($m === 'wOF2') return ['woff2', 'font/woff2'];
    return null;
}

if (isset($_GET['font'])) { // poslanie jedného fontu
    $id = (string)$_GET['font'];
    if (!preg_match('/^[a-f0-9]{16}$/', $id)) { http_response_code(404); exit; }
    foreach (readJson($dataDir . '/fonts.json') as $f) {
        if ($f['id'] !== $id) continue;
        $path = $dataDir . '/fonts/' . $f['id'] . '.' . $f['ext'];
        if (!is_file($path)) break;
        $types = ['ttf' => 'font/ttf', 'otf' => 'font/otf', 'woff' => 'font/woff', 'woff2' => 'font/woff2'];
        header('Content-Type: ' . $types[$f['ext']]);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
    http_response_code(404); exit;
}

if (($_GET['fonts'] ?? '') === 'list') { // zoznam spoločných fontov
    $list = array_map(fn($f) => ['id' => $f['id'], 'name' => $f['name'], 'url' => '?font=' . $f['id']], readJson($dataDir . '/fonts.json'));
    $next = is_dir($dataDir) ? nextUploadAt($dataDir, $fontLimitDays) : 0;
    jsonOut(['fonts' => $list, 'nextUploadAt' => $next, 'limitDays' => $fontLimitDays, 'maxBytes' => $fontMaxBytes]);
}

if (($_GET['fonts'] ?? '') === 'upload') { // nahranie fontu
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(['error' => 'Použi POST.'], 405);
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'titulky') jsonOut(['error' => 'Neplatná požiadavka.'], 403); // len z tejto stránky
    dataInit($dataDir);
    $lock = fopen($dataDir . '/.lock', 'c'); flock($lock, LOCK_EX);
    $next = nextUploadAt($dataDir, $fontLimitDays);
    if ($next) jsonOut(['error' => 'limit', 'nextUploadAt' => $next], 429);
    $up = $_FILES['font'] ?? null;
    if (!$up || ($up['error'] ?? 1) !== UPLOAD_ERR_OK) jsonOut(['error' => ($up && in_array($up['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) ? 'Font je príliš veľký.' : 'Súbor sa nepodarilo nahrať.'], 400);
    if ($up['size'] > $fontMaxBytes || $up['size'] < 1000) jsonOut(['error' => 'Font musí mať od 1 kB do ' . round($fontMaxBytes / 1048576) . ' MB.'], 400);
    $head = file_get_contents($up['tmp_name'], false, null, 0, 12);
    $type = fontType($head);
    $ext = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
    if (!$type || !in_array($ext, ['ttf', 'otf', 'woff', 'woff2'], true)) jsonOut(['error' => 'Toto nie je font. Prijímame len súbory TTF, OTF, WOFF a WOFF2.'], 400);
    $fonts = readJson($dataDir . '/fonts.json');
    if (count($fonts) >= $fontMaxCount) jsonOut(['error' => 'Knižnica fontov je plná.'], 507);
    $sha = sha1_file($up['tmp_name']);
    foreach ($fonts as $f) if (($f['sha1'] ?? '') === $sha) jsonOut(['ok' => true, 'duplicate' => true, 'font' => ['id' => $f['id'], 'name' => $f['name'], 'url' => '?font=' . $f['id']]]);
    $name = preg_replace('/[^\p{L}\p{N} _\-]/u', ' ', pathinfo($up['name'], PATHINFO_FILENAME));
    $name = trim(preg_replace('/\s+/', ' ', $name));
    $name = mb_substr($name !== '' ? $name : 'Font', 0, 40);
    $taken = array_map(fn($f) => mb_strtolower($f['name']), $fonts);
    $base = $name; $k = 2;
    while (in_array(mb_strtolower($name), $taken, true) || in_array(mb_strtolower($name), $reservedNames, true)) $name = $base . ' ' . $k++;
    $id = substr(bin2hex(random_bytes(8)), 0, 16);
    if (!move_uploaded_file($up['tmp_name'], $dataDir . '/fonts/' . $id . '.' . $type[0])) jsonOut(['error' => 'Font sa nepodarilo uložiť na server.'], 500);
    $fonts[] = ['id' => $id, 'name' => $name, 'ext' => $type[0], 'size' => $up['size'], 'sha1' => $sha, 'added' => time()];
    writeJson($dataDir . '/fonts.json', $fonts);
    $limits = array_filter(readJson($dataDir . '/limits.php'), fn($t) => $t > time() - $fontLimitDays * 86400);
    $limits[ipKey($dataDir)] = time();
    writeJson($dataDir . '/limits.php', $limits);
    jsonOut(['ok' => true, 'font' => ['id' => $id, 'name' => $name, 'url' => '?font=' . $id], 'nextUploadAt' => time() + $fontLimitDays * 86400]);
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
