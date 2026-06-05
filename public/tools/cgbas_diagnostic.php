<?php
/**
 * CGBAS / RTK CONNECTION DIAGNOSTIC TOOL
 * ---------------------------------------------------------------
 * Mini web app để chẩn đoán kết nối tới hệ thống CGBAS/RTK.
 * Truy cập:  https://<your-domain>/public/tools/cgbas_diagnostic.php
 *
 * Tính năng:
 *   1. Kiểm tra DNS
 *   2. Kiểm tra TCP socket
 *   3. Kiểm tra HTTP cơ bản
 *   4. Test API có HMAC: online-users, storage tasks
 *   5. Phân loại & bóc tách lỗi rõ ràng (NETWORK / HTTP / AUTH / JSON / APP)
 *   6. Hiển thị config (secret đã mask), signature, headers, raw response.
 *
 * Mật khẩu mặc định: "cgbas2025"  (đổi tại biến $TOOL_PASSWORD bên dưới).
 */

// ============ BẢO MẬT ============
$TOOL_PASSWORD = 'cgbas2025'; // ĐỔI MẬT KHẨU TẠI ĐÂY
session_start();
if (isset($_GET['logout'])) {
    unset($_SESSION['cgbas_diag_ok']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
if (!empty($_POST['password'])) {
    if (hash_equals($TOOL_PASSWORD, (string)$_POST['password'])) {
        $_SESSION['cgbas_diag_ok'] = true;
    } else {
        $loginError = 'Sai mật khẩu.';
    }
}
$authed = !empty($_SESSION['cgbas_diag_ok']);

// ============ LOAD CONFIG (KHÔNG KÍCH HOẠT ERROR HANDLER PRODUCTION) ============
$rootPath = dirname(__DIR__, 2);
$configLoaded = false;
$configError  = null;
try {
    // Force debug mode trước khi include config để tránh error redirect
    if (!defined('APP_ENV_FORCED')) {
        $_ENV['APP_ENV']   = 'development';
        $_SERVER['APP_ENV'] = 'development';
        putenv('APP_ENV=development');
        define('APP_ENV_FORCED', true);
    }
    if (file_exists($rootPath . '/private/config/env_loader.php')) {
        require_once $rootPath . '/private/config/env_loader.php';
    }
    if (file_exists($rootPath . '/private/api/rtk_system/generate_hash.php')) {
        require_once $rootPath . '/private/api/rtk_system/generate_hash.php';
    }
    // Định nghĩa hằng số RTK nếu chưa có
    if (!defined('RTK_API_URL'))         define('RTK_API_URL',         env('RTK_API_URL', 'http://rtk.taikhoandodac.vn:8090/openapi/broadcast/users'));
    if (!defined('RTK_API_ACCESS_KEY'))  define('RTK_API_ACCESS_KEY',  env('RTK_API_ACCESS_KEY', ''));
    if (!defined('RTK_API_SECRET_KEY'))  define('RTK_API_SECRET_KEY',  env('RTK_API_SECRET_KEY', ''));
    if (!defined('RTK_API_SIGN_METHOD')) define('RTK_API_SIGN_METHOD', env('RTK_API_SIGN_METHOD', 'HmacSHA256'));
    $configLoaded = true;
} catch (Throwable $e) {
    $configError = $e->getMessage();
}

// ============ HELPER FUNCTIONS ============

function mask($s, $keep = 4) {
    $s = (string)$s;
    $n = strlen($s);
    if ($n <= $keep * 2) return str_repeat('*', $n);
    return substr($s, 0, $keep) . str_repeat('*', max(4, $n - $keep * 2)) . substr($s, -$keep);
}

function parseHostPort($url) {
    $p = parse_url($url);
    return [
        'scheme' => $p['scheme'] ?? 'http',
        'host'   => $p['host']   ?? null,
        'port'   => $p['port']   ?? (($p['scheme'] ?? 'http') === 'https' ? 443 : 80),
        'path'   => $p['path']   ?? '/'
    ];
}

/**
 * Phân loại lỗi để dễ chẩn đoán
 * @return ['category'=>'OK|NETWORK|DNS|TIMEOUT|HTTP|AUTH|JSON|APP|CONFIG|EXCEPTION', 'level'=>'success|warn|error|info', 'hint'=>'...']
 */
function classify($httpCode, $curlErrno, $curlError, $body, $parsed) {
    // Lỗi cURL trước
    if ($curlErrno) {
        $map = [
            CURLE_COULDNT_RESOLVE_HOST => ['DNS',     'error', 'Không phân giải được tên miền. Kiểm tra DNS hoặc tên host.'],
            CURLE_COULDNT_CONNECT      => ['NETWORK', 'error', 'Không kết nối được tới server. Port bị chặn hoặc service không chạy.'],
            CURLE_OPERATION_TIMEDOUT   => ['TIMEOUT', 'error', 'Hết thời gian chờ. Mạng chậm hoặc server quá tải.'],
            CURLE_SSL_CONNECT_ERROR    => ['NETWORK', 'error', 'Lỗi SSL handshake.'],
        ];
        if (isset($map[$curlErrno])) {
            return ['category' => $map[$curlErrno][0], 'level' => $map[$curlErrno][1], 'hint' => $map[$curlErrno][2]];
        }
        return ['category' => 'NETWORK', 'level' => 'error', 'hint' => 'cURL Error #' . $curlErrno . ': ' . $curlError];
    }

    // HTTP code 0 = không nhận được response
    if ($httpCode === 0) {
        return ['category' => 'NETWORK', 'level' => 'error', 'hint' => 'Không nhận được HTTP response.'];
    }

    // 401 / 403 → auth fail
    if ($httpCode === 401 || $httpCode === 403) {
        return ['category' => 'AUTH', 'level' => 'error', 'hint' => 'Sai chữ ký HMAC / thiếu credentials / access key bị thu hồi.'];
    }

    // 5xx → lỗi server
    if ($httpCode >= 500) {
        return ['category' => 'HTTP', 'level' => 'error', 'hint' => 'Lỗi nội bộ server CGBAS (5xx).'];
    }

    // 4xx khác → lỗi client
    if ($httpCode >= 400) {
        return ['category' => 'HTTP', 'level' => 'error', 'hint' => 'Lỗi client / endpoint không tồn tại / sai tham số.'];
    }

    // 2xx → check JSON & code
    if ($httpCode >= 200 && $httpCode < 300) {
        if ($body !== null && $body !== '' && $parsed === null && json_last_error() !== JSON_ERROR_NONE) {
            return ['category' => 'JSON', 'level' => 'warn', 'hint' => 'Response không phải JSON hợp lệ: ' . json_last_error_msg()];
        }
        if (is_array($parsed) && isset($parsed['code'])) {
            $code = $parsed['code'];
            if ($code === 'SUCCESS' || $code === 'OK') {
                return ['category' => 'OK', 'level' => 'success', 'hint' => 'API trả về thành công.'];
            }
            return ['category' => 'APP', 'level' => 'warn', 'hint' => 'API trả về code=' . $code . ' (' . ($parsed['msg'] ?? '') . ')'];
        }
        return ['category' => 'OK', 'level' => 'success', 'hint' => 'HTTP 2xx (không có trường "code").'];
    }

    // 3xx
    return ['category' => 'HTTP', 'level' => 'warn', 'hint' => 'Redirect HTTP ' . $httpCode];
}

/**
 * Gọi API với HMAC auth (hoặc không) và trả về toàn bộ chi tiết để debug.
 */
function callRtkApi($method, $uri, $queryParams = [], $bodyPayload = null, $useAuth = true, $timeout = 15) {
    $configUrl = RTK_API_URL;
    $baseUrl   = str_replace('/openapi/broadcast/users', '', $configUrl);
    $qs        = $queryParams ? '?' . http_build_query($queryParams) : '';
    $url       = $baseUrl . $uri . $qs;

    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    $signatureInfo = null;

    if ($useAuth) {
        if (!function_exists('generateRtkApiSignature')) {
            return [
                'success'  => false,
                'category' => 'CONFIG', 'level' => 'error',
                'hint'     => 'Thiếu file generate_hash.php hoặc hàm generateRtkApiSignature.',
                'url'      => $url,
            ];
        }
        $nonce     = bin2hex(random_bytes(16));
        $timestamp = (string) round(microtime(true) * 1000);
        $authHdr = [
            'X-Nonce'       => $nonce,
            'X-Access-Key'  => RTK_API_ACCESS_KEY,
            'X-Sign-Method' => RTK_API_SIGN_METHOD,
            'X-Timestamp'   => $timestamp,
        ];
        $sign = generateRtkApiSignature($method, $uri, $authHdr, RTK_API_SECRET_KEY);
        $authHdr['Sign'] = $sign;
        $signatureInfo = ['nonce' => $nonce, 'timestamp' => $timestamp, 'sign' => $sign];
        foreach ($authHdr as $k => $v) $headers[] = "$k: $v";
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_HEADER         => false,
    ]);
    if ($bodyPayload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bodyPayload));
    }

    $start    = microtime(true);
    $response = curl_exec($ch);
    $ms       = round((microtime(true) - $start) * 1000, 2);
    $info     = curl_getinfo($ch);
    $errno    = curl_errno($ch);
    $errMsg   = curl_error($ch);
    curl_close($ch);

    $parsed = null;
    if ($response !== false && $response !== '') {
        $parsed = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) $parsed = null;
    }

    $verdict = classify((int)$info['http_code'], $errno, $errMsg, $response, $parsed);

    return array_merge([
        'success'       => $verdict['level'] === 'success',
        'method'        => $method,
        'url'           => $url,
        'request_hdrs'  => $headers,
        'signature'     => $signatureInfo,
        'time_ms'       => $ms,
        'http_code'     => (int)$info['http_code'],
        'content_type'  => $info['content_type'] ?? '',
        'size'          => is_string($response) ? strlen($response) : 0,
        'curl_errno'    => $errno,
        'curl_error'    => $errMsg,
        'raw'           => is_string($response) ? $response : '',
        'parsed'        => $parsed,
    ], $verdict);
}

