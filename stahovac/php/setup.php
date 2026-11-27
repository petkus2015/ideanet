<?php
/**
 * Sťahovač videí – kontrola hostingu a inštalácia yt-dlp a ffmpeg.
 * Otvorte https://vasa-domena.sk/stahovac/setup.php po nahratí súborov cez FTP.
 */
declare(strict_types=1);
require __DIR__ . '/common.php';
ensure_dirs();
@set_time_limit(300);

$messages = [];

function arch(): string
{
    $m = strtolower(php_uname('m'));
    return str_contains($m, 'aarch64') || str_contains($m, 'arm64') ? 'aarch64' : 'x86_64';
}

function try_ytdlp(array $cmd): ?string
{
    [$code, $out] = run(array_merge($cmd, ['--version']), 120);
    return $code === 0 ? trim($out) : null;
}

function install_ytdlp(array &$messages): void
{
    if (!can_run()) {
        $messages[] = ['err', 'Hosting nepovoľuje spúšťanie programov (proc_open), yt-dlp nepôjde. TikTok funguje aj bez neho.'];
        return;
    }
    if (PHP_OS_FAMILY !== 'Linux') {
        $messages[] = ['err', 'Automatická inštalácia je pripravená pre Linux hosting (' . PHP_OS_FAMILY . ' nie je podporovaný).'];
        return;
    }
    $base = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/';
    // 1) samostatný program (nepotrebuje Python)
    $bin = BIN_DIR . '/yt-dlp';
    $name = arch() === 'aarch64' ? 'yt-dlp_linux_aarch64' : 'yt-dlp_linux';
    if (http_download($base . $name, $bin . '.new') && filesize($bin . '.new') > 1000000) {
        rename($bin . '.new', $bin);
        chmod($bin, 0755);
        if ($v = try_ytdlp([$bin])) {
            file_put_contents(BIN_DIR . '/engine.json', json_encode(['cmd' => [$bin], 'version' => $v]));
            $messages[] = ['ok', "yt-dlp $v nainštalovaný."];
            return;
        }
    }
    // 2) verzia pre Python (ak ho hosting má)
    foreach (['python3', '/usr/bin/python3', '/usr/local/bin/python3'] as $py) {
        [$c, $o] = run([$py, '-c', 'import sys;print(sys.version_info >= (3, 9))'], 20);
        if ($c === 0 && trim($o) === 'True') {
            $zip = BIN_DIR . '/yt-dlp.pyz';
            if (http_download($base . 'yt-dlp', $zip) && ($v = try_ytdlp([$py, $zip]))) {
                file_put_contents(BIN_DIR . '/engine.json', json_encode(['cmd' => [$py, $zip], 'version' => $v]));
                $messages[] = ['ok', "yt-dlp $v nainštalovaný (cez Python)."];
                return;
            }
        }
    }
    $messages[] = ['err', 'yt-dlp sa nepodarilo spustiť. Hosting pravdepodobne blokuje spúšťanie stiahnutých programov. TikTok pôjde aj tak; pre ostatné platformy treba VPS alebo hosting s Pythonom.'];
}

