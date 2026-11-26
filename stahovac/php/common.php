<?php
/**
 * Sťahovač videí – spoločné funkcie pre api.php a setup.php (PHP hosting cez FTP).
 */
declare(strict_types=1);

const DATA_DIR = __DIR__ . '/data';
const BIN_DIR = __DIR__ . '/bin';
const JOB_TTL = 1800;           // s – po tomto čase sa stiahnuté súbory zmažú
const MAX_ACTIVE = 3;           // súbežné sťahovania
const MAX_FILESIZE_MB = 1024;   // max. veľkosť videa
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36';

const PLATFORMS = [
    'youtube' => ['youtube.com', 'youtu.be', 'youtube-nocookie.com'],
    'tiktok' => ['tiktok.com'],
    'instagram' => ['instagram.com', 'instagr.am'],
    'facebook' => ['facebook.com', 'fb.watch', 'fb.com'],
];

if (is_file(__DIR__ . '/config.php')) {
    require __DIR__ . '/config.php';
}

class UserError extends Exception
{
    public int $status;

    public function __construct(string $message, int $status = 400)
    {
        parent::__construct($message);
        $this->status = $status;
    }
}

function platform_of(string $url): string
{
    $parts = parse_url(trim($url));
    if (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
        throw new UserError('Vložte celý odkaz začínajúci na https://');
    }
    $host = strtolower($parts['host']);
    foreach (PLATFORMS as $name => $domains) {
        foreach ($domains as $d) {
            if ($host === $d || str_ends_with($host, '.' . $d)) {
                return $name;
            }
        }
    }
    throw new UserError('Podporované sú odkazy z TikToku, Instagramu, YouTube a Facebooku.');
}

function ensure_dirs(): void
{
    foreach ([DATA_DIR, DATA_DIR . '/jobs', DATA_DIR . '/thumbs', DATA_DIR . '/tmp', BIN_DIR] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    // zákaz priameho prístupu cez web (Apache); pri nginx chránia súbory náhodné názvy
    foreach ([DATA_DIR, BIN_DIR] as $dir) {
        if (is_dir($dir) && !is_file($dir . '/.htaccess')) {
            @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
        }
    }
}

// ------------------------------------------------------------------ spúšťanie programov

function disabled_functions(): array
{
    return array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
}

/** Dovoľuje hosting spúšťať programy (yt-dlp, ffmpeg)? */
function can_run(): bool
{
    $off = disabled_functions();
    foreach (['proc_open', 'proc_close', 'proc_get_status'] as $fn) {
        if (!function_exists($fn) || in_array($fn, $off, true)) {
            return false;
        }
    }
    return true;
}

/**
 * Spustí príkaz (pole argumentov) a vráti [kód, stdout, stderr].
 * Kód -1 = časový limit.
 */
function run(array $cmd, int $timeout = 60, ?string $cwd = null): array
{
    if (!can_run()) {
        return [127, '', 'proc_open is disabled'];
    }
    // prostredie servera (vrátane prípadnej proxy) + vlastný dočasný priečinok
    $env = array_merge(getenv() ?: [], ['PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
        'HOME' => DATA_DIR . '/tmp', 'TMPDIR' => DATA_DIR . '/tmp', 'LANG' => 'C.UTF-8', 'LC_ALL' => 'C.UTF-8']);
    $proc = @proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd, $env);
    if (!is_resource($proc)) {
        return [127, '', 'proc_open failed'];
    }
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $out = $err = '';
    $start = microtime(true);
    $code = null;
    while (true) {
        $read = [$pipes[1], $pipes[2]];
        $w = $e = null;
        if (@stream_select($read, $w, $e, 0, 200000) > 0) {
            foreach ($read as $r) {
                $chunk = fread($r, 65536);
                if ($r === $pipes[1]) {
                    $out .= $chunk;
                } else {
                    $err .= $chunk;
                }
            }
        }
        $st = proc_get_status($proc);
        if (!$st['running']) {
            $code = $st['exitcode'];
            $out .= stream_get_contents($pipes[1]);
            $err .= stream_get_contents($pipes[2]);
            break;
        }
        if (microtime(true) - $start > $timeout) {
            proc_terminate($proc, 9);
            $code = -1;
            break;
        }
    }
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
    return [$code ?? -1, $out, $err];
}

/** Príkaz na spustenie yt-dlp (pole), alebo null, ak nie je nainštalovaný. */
function ytdlp_cmd(): ?array
{
    $cfg = BIN_DIR . '/engine.json';
    if (!can_run() || !is_file($cfg)) {
        return null;
    }
    $cmd = json_decode((string) file_get_contents($cfg), true)['cmd'] ?? null;
    return is_array($cmd) && $cmd ? $cmd : null;
}

function ffmpeg_path(): ?string
{
    static $cached = false;
    if ($cached !== false) {
        return $cached;
    }
    $cached = null;
    if (!can_run()) {
        return null;
    }
    $candidates = [BIN_DIR . '/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'];
    foreach ($candidates as $c) {
        if (@is_file($c) && @is_executable($c)) {
            $cached = $c;
            return $c;
        }
    }
    return null;
}

// ------------------------------------------------------------------ HTTP

/** GET cez cURL. Vráti [kód, telo, efektívna URL]. */
function http_get(string $url, array $headers = [], ?string $cookieJar = null, int $timeout = 25): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 8,
        CURLOPT_USERAGENT => UA,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => array_merge(['Accept-Language: sk,en;q=0.8'], $headers),
    ];
    if ($cookieJar) {
        $opts[CURLOPT_COOKIEJAR] = $cookieJar;
        $opts[CURLOPT_COOKIEFILE] = $cookieJar;
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $eff = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return [$code, $body === false ? '' : $body, $eff];
}

/** Stiahne URL do súboru. $onProgress(downloaded, total) sa volá priebežne. */
function http_download(string $url, string $dest, array $headers = [], ?string $cookieJar = null, ?callable $onProgress = null): bool
{
    $fh = fopen($dest, 'wb');
    if (!$fh) {
        return false;
    }
    $ch = curl_init($url);
    $opts = [
        CURLOPT_FILE => $fh,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => UA,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FAILONERROR => true,
        CURLOPT_NOPROGRESS => $onProgress === null,
    ];
    if ($cookieJar) {
        $opts[CURLOPT_COOKIEFILE] = $cookieJar;
    }
    if ($onProgress) {
        $opts[CURLOPT_PROGRESSFUNCTION] = function ($ch, $dlTotal, $dlNow) use ($onProgress) {
            return $onProgress((int) $dlNow, (int) $dlTotal) ? 1 : 0;   // 1 = zrušiť
        };
    }
    curl_setopt_array($ch, $opts);
    $ok = curl_exec($ch) !== false;
    curl_close($ch);
    fclose($fh);
    if (!$ok) {
        @unlink($dest);
    }
    return $ok;
}

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        $p = "$dir/$f";
        is_dir($p) && !is_link($p) ? rrmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}
