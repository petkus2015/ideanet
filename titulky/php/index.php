<?php
// Subgen: hostovanie aplikácie na PHP webhostingu (nahranie cez FTP).
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
    $c = is_file(__DIR__ . '/config.php') ? (include __DIR__ . '/config.php') : [];
    echo "config.php: " . (is_array($c) ? 'ok' : 'CHÝBA') . ", Stripe kľúč: " . (!empty($c['stripe_secret_key']) ? 'nastavený' : 'chýba') . ", platobný odkaz: " . (!empty($c['payment_link']) ? 'nastavený' : 'chýba') . "\n";
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
    jsonOut(['error' => 'Nahrávanie písem na server je vypnuté. Vlastné písma ostávajú len v prehliadači používateľa.'], 410);
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

// ---------- účty: prihlásenie a ročné predplatné cez Stripe ----------
// Export videa je len pre prihlásených s platným predplatným. Registrácia = platba cez Stripe Payment Link
// (4,90 € ročne); po návrate zo Stripe server overí platbu a používateľ si nastaví heslo.
// Nastavenia sú v config.php (vzor: config-vzor.php). Bez nich prihlásenie nefunguje, appka beží ďalej.
$cfg = is_file(__DIR__ . '/config.php') ? (include __DIR__ . '/config.php') : [];
if (!is_array($cfg)) $cfg = [];
$cfg += ['stripe_secret_key' => '', 'stripe_webhook_secret' => '', 'payment_link' => '', 'price_label' => '4,90 € ročne',
         'mail_from' => '', 'stripe_api' => 'https://api.stripe.com', 'site_name' => 'Subgen', 'admin_emails' => []];