function install_ffmpeg(array &$messages): void
{
    if (!can_run()) {
        $messages[] = ['err', 'Hosting nepovoľuje spúšťanie programov, ffmpeg nepôjde.'];
        return;
    }
    if (!class_exists('ZipArchive')) {
        $messages[] = ['err', 'Chýba PHP rozšírenie zip – ffmpeg nahrajte ručne do priečinka bin/ (súbor ffmpeg).'];
        return;
    }
    // statický ffmpeg z balíčka imageio-ffmpeg (PyPI)
    [$code, $json] = http_get('https://pypi.org/pypi/imageio-ffmpeg/json');
    $data = json_decode($json, true);
    $arch = arch();
    $wheel = null;
    foreach ($data['urls'] ?? [] as $f) {
        if (str_ends_with($f['filename'], '.whl') && str_contains($f['filename'], 'manylinux') && str_contains($f['filename'], $arch)) {
            $wheel = $f['url'];
        }
    }
    if (!$wheel) {
        $messages[] = ['err', 'Nenašiel sa ffmpeg pre tento server (' . $arch . ').'];
        return;
    }
    $tmp = DATA_DIR . '/tmp/ffmpeg.whl';
    if (!http_download($wheel, $tmp)) {
        $messages[] = ['err', 'ffmpeg sa nepodarilo stiahnuť.'];
        return;
    }
    $zip = new ZipArchive();
    $done = false;
    if ($zip->open($tmp) === true) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $n = $zip->getNameIndex($i);
            if (preg_match('#imageio_ffmpeg/binaries/ffmpeg-linux#', $n)) {
                file_put_contents(BIN_DIR . '/ffmpeg', $zip->getFromIndex($i));
                chmod(BIN_DIR . '/ffmpeg', 0755);
                $done = true;
            }
        }
        $zip->close();
    }
    @unlink($tmp);
    [$c, $o] = $done ? run([BIN_DIR . '/ffmpeg', '-version'], 30) : [1, ''];
    $messages[] = $c === 0 ? ['ok', 'ffmpeg nainštalovaný: ' . strtok($o, "\n")] : ['err', 'ffmpeg sa nepodarilo spustiť na tomto hostingu.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'ytdlp' || $action === 'all') {
        install_ytdlp($messages);
    }
    if ($action === 'ffmpeg' || $action === 'all') {
        install_ffmpeg($messages);
    }
}

