<?php
/**
 * Sťahovač videí – API pre PHP hosting.
 *
 * Dva spôsoby získania videa:
 *  1. yt-dlp (nainštaluje setup.php) – TikTok, Instagram, YouTube, Facebook.
 *     Vyžaduje, aby hosting povolil spúšťanie programov (proc_open).
 *  2. Čisté PHP – len TikTok; funguje aj na hostingu, ktorý programy zakazuje.
 *
 * Volania: api.php?r=info | download | jobs/<id> | jobs/<id>/file | jobs/<id>/cancel | thumb/<token>
 */
declare(strict_types=1);
require __DIR__ . '/common.php';

ensure_dirs();
@set_time_limit(120);

$route = trim((string) ($_GET['r'] ?? ''), '/');

try {
    cleanup();
    if ($route === 'info') {
        respond(api_info(read_json()));
    } elseif ($route === 'download') {
        api_download(read_json());   // odpovedá sama (môže pokračovať na pozadí)
    } elseif (preg_match('#^jobs/([0-9a-f]{32})$#', $route, $m)) {
        respond(job_status($m[1]));
    } elseif (preg_match('#^jobs/([0-9a-f]{32})/file$#', $route, $m)) {
        send_file($m[1]);
    } elseif (preg_match('#^jobs/([0-9a-f]{32})/cancel$#', $route, $m)) {
        respond(cancel_job($m[1]));
    } elseif (preg_match('#^thumb/([0-9a-f]{32})$#', $route, $m)) {
        send_thumb($m[1]);
    } else {
        respond(['error' => 'Neznáma požiadavka.'], 404);
    }
} catch (UserError $e) {
    respond(['error' => $e->getMessage()], $e->status);
} catch (Throwable $e) {
    error_log('stahovac: ' . $e);
    respond(['error' => 'Na serveri nastala chyba. Skúste to znova.'], 500);
}