function renderResult($title, $r) {
    $level = $r['level']    ?? 'info';
    $cat   = $r['category'] ?? '?';
    $hint  = $r['hint']     ?? '';
    $colors = [
        'success' => '#16a34a',
        'warn'    => '#d97706',
        'error'   => '#dc2626',
        'info'    => '#0284c7',
    ];
    $bg = $colors[$level] ?? '#64748b';
    echo '<div class="card">';
    echo '<div class="card-head" style="border-left-color:' . $bg . '">';
    echo '<div><span class="badge" style="background:' . $bg . '">' . htmlspecialchars($cat) . '</span> ';
    echo '<strong>' . htmlspecialchars($title) . '</strong></div>';
    if (isset($r['time_ms'])) echo '<div class="time">' . $r['time_ms'] . ' ms</div>';
    echo '</div>';

    echo '<div class="card-body">';
    if ($hint) echo '<p class="hint">' . htmlspecialchars($hint) . '</p>';

    if (!empty($r['url'])) {
        echo '<div class="kv"><b>URL:</b><code>' . htmlspecialchars($r['method'] ?? 'GET') . ' ' . htmlspecialchars($r['url']) . '</code></div>';
    }
    if (isset($r['http_code']) && $r['http_code'] !== null) {
        $codeColor = $r['http_code'] >= 200 && $r['http_code'] < 300 ? '#16a34a' : ($r['http_code'] >= 400 ? '#dc2626' : '#d97706');
        echo '<div class="kv"><b>HTTP Code:</b><span style="color:' . $codeColor . ';font-weight:bold">' . $r['http_code'] . '</span></div>';
    }
    if (!empty($r['content_type']))  echo '<div class="kv"><b>Content-Type:</b><code>' . htmlspecialchars($r['content_type']) . '</code></div>';
    if (!empty($r['curl_error']))    echo '<div class="kv"><b>cURL Error:</b><code style="color:#dc2626">#' . (int)$r['curl_errno'] . ' ' . htmlspecialchars($r['curl_error']) . '</code></div>';

    if (!empty($r['signature'])) {
        echo '<details><summary>Signature info</summary><pre>';
        echo 'Nonce:     ' . htmlspecialchars($r['signature']['nonce']) . "\n";
        echo 'Timestamp: ' . htmlspecialchars($r['signature']['timestamp']) . "\n";
        echo 'Sign:      ' . htmlspecialchars($r['signature']['sign']);
        echo '</pre></details>';
    }
    if (!empty($r['request_hdrs'])) {
        echo '<details><summary>Request headers</summary><pre>';
        foreach ($r['request_hdrs'] as $h) {
            $h = preg_replace('/^(X-Access-Key:\s*)(.+)$/i', '$1' . mask(RTK_API_ACCESS_KEY), $h);
            echo htmlspecialchars($h) . "\n";
        }
        echo '</pre></details>';
    }
    if (!empty($r['parsed'])) {
        echo '<details open><summary>Parsed JSON</summary><pre>' . htmlspecialchars(json_encode($r['parsed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre></details>';
    } elseif (!empty($r['raw'])) {
        echo '<details><summary>Raw response (' . $r['size'] . ' bytes)</summary><pre>' . htmlspecialchars(substr($r['raw'], 0, 4000)) . '</pre></details>';
    }
    echo '</div></div>';
}

// ============ XỬ LÝ TEST KHI USER SUBMIT ============
$tests = $_POST['tests'] ?? [];
$runTests = $authed && isset($_POST['run']);
$customUrl = trim((string)($_POST['custom_url'] ?? ''));

?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CGBAS Diagnostic Tool</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#0f172a;color:#e2e8f0;line-height:1.5;padding:20px}
  .wrap{max-width:1100px;margin:0 auto}
  h1{font-size:24px;margin-bottom:6px;color:#fff}
  h1 .pill{font-size:11px;padding:3px 8px;background:#1e40af;border-radius:999px;vertical-align:middle;margin-left:8px}
  .sub{color:#94a3b8;font-size:13px;margin-bottom:20px}
  .panel{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:18px;margin-bottom:18px}
  .panel h2{font-size:15px;color:#cbd5e1;margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;font-weight:600}
  label{display:block;font-size:13px;color:#cbd5e1;margin-bottom:4px}
  input[type=text],input[type=password]{width:100%;padding:9px 12px;border-radius:6px;border:1px solid #475569;background:#0f172a;color:#e2e8f0;font-size:14px;font-family:inherit}
  input[type=text]:focus,input[type=password]:focus{outline:none;border-color:#3b82f6}
  button{background:#3b82f6;color:#fff;border:none;padding:10px 18px;border-radius:6px;cursor:pointer;font-size:14px;font-weight:600}
  button:hover{background:#2563eb}
  .btn-ghost{background:#334155}
  .btn-ghost:hover{background:#475569}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .check{display:flex;align-items:center;gap:8px;background:#0f172a;padding:10px 12px;border-radius:6px;border:1px solid #334155;font-size:13px;cursor:pointer}
  .check:hover{border-color:#3b82f6}
  .check input{cursor:pointer}
  .conf{background:#0f172a;padding:12px;border-radius:6px;border:1px solid #334155;font-family:Consolas,monospace;font-size:12px;color:#94a3b8;white-space:pre}
  .card{background:#1e293b;border:1px solid #334155;border-radius:10px;margin-bottom:14px;overflow:hidden}
  .card-head{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:#0f172a;border-left:4px solid #64748b}
  .badge{display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;color:#fff;letter-spacing:.5px}
  .time{color:#94a3b8;font-size:12px;font-family:Consolas,monospace}
  .card-body{padding:14px 16px}
  .hint{background:#0f172a;border-left:3px solid #475569;padding:8px 12px;margin-bottom:10px;font-size:13px;color:#cbd5e1;border-radius:3px}
  .kv{font-size:13px;margin:4px 0;color:#cbd5e1}
  .kv b{color:#94a3b8;display:inline-block;min-width:110px;font-weight:600}
  .kv code{background:#0f172a;padding:2px 6px;border-radius:3px;color:#a5f3fc;font-size:12px;word-break:break-all}
  details{margin-top:8px;background:#0f172a;border-radius:6px;border:1px solid #334155}
  summary{padding:8px 12px;cursor:pointer;font-size:12px;color:#94a3b8;font-weight:600;user-select:none}
  summary:hover{color:#e2e8f0}
  pre{padding:10px 12px;font-size:11.5px;color:#cbd5e1;overflow:auto;max-height:380px;font-family:Consolas,monospace}
  .legend{display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:#94a3b8;margin-top:12px}
  .legend span{display:flex;align-items:center;gap:6px}
  .legend i{display:inline-block;width:12px;height:12px;border-radius:3px}
  .err{background:#7f1d1d;border:1px solid #dc2626;color:#fecaca;padding:10px 14px;border-radius:6px;margin-bottom:14px;font-size:13px}
  .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
  a.logout{color:#94a3b8;font-size:13px;text-decoration:none}
  a.logout:hover{color:#fff}
  @media (max-width:600px){.grid{grid-template-columns:1fr}.kv b{display:block;min-width:0}}
</style>
</head>
<body>
<div class="wrap">

<?php if (!$authed): ?>
  <div class="top">
    <h1>CGBAS Diagnostic <span class="pill">v1.0</span></h1>
  </div>
  <div class="panel" style="max-width:420px;margin:60px auto">
    <h2>Đăng nhập</h2>
    <?php if (!empty($loginError)): ?><div class="err"><?= htmlspecialchars($loginError) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <label>Mật khẩu công cụ</label>
      <input type="password" name="password" autofocus required>
      <div style="margin-top:14px"><button>Vào</button></div>
    </form>
    <p style="color:#64748b;font-size:12px;margin-top:14px">Đổi mật khẩu trong biến <code style="color:#a5f3fc">$TOOL_PASSWORD</code> đầu file.</p>
  </div>

<?php else: ?>

  <div class="top">
    <h1>CGBAS Diagnostic <span class="pill">v1.0</span></h1>
    <a class="logout" href="?logout=1">Đăng xuất ›</a>
  </div>
  <div class="sub">Chẩn đoán kết nối tới hệ thống CGBAS/RTK. Mọi lỗi sẽ được phân loại theo nhóm: <b>DNS · NETWORK · HTTP · AUTH · JSON · APP</b>.</div>

  <?php if (!$configLoaded): ?>
    <div class="err">Không nạp được config: <?= htmlspecialchars((string)$configError) ?></div>
  <?php endif; ?>

  <!-- Config panel -->
  <div class="panel">
    <h2>Cấu hình hiện tại</h2>
    <?php
    $hp = parseHostPort(RTK_API_URL);
    $confLines = sprintf(
      "RTK_API_URL          = %s\nRTK_API_ACCESS_KEY   = %s\nRTK_API_SECRET_KEY   = %s\nRTK_API_SIGN_METHOD  = %s\nHost                 = %s\nPort                 = %d\nScheme               = %s\nPHP_VERSION          = %s\ncurl_version         = %s",
      RTK_API_URL,
      mask(RTK_API_ACCESS_KEY),
      mask(RTK_API_SECRET_KEY),
      RTK_API_SIGN_METHOD,
      $hp['host'] ?? '?',
      $hp['port'],
      $hp['scheme'],
      PHP_VERSION,
      curl_version()['version'] ?? '?'
    );
    echo '<div class="conf">' . htmlspecialchars($confLines) . '</div>';
    ?>
  </div>

  <!-- Test form -->
  <div class="panel">
    <h2>Chọn bài test</h2>
    <form method="post">
      <div class="grid">
        <?php
        $allTests = [
          'dns'      => 'Phân giải DNS (gethostbyname)',
          'tcp'      => 'Mở TCP socket tới host:port',
          'http'     => 'HTTP GET cơ bản đến base URL',
          'online'   => 'API /openapi/broadcast/online-users  (HMAC)',
          'tasks'    => 'API /openapi/stream/storage/tasks    (HMAC)',
          'noauth'   => 'API online-users KHÔNG auth (kỳ vọng 401)',
        ];
        $selected = !empty($tests) ? $tests : array_keys($allTests);
        foreach ($allTests as $k => $label) {
          $chk = in_array($k, $selected, true) ? 'checked' : '';
          echo '<label class="check"><input type="checkbox" name="tests[]" value="' . $k . '" ' . $chk . '> ' . htmlspecialchars($label) . '</label>';
        }
        ?>
      </div>

      <div style="margin-top:14px">
        <label>URL tùy ý (optional - sẽ GET với HMAC auth)</label>
        <input type="text" name="custom_url" placeholder="http://rtk.taikhoandodac.vn:8090/openapi/..." value="<?= htmlspecialchars($customUrl) ?>">
      </div>

      <div style="margin-top:16px;display:flex;gap:8px">
        <button name="run" value="1">▶ Chạy test</button>
        <button type="button" class="btn-ghost" onclick="document.querySelectorAll('input[name=\'tests[]\']').forEach(c=>c.checked=true)">Chọn tất cả</button>
        <button type="button" class="btn-ghost" onclick="document.querySelectorAll('input[name=\'tests[]\']').forEach(c=>c.checked=false)">Bỏ chọn</button>
      </div>
    </form>

    <div class="legend">
      <span><i style="background:#16a34a"></i>OK – kết nối + auth + dữ liệu thành công</span>
      <span><i style="background:#d97706"></i>WARN – phản hồi nhưng có vấn đề</span>
      <span><i style="background:#dc2626"></i>ERROR – không kết nối được / sai auth</span>
    </div>
  </div>

  <?php if ($runTests): ?>
    <div class="panel">
      <h2>Kết quả</h2>

      <?php
      $hp   = parseHostPort(RTK_API_URL);
      $host = $hp['host']; $port = $hp['port'];

      // 1. DNS
      if (in_array('dns', $tests, true)) {
          $start = microtime(true);
          $ip    = $host ? @gethostbyname($host) : false;
          $ms    = round((microtime(true) - $start) * 1000, 2);
          if (!$host) {
              renderResult('DNS Resolution', ['category'=>'CONFIG','level'=>'error','hint'=>'RTK_API_URL không hợp lệ.','time_ms'=>$ms]);
          } elseif ($ip && $ip !== $host) {
              renderResult('DNS Resolution', ['category'=>'OK','level'=>'success','time_ms'=>$ms,'hint'=>"Host '$host' → IP $ip"]);
          } else {
              renderResult('DNS Resolution', ['category'=>'DNS','level'=>'error','time_ms'=>$ms,'hint'=>"Không phân giải được '$host'. Kiểm tra DNS server hoặc tên miền."]);
          }
      }

      // 2. TCP socket
      if (in_array('tcp', $tests, true)) {
          $start = microtime(true);
          $fp = @fsockopen($host, $port, $errno, $errstr, 8);
          $ms = round((microtime(true) - $start) * 1000, 2);
          if ($fp) {
              fclose($fp);
              renderResult("TCP Socket {$host}:{$port}", ['category'=>'OK','level'=>'success','time_ms'=>$ms,'hint'=>'Port mở, có thể bắt tay được.']);
          } else {
              renderResult("TCP Socket {$host}:{$port}", ['category'=>'NETWORK','level'=>'error','time_ms'=>$ms,'hint'=>"$errstr (errno=$errno). Port bị chặn / service không chạy / hosting không cho outbound."]);
          }
      }

      // 3. HTTP GET cơ bản
      if (in_array('http', $tests, true)) {
          $base = $hp['scheme'] . '://' . $host . ':' . $port . '/';
          $ch = curl_init($base);
          curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_NOBODY=>false]);
          $start = microtime(true);
          $resp  = curl_exec($ch);
          $ms    = round((microtime(true) - $start) * 1000, 2);
          $info  = curl_getinfo($ch);
          $en    = curl_errno($ch); $em = curl_error($ch);
          curl_close($ch);
          $r = [
            'url'=>$base,'method'=>'GET','time_ms'=>$ms,
            'http_code'=>(int)$info['http_code'],
            'content_type'=>$info['content_type'] ?? '',
            'curl_errno'=>$en,'curl_error'=>$em,
            'raw'=>is_string($resp)?substr($resp,0,2000):'',
            'size'=>is_string($resp)?strlen($resp):0,
          ];
          $verdict = classify($r['http_code'], $en, $em, $resp, null);
          // 401/403 ở đây không phải lỗi tổng quát mà chỉ là server đang chạy
          if ($r['http_code'] === 401 || $r['http_code'] === 403) {
              $verdict = ['category'=>'OK','level'=>'success','hint'=>'Server đang chạy (' . $r['http_code'] . ' – cần auth, đúng như mong đợi).'];
          }
          renderResult('HTTP GET base URL', array_merge($r, $verdict));
      }

      // 4. Online users (auth)
      if (in_array('online', $tests, true)) {
          $r = callRtkApi('GET', '/openapi/broadcast/online-users', ['page'=>1,'size'=>5,'status'=>''], null, true, 15);
          renderResult('API online-users (HMAC auth)', $r);
      }

      // 5. Storage tasks (auth)
      if (in_array('tasks', $tests, true)) {
          $r = callRtkApi('GET', '/openapi/stream/storage/tasks', ['page'=>1,'size'=>5], null, true, 15);
          renderResult('API storage tasks (HMAC auth)', $r);
      }

      // 6. NO AUTH (kỳ vọng 401/403/5xx để xác nhận server còn enforce auth)
      if (in_array('noauth', $tests, true)) {
          $r = callRtkApi('GET', '/openapi/broadcast/online-users', ['page'=>1,'size'=>1], null, false, 10);
          $hc = $r['http_code'];
          if ($hc === 401 || $hc === 403) {
              $r['category']='OK'; $r['level']='success';
              $r['hint']='Server enforce auth chuẩn (HTTP ' . $hc . ').';
          } elseif ($hc >= 500) {
              $r['category']='OK'; $r['level']='success';
              $r['hint']='Server từ chối request không auth (HTTP ' . $hc . ') – auth được enforce dù response không chuẩn.';
          } elseif ($hc >= 200 && $hc < 300) {
              $r['category']='APP'; $r['level']='warn';
              $r['hint']='⚠ Endpoint MỞ không cần auth – cảnh báo bảo mật!';
          }
          renderResult('API online-users KHÔNG auth', $r);
      }

      // 7. Custom URL
      if ($customUrl !== '') {
          $p = parse_url($customUrl);
          $uri = ($p['path'] ?? '/') . (!empty($p['query']) ? '?' . $p['query'] : '');
          $ch = curl_init($customUrl);
          $nonce = bin2hex(random_bytes(16));
          $timestamp = (string) round(microtime(true)*1000);
          $hdr = ['X-Nonce'=>$nonce,'X-Access-Key'=>RTK_API_ACCESS_KEY,'X-Sign-Method'=>RTK_API_SIGN_METHOD,'X-Timestamp'=>$timestamp];
          $sign = function_exists('generateRtkApiSignature') ? generateRtkApiSignature('GET', $p['path'] ?? '/', $hdr, RTK_API_SECRET_KEY) : '';
          $hdr['Sign'] = $sign;
          $headers = ['Accept: application/json'];
          foreach ($hdr as $k=>$v) $headers[] = "$k: $v";
          curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>$headers]);
          $start = microtime(true);
          $resp = curl_exec($ch);
          $ms = round((microtime(true)-$start)*1000,2);
          $info = curl_getinfo($ch); $en=curl_errno($ch); $em=curl_error($ch);
          curl_close($ch);
          $parsed = json_decode((string)$resp, true);
          if (json_last_error()!==JSON_ERROR_NONE) $parsed=null;
          $verdict = classify((int)$info['http_code'], $en, $em, $resp, $parsed);
          renderResult('Custom URL', array_merge([
            'url'=>$customUrl,'method'=>'GET','time_ms'=>$ms,'http_code'=>(int)$info['http_code'],
            'content_type'=>$info['content_type']??'','curl_errno'=>$en,'curl_error'=>$em,
            'request_hdrs'=>$headers,'signature'=>['nonce'=>$nonce,'timestamp'=>$timestamp,'sign'=>$sign],
            'raw'=>is_string($resp)?$resp:'','size'=>is_string($resp)?strlen($resp):0,'parsed'=>$parsed,
          ], $verdict));
      }
      ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

<p style="text-align:center;color:#475569;font-size:11px;margin-top:30px">CGBAS Diagnostic · Tài khoản đo đạc · <?= date('Y-m-d H:i:s') ?></p>
</div>
</body>
</html>