// ------------------------------------------------------------------ kontroly
$checks = [];
$checks[] = ['PHP 8.0 alebo novší', version_compare(PHP_VERSION, '8.0', '>='), 'Verzia ' . PHP_VERSION, true];
$checks[] = ['Rozšírenie cURL', function_exists('curl_init'), function_exists('curl_init') ? 'Áno' : 'Zapnite ho v nastaveniach hostingu', true];
$writable = is_writable(DATA_DIR) && is_writable(BIN_DIR);
$checks[] = ['Zápis do priečinkov data/ a bin/', $writable, $writable ? 'Áno' : 'Nastavte práva 755 (cez FTP klienta)', true];
$checks[] = ['Spúšťanie programov (proc_open)', can_run(), can_run() ? 'Povolené' : 'Zakázané – pôjde TikTok a Facebook', false];
$ytdlp = ytdlp_cmd();
$ytv = $ytdlp ? (json_decode((string) file_get_contents(BIN_DIR . '/engine.json'), true)['version'] ?? '?') : null;
$checks[] = ['yt-dlp (YouTube, Instagram, Facebook)', (bool) $ytdlp, $ytdlp ? "Verzia $ytv" : 'Nenainštalovaný', false];
$ff = ffmpeg_path();
$checks[] = ['ffmpeg (spájanie obrazu a zvuku)', (bool) $ff, $ff ? 'Nájdený' : 'Nenainštalovaný – YouTube len v nižšej kvalite', false];
$net = function_exists('curl_init') ? (http_get('https://www.tiktok.com/robots.txt', [], null, 10)[0] ?? 0) : 0;
$checks[] = ['Prístup na internet zo servera', $net > 0 && $net < 500, $net ? "HTTP $net" : 'Server sa nedostane von', true];
$ready = $checks[0][1] && $checks[1][1] && $checks[2][1];
?>
<!doctype html>
<html lang="sk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Sťahovač – nastavenie</title>
<style>
  :root { --bg:#0b0d1a; --card:#141729; --line:#262b47; --text:#f2f3fa; --muted:#9097b8; --ok:#2ee59d; --err:#ff5d6c; --warn:#ffc24b; --acc:#ff2d87; --acc2:#7b5cff; color-scheme: dark; }
  body { margin:0; background:var(--bg); color:var(--text); font:15px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; }
  main { max-width:620px; margin:0 auto; padding:28px max(16px, env(safe-area-inset-left)) 48px; display:flex; flex-direction:column; gap:16px; }
  h1 { margin:0; font-size:24px; } p { margin:0; color:var(--muted); }
  .card { background:var(--card); border:1px solid var(--line); border-radius:18px; padding:16px; display:flex; flex-direction:column; gap:10px; }
  .row { display:flex; gap:12px; align-items:flex-start; }
  .dot { width:22px; height:22px; border-radius:50%; flex:none; display:grid; place-items:center; font-size:13px; font-weight:800; color:#0b0d1a; margin-top:1px; }
  .ok .dot { background:var(--ok); } .bad .dot { background:var(--err); } .opt .dot { background:var(--warn); }
  .row b { display:block; font-weight:600; } .row span { color:var(--muted); font-size:13px; }
  .msg { border-radius:12px; padding:10px 12px; font-size:14px; }
  .msg.ok { background:rgba(46,229,157,.12); color:#b9f7dc; } .msg.err { background:rgba(255,93,108,.12); color:#ffc5cb; }
  form { display:flex; flex-wrap:wrap; gap:8px; }
  button, a.btn { font:inherit; font-weight:700; border:0; border-radius:12px; padding:12px 16px; cursor:pointer; text-decoration:none; color:#fff; background:linear-gradient(135deg,var(--acc),var(--acc2)); }
  button.ghost { background:#0f1224; border:1px solid var(--line); }
  code { background:#0f1224; padding:1px 6px; border-radius:6px; }
</style>
</head>
<body>
<main>
  <h1>Sťahovač – nastavenie servera</h1>
  <p>Táto stránka skontroluje hosting a jedným klikom doinštaluje nástroje na sťahovanie.</p>

  <?php foreach ($messages as [$type, $text]): ?>
    <div class="msg <?= $type ?>"><?= htmlspecialchars($text) ?></div>
  <?php endforeach; ?>

  <section class="card">
    <?php foreach ($checks as [$label, $ok, $detail, $required]): ?>
      <div class="row <?= $ok ? 'ok' : ($required ? 'bad' : 'opt') ?>">
        <span class="dot"><?= $ok ? '✓' : ($required ? '✕' : '!') ?></span>
        <div><b><?= htmlspecialchars($label) ?></b><span><?= htmlspecialchars($detail) ?></span></div>
      </div>
    <?php endforeach; ?>
  </section>

  <?php if ($ready): ?>
  <section class="card">
    <b>Inštalácia nástrojov</b>
    <p>Stiahne <code>yt-dlp</code> (z GitHubu) a <code>ffmpeg</code> (z PyPI) do priečinka <code>bin/</code>. Trvá to asi minútu.</p>
    <form method="post">
      <button name="action" value="all"><?= $ytdlp ? 'Aktualizovať všetko' : 'Nainštalovať všetko' ?></button>
      <button class="ghost" name="action" value="ytdlp">Len yt-dlp</button>
      <button class="ghost" name="action" value="ffmpeg">Len ffmpeg</button>
    </form>
  </section>
  <?php endif; ?>

  <section class="card">
    <b>Čo bude fungovať</b>
    <p><?php
      if ($ytdlp && $ff) echo 'Všetko: TikTok, Instagram, YouTube a Facebook v pôvodnej kvalite.';
      elseif ($ytdlp) echo 'TikTok, Instagram, Facebook. YouTube len do 360p (na vyššie treba ffmpeg).';
      elseif ($ready) echo 'TikTok a Facebook (čisté PHP). Pre Instagram a YouTube nainštalujte yt-dlp – ak to hosting nedovolí, treba VPS.';
      else echo 'Najprv opravte červené položky.';
    ?></p>
    <a class="btn" href="./">Otvoriť aplikáciu</a>
  </section>
</main>
</body>
</html>