function authStart() {
    global $https;
    session_name('subgen');
    session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 60, 'path' => '/', 'secure' => (bool)$https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
function stripeGet($path) { // čítanie zo Stripe API tajným kľúčom zo servera
    global $cfg;
    if (!$cfg['stripe_secret_key']) return null;
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 15, 'ignore_errors' => true,
        'header' => "Authorization: Bearer " . $cfg['stripe_secret_key'] . "\r\n"]]);
    $r = @file_get_contents(rtrim($cfg['stripe_api'], '/') . $path, false, $ctx);
    $j = $r ? json_decode($r, true) : null;
    return is_array($j) && !isset($j['error']) ? $j : null;
}
function userKey($email) { return mb_strtolower(trim($email)); }
function isAdmin($u) { global $cfg; return $u && in_array(mb_strtolower($u['email']), array_map('mb_strtolower', (array)$cfg['admin_emails']), true); }
function userPublic($u) { return $u ? ['email' => $u['email'], 'until' => (int)$u['until'], 'active' => (int)$u['until'] > time(), 'admin' => isAdmin($u)] : null; }
// ---------- analytika (len pre správcu) ----------
// Počíta návštevy stránky a registrácie po dňoch, bez cookies a bez externých služieb. Unikátny návštevník je
// odtlačok IP + prehliadača s denne meneným tajným kľúčom; ukladá sa len počas dňa, potom sa zahodí (súkromie).
function statBump($dataDir, $field) {
    if (!@is_dir($dataDir) && !@mkdir($dataDir . '/fonts', 0755, true)) return;
    $lock = @fopen($dataDir . '/.lock', 'c'); if (!$lock) return; flock($lock, LOCK_EX);
    $f = $dataDir . '/stats.php'; $st = readJson($f); $d = gmdate('Y-m-d');
    if (!isset($st['days'][$d])) $st['days'][$d] = ['v' => 0, 'u' => 0, 'r' => 0];
    if ($field === 'v') {
        if (($st['today']['d'] ?? '') !== $d) $st['today'] = ['d' => $d, 'h' => [], 'salt' => bin2hex(random_bytes(8))];
        $h = substr(hash('sha256', $st['today']['salt'] . '|' . ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 16);
        $st['days'][$d]['v']++;
        if (!isset($st['today']['h'][$h])) { $st['today']['h'][$h] = 1; $st['days'][$d]['u']++; }
    } else $st['days'][$d][$field] = ($st['days'][$d][$field] ?? 0) + 1;
    writeJson($f, $st); flock($lock, LOCK_UN);
}
function untilFrom($sess) { // platnosť: koniec obdobia predplatného, pri jednorazovej platbe rok od zaplatenia
    $sub = $sess['subscription'] ?? null;
    if (is_array($sub) && !empty($sub['current_period_end'])) return (int)$sub['current_period_end'];
    if (is_array($sub) && !empty($sub['items']['data'][0]['current_period_end'])) return (int)$sub['items']['data'][0]['current_period_end'];
    return time() + 365 * 86400;
}
// Jeden účet môže byť prihlásený najviac na 3 zariadeniach naraz. Každé prihlásenie je zariadenie s náhodným ID
// v relácii; zariadenie, ktoré sa 30 dní neozvalo, sa uvoľní samo.
const MAX_DEVICES = 3;
function devPrune($u) { return array_filter($u['devices'] ?? [], fn($d) => ($d['seen'] ?? 0) > time() - 30 * 86400); }
function deviceLogin(&$users, $k, $mode) { // $mode: 'ask' = pri plnom počte odmietne, 'oldest' = uvoľní najstaršie, 'all' = odhlási ostatné
    $devs = devPrune($users[$k]);
    if ($mode === 'all') $devs = [];
    if (count($devs) >= MAX_DEVICES) {
        if ($mode === 'ask') return false;
        uasort($devs, fn($a, $b) => $a['seen'] <=> $b['seen']);
        while (count($devs) >= MAX_DEVICES) array_shift($devs);
    }
    $id = bin2hex(random_bytes(12));
    $devs[$id] = ['seen' => time(), 'ua' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 120)];
    $users[$k]['devices'] = $devs;
    session_regenerate_id(true); $_SESSION['email'] = $users[$k]['email']; $_SESSION['dev'] = $id;
    return true;
}
function needPost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(['error' => 'Použi POST.'], 405);
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'titulky') jsonOut(['error' => 'Neplatná požiadavka.'], 403);
    $b = json_decode(file_get_contents('php://input'), true);
    return is_array($b) ? $b : [];
}
function tooMany($d, $what, $max, $sec) { // jednoduchá ochrana proti skúšaniu hesiel
    $f = $d . '/attempts.php'; $a = readJson($f); $k = $what . ':' . ipKey($d);
    $list = array_values(array_filter($a[$k] ?? [], fn($t) => $t > time() - $sec));
    if (count($list) >= $max) return true;
    $list[] = time(); $a[$k] = $list;
    foreach ($a as $kk => $v) if (!array_filter($v, fn($t) => $t > time() - 86400)) unset($a[$kk]);
    writeJson($f, $a); return false;
}