// =================================================================== pomocné

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function read_json(): array
{
    $data = json_decode((string) file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function job_dir(string $id): string
{
    return DATA_DIR . '/jobs/' . $id;
}

function load_meta(string $id): array
{
    $f = job_dir($id) . '/meta.json';
    if (!is_file($f)) {
        throw new UserError('Sťahovanie sa nenašlo.', 404);
    }
    return json_decode((string) file_get_contents($f), true) ?: [];
}

function save_json(string $file, array $data): void
{
    file_put_contents($file . '.tmp', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    rename($file . '.tmp', $file);
}

function friendly_error(string $text): string
{
    $low = strtolower($text);
    if (preg_match('/private|login|log in|cookies|sign in/', $low)) {
        return 'Video je súkromné alebo vyžaduje prihlásenie, nedá sa stiahnuť.';
    }
    if (str_contains($low, 'unsupported url')) {
        return 'Na tomto odkaze sa nenašlo video.';
    }
    if (preg_match('/not available|unavailable|removed|http error 404/', $low)) {
        return 'Video už nie je dostupné (bolo zmazané alebo je obmedzené).';
    }
    if (preg_match('/no video formats|no formats|requested format is not available/', $low)) {
        return 'Za odkazom sa nenachádza video, alebo zvolená kvalita nie je dostupná.';
    }
    if (preg_match('/timed out|timeout/', $low)) {
        return 'Platforma neodpovedala včas. Skúste to znova.';
    }
    if (preg_match('/max-filesize|larger than max/', $low)) {
        return 'Video je príliš veľké.';
    }
    return 'Video sa nepodarilo načítať. Skontrolujte odkaz a skúste znova.';
}

function register_thumb(?string $url): ?string
{
    if (!$url || !preg_match('#^https?://#', $url)) {
        return null;
    }
    $token = bin2hex(random_bytes(16));
    file_put_contents(DATA_DIR . "/thumbs/$token.url", $url);
    return 'api.php?r=' . rawurlencode("thumb/$token");
}

function nice_filename(?string $title, ?string $id, string $ext): string
{
    $name = preg_replace('/#\S+/u', ' ', (string) $title);
    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($ascii !== false) {
            $name = $ascii;
        }
    }
    $name = preg_replace('/[^A-Za-z0-9 _-]+/', ' ', $name);
    $name = trim(preg_replace('/\s+/', ' ', $name), ' -_');
    $name = rtrim(substr($name, 0, 60), ' -_') ?: 'video';
    if ($id) {
        $name .= ' - ' . substr(preg_replace('/[^\w-]/', '', $id), 0, 24);
    }
    return $name . '.' . $ext;
}

function short_codec(?string $c): string
{
    if (!$c || $c === 'none') {
        return '';
    }
    $c = strtolower($c);
    $map = ['avc' => 'H.264', 'h264' => 'H.264', 'hev' => 'H.265', 'hvc' => 'H.265', 'h265' => 'H.265',
        'bytevc1' => 'H.265', 'vp09' => 'VP9', 'vp9' => 'VP9', 'av01' => 'AV1', 'vp8' => 'VP8'];
    foreach ($map as $prefix => $label) {
        if (str_starts_with($c, $prefix)) {
            return $label;
        }
    }
    return strtoupper(explode('.', $c)[0]);
}

// =================================================================== info

function api_info(array $in): array
{
    $url = trim((string) ($in['url'] ?? ''));
    if ($url === '') {
        throw new UserError('Vložte odkaz na video.');
    }
    $platform = platform_of($url);
    $ytdlp = ytdlp_cmd();

    if ($ytdlp) {
        [$code, $out, $err] = run(array_merge($ytdlp, ['-J', '--no-playlist', '--no-warnings', '--no-cache-dir',
            '--socket-timeout', '20', $url]), 90);
        $info = $code === 0 ? json_decode($out, true) : null;
        if (is_array($info)) {
            return info_from_ytdlp($info, $platform, $url);
        }
        if ($platform !== 'tiktok') {
            throw new UserError(friendly_error($err ?: $out), 422);
        }
        // TikTok: skúsime ešte čisté PHP
    }
    if ($platform === 'tiktok') {
        return info_from_tiktok($url);
    }
    $name = ['youtube' => 'YouTube', 'instagram' => 'Instagram', 'facebook' => 'Facebook'][$platform] ?? $platform;
    if (!can_run()) {
        throw new UserError("Tento webhosting nedovoľuje sťahovanie z platformy $name (zakazuje spúšťanie programov). Funguje TikTok.", 503);
    }
    throw new UserError("Pre $name treba na serveri nainštalovať yt-dlp. Otvorte setup.php.", 503);
}

function pick_video_entry(array $info): array
{
    if (!empty($info['entries'])) {
        foreach ($info['entries'] as $e) {
            if (is_array($e) && !empty($e['formats'])) {
                return $e;
            }
        }
        throw new UserError('Za odkazom sa nenachádza video (možno ide o fotku).');
    }
    return $info;
}

function info_from_ytdlp(array $info, string $platform, string $url): array
{
    $info = pick_video_entry($info);
    if (!empty($info['is_live'])) {
        throw new UserError('Živé vysielanie sa nedá stiahnuť.');
    }
    $hasFF = ffmpeg_path() !== null;
    $dur = $info['duration'] ?? null;
    $formats = $info['formats'] ?? [$info];

    $isVideo = fn($f) => ($f['vcodec'] ?? null) !== 'none' && !in_array($f['ext'] ?? '', ['mhtml', 'jpg', 'png', 'webp'], true)
        && (($f['vcodec'] ?? null) !== null || !empty($f['height']));
    $hasAudio = fn($f) => ($f['acodec'] ?? null) !== 'none' && !(($f['acodec'] ?? null) === null && !empty($f['height']));
    $size = function ($f) use ($dur) {
        $s = $f['filesize'] ?? $f['filesize_approx'] ?? null;
        if (!$s && !empty($f['tbr']) && $dur) {
            $s = $f['tbr'] * 1000 / 8 * $dur;
        }
        return $s ? (int) $s : null;
    };

    $videos = array_values(array_filter($formats, $isVideo));
    if (!$hasFF) {   // bez ffmpeg sa nedajú spojiť oddelené stopy – len videá so zvukom
        $videos = array_values(array_filter($videos, $hasAudio));
    }
    if (!$videos) {
        throw new UserError($hasFF ? 'Za odkazom sa nenachádza video (možno ide o fotku).'
            : 'Toto video má oddelený obraz a zvuk – na serveri treba nainštalovať ffmpeg (setup.php).');
    }
    $audios = array_values(array_filter($formats, fn($f) => !$isVideo($f) && $hasAudio($f)));
    usort($audios, fn($a, $b) => ($b['abr'] ?? $b['tbr'] ?? 0) <=> ($a['abr'] ?? $a['tbr'] ?? 0));
    $bestAudio = $audios[0] ?? null;

    $rank = fn($f) => [$f['height'] ?? 0, $f['width'] ?? 0, $f['fps'] ?? 0, $f['tbr'] ?? 0, $hasAudio($f) ? 1 : 0];
    usort($videos, fn($a, $b) => $rank($b) <=> $rank($a));

    $describe = function ($f) use ($hasAudio, $bestAudio, $size, $hasFF) {
        $muxed = $hasAudio($f);
        $s = $size($f);
        if ($s && !$muxed && $bestAudio && $size($bestAudio)) {
            $s += $size($bestAudio);
        }
        return [
            'width' => $f['width'] ?? null, 'height' => $f['height'] ?? null,
            'fps' => isset($f['fps']) ? (int) round($f['fps']) : null,
            'codec' => short_codec($f['vcodec'] ?? null), 'size' => $s,
            'spec' => ($muxed || !$bestAudio || !$hasFF) ? $f['format_id'] : $f['format_id'] . '+' . $bestAudio['format_id'],
        ];
    };

    $best = $videos[0];
    $orig = $describe($best);
    $orig['spec'] .= $hasFF ? '/bv+ba/b' : '/b';
    $options = [array_merge($orig, ['id' => 'original', 'label' => 'Originál', 'hint' => 'Najvyššia kvalita, bez prekódovania'])];

    $short = fn($f) => min($f['height'] ?? 0, $f['width'] ?? ($f['height'] ?? 0));
    $bestKey = $short($best);
    $seen = [];
    foreach ($videos as $f) {
        $k = $short($f);
        if (!$k || $k >= $bestKey || isset($seen[$k])) {
            continue;
        }
        $seen[$k] = true;
        $options[] = array_merge($describe($f), ['id' => "r$k", 'label' => "{$k}p", 'hint' => 'Menší súbor']);
        if (count($options) >= 6) {
            break;
        }
    }

    if ($orig['codec'] !== 'H.264' && $orig['codec'] !== '') {
        foreach ($videos as $f) {
            if (short_codec($f['vcodec'] ?? null) === 'H.264') {
                $o = $describe($f);
                $dup = false;
                foreach ($options as &$x) {
                    if ($x['spec'] === $o['spec']) {
                        $x['hint'] = 'Prehrá každý telefón';
                        $dup = true;
                    }
                }
                unset($x);
                if (!$dup) {
                    $options[] = array_merge($o, ['id' => 'compat', 'label' => 'MP4 (H.264)', 'hint' => 'Prehrá každý telefón']);
                }
                break;
            }
        }
    }

    $split = (bool) array_filter($videos, fn($f) => !$hasAudio($f));
    if ($split && $bestAudio) {
        $options[] = ['id' => 'audio', 'label' => 'Iba zvuk', 'hint' => 'Zvuková stopa z videa', 'audio' => true,
            'spec' => 'ba/b', 'size' => $size($bestAudio), 'codec' => strtoupper(explode('.', (string) ($bestAudio['acodec'] ?? ''))[0])];
    } elseif ($hasFF) {
        $muxed = array_values(array_filter($videos, $hasAudio));
        $small = end($muxed) ?: $best;
        $options[] = ['id' => 'audio', 'label' => 'Iba zvuk', 'hint' => 'Zvuková stopa z videa', 'audio' => true,
            'spec' => $small['format_id'], 'size' => null, 'codec' => strtoupper(explode('.', (string) ($small['acodec'] ?? ''))[0])];
    }

    $thumb = $info['thumbnail'] ?? null;
    if (!$thumb && !empty($info['thumbnails'])) {
        $thumb = end($info['thumbnails'])['url'] ?? null;
    }
    return [
        'platform' => $platform,
        'url' => $info['webpage_url'] ?? $url,
        'title' => $info['title'] ?? $info['description'] ?? 'Video',
        'uploader' => $info['uploader'] ?? $info['channel'] ?? $info['creator'] ?? null,
        'duration' => $dur,
        'thumbnail' => register_thumb($thumb),
        'width' => $orig['width'], 'height' => $orig['height'],
        'options' => $options,
    ];
}

// ------------------------------------------------------------------- TikTok bez yt-dlp

function tiktok_item(string $url, string $jar): array
{
    [$code, $html, $eff] = http_get($url, [], $jar);
    if ($code >= 400 || !preg_match('#<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application/json">(.*?)</script>#s', $html, $m)) {
        throw new UserError('TikTok stránku sa nepodarilo načítať. Skúste to znova.', 422);
    }
    $data = json_decode($m[1], true);
    $detail = $data['__DEFAULT_SCOPE__']['webapp.video-detail'] ?? null;
    $item = $detail['itemInfo']['itemStruct'] ?? null;
    if (!$item) {
        $status = (int) ($detail['statusCode'] ?? 0);
        throw new UserError($status ? 'Video už nie je dostupné alebo je súkromné.' : 'Na tomto odkaze sa nenašlo video.', 422);
    }
    if (empty($item['video']['bitrateInfo']) && empty($item['video']['playAddr'])) {
        throw new UserError('Za odkazom sa nenachádza video (možno ide o fotky).', 422);
    }
    $item['_url'] = $eff;
    return $item;
}

function tiktok_variants(array $item): array
{
    $v = $item['video'];
    $list = [];
    foreach ($v['bitrateInfo'] ?? [] as $b) {
        $p = $b['PlayAddr'] ?? [];
        if (empty($p['UrlList'])) {
            continue;
        }
        $list[] = ['id' => (string) $b['GearName'], 'width' => $p['Width'] ?? null, 'height' => $p['Height'] ?? null,
            'size' => isset($p['DataSize']) ? (int) $p['DataSize'] : null, 'codec' => short_codec($b['CodecType'] ?? ''),
            'urls' => $p['UrlList'], 'bitrate' => $b['Bitrate'] ?? 0];
    }
    if (!$list && !empty($v['playAddr'])) {
        $list[] = ['id' => 'play', 'width' => $v['width'] ?? null, 'height' => $v['height'] ?? null, 'size' => null,
            'codec' => short_codec($v['codecType'] ?? 'h264'), 'urls' => [$v['playAddr']], 'bitrate' => 0];
    }
    usort($list, fn($a, $b) => [$b['height'], $b['bitrate']] <=> [$a['height'], $a['bitrate']]);
    return $list;
}

function info_from_tiktok(string $url): array
{
    $jar = tempnam(DATA_DIR . '/tmp', 'ck');
    try {
        $item = tiktok_item($url, $jar);
    } finally {
        @unlink($jar);
    }
    $vars = tiktok_variants($item);
    $opt = fn($x) => ['width' => $x['width'], 'height' => $x['height'], 'fps' => null, 'codec' => $x['codec'],
        'size' => $x['size'], 'spec' => 'tt:' . $x['id']];
    $best = $vars[0];
    $options = [array_merge($opt($best), ['id' => 'original', 'label' => 'Originál', 'hint' => 'Najvyššia kvalita, bez prekódovania'])];
    $short = fn($x) => min((int) $x['height'], (int) ($x['width'] ?? $x['height']));
    $seen = [];
    foreach ($vars as $x) {
        $k = $short($x);
        if ($k >= $short($best) || isset($seen[$k])) {
            continue;
        }
        $seen[$k] = true;
        $options[] = array_merge($opt($x), ['id' => "r$k", 'label' => "{$k}p", 'hint' => 'Menší súbor']);
    }
    if ($best['codec'] !== 'H.264') {
        $h264 = array_values(array_filter($vars, fn($x) => $x['codec'] === 'H.264'));
        if ($h264) {
            usort($h264, fn($a, $b) => [$b['height'], $b['bitrate']] <=> [$a['height'], $a['bitrate']]);
            $o = $opt($h264[0]);
            $dup = false;
            foreach ($options as &$x) {
                if (($x['height'] ?? 0) === $o['height'] && $x['codec'] === 'H.264') {
                    $dup = true;
                }
                if ($x['spec'] === $o['spec']) {
                    $x['hint'] = 'Prehrá každý telefón';
                    $dup = true;
                }
            }
            unset($x);
            if (!$dup) {
                $options[] = array_merge($o, ['id' => 'compat', 'label' => 'MP4 (H.264)', 'hint' => 'Prehrá každý telefón']);
            }
        }
    }
    if (ffmpeg_path()) {
        $small = end($vars);
        $options[] = ['id' => 'audio', 'label' => 'Iba zvuk', 'hint' => 'Zvuková stopa z videa', 'audio' => true,
            'spec' => 'tt:' . $small['id'], 'size' => null, 'codec' => 'AAC'];
    }
    return [
        'platform' => 'tiktok',
        'url' => $item['_url'] ?: $url,
        'title' => trim((string) ($item['desc'] ?? '')) ?: 'TikTok video',
        'uploader' => $item['author']['uniqueId'] ?? null,
        'duration' => $item['video']['duration'] ?? null,
        'thumbnail' => register_thumb($item['video']['cover'] ?? $item['video']['originCover'] ?? null),
        'width' => $best['width'], 'height' => $best['height'],
        'options' => $options,
    ];
}

// =================================================================== sťahovanie

function active_jobs(): int
{
    $n = 0;
    foreach (glob(DATA_DIR . '/jobs/*/meta.json') ?: [] as $m) {
        $dir = dirname($m);
        if (!is_file("$dir/exit") && time() - filemtime($m) < 900) {
            $n++;
        }
    }
    return $n;
}

function api_download(array $in): void
{
    $url = trim((string) ($in['url'] ?? ''));
    $spec = trim((string) ($in['spec'] ?? 'b'));
    $audio = !empty($in['audio']);
    $platform = platform_of($url);
    if (strlen($spec) > 200 || !preg_match('#^[\w\-+/*\[\]^=<>.,: ]+$#', $spec)) {
        throw new UserError('Neplatná kvalita.');
    }
    if (active_jobs() >= MAX_ACTIVE) {
        throw new UserError('Server práve sťahuje iné videá. Skúste to o chvíľu.', 429);
    }
    $id = bin2hex(random_bytes(16));
    $dir = job_dir($id);
    mkdir($dir, 0755, true);
    $meta = ['url' => $url, 'spec' => $spec, 'audio' => $audio, 'platform' => $platform, 'created' => time()];

    if (str_starts_with($spec, 'tt:')) {
        $meta['engine'] = 'php';
        save_json("$dir/meta.json", $meta);
        respond_and_continue(['id' => $id]);
        run_tiktok_job($id, $meta);
        return;
    }
    $ytdlp = ytdlp_cmd();
    if (!$ytdlp) {
        rrmdir($dir);
        throw new UserError('Na serveri nie je nainštalovaný yt-dlp. Otvorte setup.php.', 503);
    }
    $meta['engine'] = 'ytdlp';
    save_json("$dir/meta.json", $meta);
    start_ytdlp_job($id, $ytdlp, $url, $spec, $audio);
    respond(['id' => $id]);
}

/** Pošle odpoveď prehliadaču a PHP pokračuje v práci na pozadí. */
function respond_and_continue(array $data): void
{
    ignore_user_abort(true);
    @set_time_limit(0);
    $body = json_encode($data);
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('Connection: close');
    header('Content-Length: ' . strlen($body));
    header('Content-Encoding: none');
    echo $body;
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } elseif (function_exists('litespeed_finish_request')) {
        litespeed_finish_request();
    } else {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }
}

function start_ytdlp_job(string $id, array $ytdlp, string $url, string $spec, bool $audio): void
{
    $dir = job_dir($id);
    $args = array_merge($ytdlp, [
        '--no-playlist', '--no-warnings', '--no-cache-dir', '--newline', '--no-colors', '--no-part',
        '--progress-template', 'download:[P] %(progress.downloaded_bytes)s %(progress.total_bytes)s %(progress.total_bytes_estimate)s %(progress.speed)s %(progress.eta)s',
        '-f', $spec, '-o', 'out.%(ext)s', '--max-filesize', MAX_FILESIZE_MB . 'M',
        '--socket-timeout', '20', '--retries', '3', '--concurrent-fragments', '4',
        '--print-to-file', '%(title)s', 'title.txt', '--print-to-file', '%(id)s', 'id.txt',
    ]);
    $ff = ffmpeg_path();
    if ($ff) {
        $args = array_merge($args, ['--ffmpeg-location', $ff]);
    }
    if ($audio) {
        if ($ff) {
            $args = array_merge($args, ['-x', '--audio-format', 'best']);
        }
    } else {
        $args = array_merge($args, ['--merge-output-format', 'mp4']);
    }
    $args[] = $url;
    $quoted = implode(' ', array_map('escapeshellarg', $args));
    $script = "cd " . escapeshellarg($dir) . "\n"
        . "export HOME=" . escapeshellarg(DATA_DIR . '/tmp') . " TMPDIR=" . escapeshellarg(DATA_DIR . '/tmp') . " LANG=C.UTF-8 LC_ALL=C.UTF-8\n"
        . "sh -c 'echo \$\$ > pid; exec \"\$0\" \"\$@\"' $quoted > log 2>&1\n"
        . "echo \$? > exit\n";
    file_put_contents("$dir/run.sh", $script);
    $proc = proc_open('nohup sh ' . escapeshellarg("$dir/run.sh") . ' > /dev/null 2>&1 &', [], $pipes);
    if (is_resource($proc)) {
        proc_close($proc);
    }
}

function run_tiktok_job(string $id, array $meta): void
{
    $dir = job_dir($id);
    $progress = function (array $p) use ($dir) {
        save_json("$dir/progress.json", $p);
    };
    $jar = tempnam(DATA_DIR . '/tmp', 'ck');
    try {
        $item = tiktok_item($meta['url'], $jar);
        $gear = substr($meta['spec'], 3);
        $vars = tiktok_variants($item);
        $sel = null;
        foreach ($vars as $x) {
            if ($x['id'] === $gear) {
                $sel = $x;
            }
        }
        $sel = $sel ?? $vars[0];
        file_put_contents("$dir/title.txt", (string) ($item['desc'] ?? 'TikTok video'));
        file_put_contents("$dir/id.txt", (string) ($item['id'] ?? ''));
        $out = "$dir/out.mp4";
        $last = 0.0;
        $start = microtime(true);
        $ok = false;
        foreach ($sel['urls'] as $u) {
            $ok = http_download($u, $out, ['Referer: https://www.tiktok.com/'], $jar,
                function (int $now, int $total) use (&$last, $start, $progress, $dir, $sel) {
                    if (is_file("$dir/cancel")) {
                        return true;
                    }
                    $t = microtime(true);
                    if ($t - $last > 0.4) {
                        $last = $t;
                        $total = $total ?: (int) ($sel['size'] ?? 0);
                        $speed = $now / max(0.001, $t - $start);
                        $progress(['status' => 'downloading', 'downloaded' => $now, 'total' => $total ?: null,
                            'speed' => $speed, 'eta' => $total && $speed ? (int) (($total - $now) / $speed) : null]);
                    }
                    return false;
                });
            if ($ok) {
                break;
            }
        }
        if (is_file("$dir/cancel")) {
            file_put_contents("$dir/exit", 'cancelled');
            return;
        }
        if (!$ok) {
            throw new UserError('Video sa nepodarilo stiahnuť z TikToku. Skúste to znova.');
        }
        if (!empty($meta['audio']) && ($ff = ffmpeg_path())) {
            $progress(['status' => 'processing']);
            [$c] = run([$ff, '-y', '-v', 'error', '-i', $out, '-vn', '-c:a', 'copy', "$dir/out.m4a"], 300);
            if ($c === 0) {
                @unlink($out);
            }
        }
        file_put_contents("$dir/exit", '0');
    } catch (Throwable $e) {
        file_put_contents("$dir/error.txt", $e instanceof UserError ? $e->getMessage() : 'Video sa nepodarilo stiahnuť.');
        file_put_contents("$dir/exit", '1');
    } finally {
        @unlink($jar);
    }
}

function out_file(string $dir): ?string
{
    $files = array_filter(glob("$dir/out.*") ?: [], fn($f) => !preg_match('/\.(part|ytdl|tmp|temp)$|\.f\d+\./', $f));
    if (!$files) {
        return null;
    }
    usort($files, fn($a, $b) => filesize($b) <=> filesize($a));
    return $files[0];
}

function job_status(string $id): array
{
    $meta = load_meta($id);
    $dir = job_dir($id);
    if (is_file("$dir/exit")) {
        $exit = trim((string) file_get_contents("$dir/exit"));
        if ($exit === 'cancelled' || is_file("$dir/cancel")) {
            return ['status' => 'cancelled'];
        }
        $file = out_file($dir);
        if ($exit === '0' && $file) {
            return ['status' => 'done', 'percent' => 100, 'filename' => download_name($dir, $file), 'size' => filesize($file)];
        }
        if (is_file("$dir/error.txt")) {
            return ['status' => 'error', 'error' => file_get_contents("$dir/error.txt")];
        }
        $log = (string) @file_get_contents("$dir/log");
        preg_match_all('/^ERROR: .*$/m', $log, $m);
        return ['status' => 'error', 'error' => friendly_error(end($m[0]) ?: $log)];
    }
    if (($meta['engine'] ?? '') === 'php') {
        $p = is_file("$dir/progress.json") ? json_decode((string) file_get_contents("$dir/progress.json"), true) : null;
        if (!$p) {
            return ['status' => 'queued', 'percent' => 0];
        }
        if ($p['status'] === 'downloading') {
            $p['percent'] = !empty($p['total']) ? round($p['downloaded'] / $p['total'] * 100, 1) : null;
            $p['stream'] = 1;
        }
        return $p;
    }
    // yt-dlp: priebeh čítame z logu
    $log = (string) @file_get_contents("$dir/log");
    if ($log === '') {
        if (time() - ($meta['created'] ?? time()) > 60) {   // proces sa nespustil
            return ['status' => 'error', 'error' => 'Sťahovanie sa na serveri nespustilo. Otvorte setup.php a skontrolujte inštaláciu.'];
        }
        return ['status' => 'queued', 'percent' => 0];
    }
    $streams = max(1, substr_count($log, '[download] Destination:'));
    $lastP = strrpos($log, '[P] ');
    $after = $lastP === false ? $log : substr($log, $lastP);
    if (preg_match('/\[(Merger|ExtractAudio|FixupM\w*|VideoRemuxer|ffmpeg)\]/', $after)) {
        return ['status' => 'processing', 'percent' => 100];
    }
    if ($lastP !== false && preg_match('/^\[P\] (\S+) (\S+) (\S+) (\S+) (\S+)/m', $after, $m)) {
        $num = fn($v) => is_numeric($v) ? (float) $v : null;
        $done = $num($m[1]);
        $total = $num($m[2]) ?? $num($m[3]);
        return ['status' => 'downloading', 'stream' => $streams, 'downloaded' => $done, 'total' => $total,
            'percent' => $done !== null && $total ? round($done / $total * 100, 1) : null,
            'speed' => $num($m[4]), 'eta' => $num($m[5])];
    }
    return ['status' => 'downloading', 'stream' => $streams, 'percent' => null];
}

function download_name(string $dir, string $file): string
{
    $title = @file_get_contents("$dir/title.txt") ?: null;
    $vid = trim((string) @file_get_contents("$dir/id.txt")) ?: null;
    return nice_filename($title ? trim($title) : null, $vid, strtolower(pathinfo($file, PATHINFO_EXTENSION)));
}

function cancel_job(string $id): array
{
    load_meta($id);
    $dir = job_dir($id);
    touch("$dir/cancel");
    $pid = (int) @file_get_contents("$dir/pid");
    if ($pid > 1 && can_run()) {
        run(['kill', (string) $pid], 5);
    }
    return ['ok' => true];
}

function send_file(string $id): void
{
    load_meta($id);
    $dir = job_dir($id);
    $file = out_file($dir);
    if (!is_file("$dir/exit") || !$file) {
        throw new UserError('Súbor ešte nie je pripravený.', 404);
    }
    $types = ['mp4' => 'video/mp4', 'm4a' => 'audio/mp4', 'webm' => 'video/webm', 'mkv' => 'video/x-matroska',
        'mp3' => 'audio/mpeg', 'opus' => 'audio/ogg', 'ogg' => 'audio/ogg'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    @set_time_limit(0);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: attachment; filename="' . download_name($dir, $file) . '"');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    readfile($file);
}

function send_thumb(string $token): void
{
    $f = DATA_DIR . "/thumbs/$token.url";
    $cache = DATA_DIR . "/thumbs/$token.img";
    if (!is_file($f)) {
        http_response_code(404);
        return;
    }
    if (!is_file($cache)) {
        [$code, $body] = http_get(trim((string) file_get_contents($f)), [], null, 15);
        if ($code >= 400 || $body === '') {
            http_response_code(404);
            return;
        }
        file_put_contents($cache, $body);
    }
    $info = @getimagesize($cache);
    if (!$info) {
        http_response_code(404);
        return;
    }
    header('Content-Type: ' . $info['mime']);
    header('Cache-Control: private, max-age=3600');
    readfile($cache);
}

// =================================================================== údržba

function cleanup(): void
{
    $now = time();
    foreach (glob(DATA_DIR . '/jobs/*', GLOB_ONLYDIR) ?: [] as $dir) {
        if ($now - filemtime($dir) > JOB_TTL) {
            $pid = (int) @file_get_contents("$dir/pid");
            if ($pid > 1 && !is_file("$dir/exit") && can_run()) {
                run(['kill', (string) $pid], 5);
            }
            rrmdir($dir);
        }
    }
    foreach (glob(DATA_DIR . '/thumbs/*') ?: [] as $f) {
        if ($now - filemtime($f) > JOB_TTL) {
            @unlink($f);
        }
    }
    foreach (glob(DATA_DIR . '/tmp/ck*') ?: [] as $f) {
        if ($now - filemtime($f) > 3600) {
            @unlink($f);
        }
    }
    // yt-dlp raz týždenne aktualizujeme (platformy často menia stránky)
    $engine = BIN_DIR . '/engine.json';
    if (is_file($engine) && $now - filemtime($engine) > 7 * 86400 && can_run()) {
        touch($engine);
        $cmd = json_decode((string) file_get_contents($engine), true)['cmd'] ?? null;
        if ($cmd) {
            $proc = proc_open('nohup ' . implode(' ', array_map('escapeshellarg', array_merge($cmd, ['-U']))) . ' > /dev/null 2>&1 &', [], $pipes);
            if (is_resource($proc)) {
                proc_close($proc);
            }
        }
    }
}