if (isset($_GET['auth'])) {
    $act = (string)$_GET['auth'];
    $ready = $cfg['stripe_secret_key'] !== '' && $cfg['payment_link'] !== '';

    if ($act === 'webhook') { // voliteľné: Stripe oznámi obnovenie alebo zrušenie predplatného
        $payload = file_get_contents('php://input'); $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        if (!$cfg['stripe_webhook_secret'] || !preg_match('/t=(\d+)/', $sig, $tm) || !preg_match_all('/v1=([a-f0-9]+)/', $sig, $vs)) jsonOut(['error' => 'bad signature'], 400);
        $exp = hash_hmac('sha256', $tm[1] . '.' . $payload, $cfg['stripe_webhook_secret']);
        if (abs(time() - (int)$tm[1]) > 600 || !in_array($exp, $vs[1], true)) jsonOut(['error' => 'bad signature'], 400);
        $ev = json_decode($payload, true); $o = $ev['data']['object'] ?? [];
        dataInit($dataDir); $lock = fopen($dataDir . '/.lock', 'c'); flock($lock, LOCK_EX);
        $users = readJson($dataDir . '/users.php');
        if (($ev['type'] ?? '') === 'invoice.paid' && !empty($o['customer'])) {
            $end = (int)($o['lines']['data'][0]['period']['end'] ?? 0);
            foreach ($users as $k => $u) if (($u['customer'] ?? '') === $o['customer'] && $end > (int)$u['until']) $users[$k]['until'] = $end;
        }
        if (($ev['type'] ?? '') === 'customer.subscription.deleted' && !empty($o['customer'])) {
            $end = (int)($o['current_period_end'] ?? $o['ended_at'] ?? time());
            foreach ($users as $k => $u) if (($u['customer'] ?? '') === $o['customer']) $users[$k]['until'] = min((int)$u['until'], $end);
        }
        writeJson($dataDir . '/users.php', $users);
        jsonOut(['ok' => true]);
    }

    authStart();
    if ($act === 'me') {
        $u = null;
        if (!empty($_SESSION['email'])) {
            $users = readJson($dataDir . '/users.php'); $k = userKey($_SESSION['email']); $u = $users[$k] ?? null; $dev = (string)($_SESSION['dev'] ?? '');
            if (!$u || !isset($u['devices'][$dev])) { $u = null; $_SESSION = []; } // odhlásené z iného zariadenia
            elseif (($u['devices'][$dev]['seen'] ?? 0) < time() - 600) { // posledná aktivita, najviac raz za 10 minút
                dataInit($dataDir); $lock = fopen($dataDir . '/.lock', 'c'); flock($lock, LOCK_EX);
                $users = readJson($dataDir . '/users.php');
                if (isset($users[$k]['devices'][$dev])) { $users[$k]['devices'][$dev]['seen'] = time(); writeJson($dataDir . '/users.php', $users); }
            }
        }
        jsonOut(['user' => userPublic($u), 'ready' => $ready, 'payLink' => $ready ? $cfg['payment_link'] : '', 'price' => $cfg['price_label']]);
    }
    if ($act === 'logout') {
        needPost();
        if (!empty($_SESSION['email']) && !empty($_SESSION['dev'])) { // uvoľní miesto pre ďalšie zariadenie
            dataInit($dataDir); $lock = fopen($dataDir . '/.lock', 'c'); flock($lock, LOCK_EX);
            $users = readJson($dataDir . '/users.php'); $k = userKey($_SESSION['email']);
            if (isset($users[$k]['devices'][$_SESSION['dev']])) { unset($users[$k]['devices'][$_SESSION['dev']]); writeJson($dataDir . '/users.php', $users); }
        }
        $_SESSION = []; session_destroy(); jsonOut(['ok' => true]);
    }

    if ($act === 'stats') { // analytika: len prihlásený správca (admin_emails v config.php)
        $users = readJson($dataDir . '/users.php'); $u = !empty($_SESSION['email']) ? ($users[userKey($_SESSION['email'])] ?? null) : null;
        if (!$u || !isset($u['devices'][(string)($_SESSION['dev'] ?? '')]) || !isAdmin($u)) jsonOut(['error' => 'Len pre správcu.'], 403);
        $st = readJson($dataDir . '/stats.php'); $days = $st['days'] ?? []; ksort($days);
        $paid = 0; foreach ($users as $x) if ((int)($x['until'] ?? 0) > time()) $paid++;
        jsonOut(['days' => $days, 'users' => count($users), 'active' => $paid]);
    }
    $b = needPost();
    dataInit($dataDir);
    $lock = fopen($dataDir . '/.lock', 'c'); flock($lock, LOCK_EX);
    $users = readJson($dataDir . '/users.php');

    if ($act === 'login') {
        if (tooMany($dataDir, 'login', 10, 900)) jsonOut(['error' => 'Príliš veľa pokusov. Skús to o 15 minút.'], 429);
        $u = $users[userKey($b['email'] ?? '')] ?? null;
        if (!$u || empty($u['hash']) || !password_verify((string)($b['password'] ?? ''), $u['hash'])) jsonOut(['error' => 'Nesprávny e-mail alebo heslo.'], 401);
        $k = userKey($u['email']);
        if (!deviceLogin($users, $k, !empty($b['force']) ? 'all' : 'ask'))
            jsonOut(['error' => 'Tento účet je už prihlásený na ' . MAX_DEVICES . ' zariadeniach. Najviac ' . MAX_DEVICES . ' zariadenia naraz.', 'code' => 'devices'], 409);
        writeJson($dataDir . '/users.php', $users);
        jsonOut(['ok' => true, 'user' => userPublic($users[$k])]);
    }
    if ($act === 'claim') { // návrat zo Stripe: overenie platby podľa ID platobnej relácie
        if (!$ready) jsonOut(['error' => 'Platby ešte nie sú na serveri nastavené.'], 503);
        $sid = (string)($b['session_id'] ?? '');
        if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sid)) jsonOut(['error' => 'Neplatná platba.'], 400);
        $claims = readJson($dataDir . '/claims.php');
        if (!isset($claims[$sid])) {
            $sess = stripeGet('/v1/checkout/sessions/' . rawurlencode($sid) . '?expand[]=subscription');
            if (!$sess) jsonOut(['error' => 'Platbu sa nepodarilo overiť v Stripe. Skús obnoviť stránku.'], 502);
            if (($sess['status'] ?? '') !== 'complete' || !in_array($sess['payment_status'] ?? '', ['paid', 'no_payment_required'], true)) jsonOut(['error' => 'Platba ešte nie je dokončená.'], 402);
            $email = $sess['customer_details']['email'] ?? $sess['customer_email'] ?? '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonOut(['error' => 'V platbe chýba e-mail.'], 400);
            $claims[$sid] = ['email' => $email, 'until' => untilFrom($sess), 'customer' => is_string($sess['customer'] ?? null) ? $sess['customer'] : '', 'used' => false, 't' => time()];
            writeJson($dataDir . '/claims.php', $claims);
        }
        $c = $claims[$sid]; $k = userKey($c['email']);
        if (isset($users[$k]) && !empty($users[$k]['hash'])) { // existujúci účet: predĺženie a prihlásenie
            if (!$c['used']) { $users[$k]['until'] = max((int)$users[$k]['until'], (int)$c['until']); if ($c['customer']) $users[$k]['customer'] = $c['customer']; $claims[$sid]['used'] = true; writeJson($dataDir . '/claims.php', $claims); writeJson($dataDir . '/users.php', $users); }
            deviceLogin($users, $k, 'oldest'); writeJson($dataDir . '/users.php', $users);
            jsonOut(['ok' => true, 'user' => userPublic($users[$k]), 'needsPassword' => false]);
        }
        jsonOut(['ok' => true, 'email' => $c['email'], 'needsPassword' => true]);
    }
    if ($act === 'setpw') { // nový účet po platbe: nastavenie hesla
        $sid = (string)($b['session_id'] ?? ''); $pw = (string)($b['password'] ?? '');
        $claims = readJson($dataDir . '/claims.php'); $c = $claims[$sid] ?? null;
        if (!$c || $c['used']) jsonOut(['error' => 'Tento odkaz už bol použitý. Prihlás sa.'], 400);
        if (mb_strlen($pw) < 8) jsonOut(['error' => 'Heslo musí mať aspoň 8 znakov.'], 400);
        $k = userKey($c['email']);
        $users[$k] = ['email' => $c['email'], 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'until' => max((int)($users[$k]['until'] ?? 0), (int)$c['until']), 'customer' => $c['customer'], 'created' => time()];
        $claims[$sid]['used'] = true;
        deviceLogin($users, $k, 'all');
        writeJson($dataDir . '/users.php', $users); writeJson($dataDir . '/claims.php', $claims);
        flock($lock, LOCK_UN); statBump($dataDir, 'r'); // nová registrácia do analytiky
        jsonOut(['ok' => true, 'user' => userPublic($users[$k])]);
    }
    if ($act === 'forgot') { // obnova hesla e-mailom (vždy rovnaká odpoveď, aby sa nedalo zisťovať, kto má účet)
        if (tooMany($dataDir, 'forgot', 5, 3600)) jsonOut(['error' => 'Príliš veľa pokusov. Skús to neskôr.'], 429);
        $u = $users[userKey($b['email'] ?? '')] ?? null;
        if ($u) {
            $tok = bin2hex(random_bytes(20)); $resets = readJson($dataDir . '/resets.php');
            foreach ($resets as $h => $r) if ($r['exp'] < time()) unset($resets[$h]);
            $resets[hash('sha256', $tok)] = ['email' => $u['email'], 'exp' => time() + 3600]; writeJson($dataDir . '/resets.php', $resets);
            $link = ($https ? 'https' : 'http') . '://' . $host . strtok($_SERVER['REQUEST_URI'] ?? '/', '?') . '?reset=' . $tok;
            $from = $cfg['mail_from'] ?: ('no-reply@' . preg_replace('/:\d+$/', '', $host));
            @mail($u['email'], '=?UTF-8?B?' . base64_encode($cfg['site_name'] . ': nové heslo') . '?=', "Nové heslo si nastavíš tu (platí 1 hodinu):\n\n" . $link . "\n\nAk si o zmenu nežiadal(a), správu ignoruj.\n",
                "From: " . $from . "\r\nContent-Type: text/plain; charset=utf-8");
        }
        jsonOut(['ok' => true]);
    }
    if ($act === 'reset') {
        $tok = (string)($b['token'] ?? ''); $pw = (string)($b['password'] ?? '');
        $resets = readJson($dataDir . '/resets.php'); $h = hash('sha256', $tok); $r = $resets[$h] ?? null;
        if (!$r || $r['exp'] < time()) jsonOut(['error' => 'Odkaz na zmenu hesla je neplatný alebo vypršal.'], 400);
        if (mb_strlen($pw) < 8) jsonOut(['error' => 'Heslo musí mať aspoň 8 znakov.'], 400);
        $k = userKey($r['email']); if (!isset($users[$k])) jsonOut(['error' => 'Účet neexistuje.'], 400);
        $users[$k]['hash'] = password_hash($pw, PASSWORD_DEFAULT); unset($resets[$h]);
        deviceLogin($users, $k, 'all'); // nové heslo odhlási všetky ostatné zariadenia
        writeJson($dataDir . '/users.php', $users); writeJson($dataDir . '/resets.php', $resets);
        jsonOut(['ok' => true, 'user' => userPublic($users[$k])]);
    }
    jsonOut(['error' => 'Neznáma akcia.'], 404);
}

if (!is_file($file)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Chýba súbor app.html. Nahraj ho do rovnakého priečinka ako index.php.';
    exit;
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !preg_match('/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless/i', $ua)
    && ($_SERVER['HTTP_PURPOSE'] ?? '') !== 'prefetch' && ($_SERVER['HTTP_SEC_PURPOSE'] ?? '') !== 'prefetch') {
    try { statBump($dataDir, 'v'); } catch (Throwable $e) { /* analytika nesmie zablokovať stránku */ }
}
header('Content-Type: text/html; charset=utf-8');
// Izolácia stránky: prehliadač potom dovolí rozpoznávanie reči na viacerých jadrách procesora (rýchlejšie).
// "credentialless" nechá fungovať písma Google aj knižnicu z CDN; Safari hlavičku ignoruje a beží na jednom jadre.
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Embedder-Policy: credentialless');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache');
echo file_get_contents($file);
