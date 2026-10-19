<?php
// ===== 安全取得顯示資訊 =====
$title = $data['title'] ?? '404';
$url   = $data['url']   ?? ($_GET['url'] ?? '/');
$url   = htmlspecialchars((string)$url, ENT_QUOTES, 'UTF-8');

$ua = htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', ENT_QUOTES, 'UTF-8');

// ===== Client IP（瀏覽器那端）=====
$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$clientIp = trim(explode(',', (string)$clientIp)[0]);
$clientIp = htmlspecialchars($clientIp, ENT_QUOTES, 'UTF-8');

// ===== Server IP / Time（Linux 控制器本機，優先）=====
$serverTime = '';
$serverIp   = '';

// 有些環境會禁用 shell_exec
$canShell = function_exists('shell_exec') && is_callable('shell_exec');

if ($canShell) {
    // Linux time
    $serverTime = trim((string)@shell_exec('date "+%Y-%m-%d %H:%M:%S" 2>/dev/null'));

    // Linux IP（hostname -I 可能回多個 IP，取第一個）
    $ipLine = trim((string)@shell_exec('hostname -I 2>/dev/null'));
    if ($ipLine !== '') {
        $serverIp = trim(preg_split('/\s+/', $ipLine)[0] ?? '');
    }

    // 若 hostname -I 沒抓到，用 ip route 再試一次（更準）
    if ($serverIp === '') {
        $serverIp = trim((string)@shell_exec("ip route get 1.1.1.1 2>/dev/null | awk '{for(i=1;i<=NF;i++) if(\$i==\"src\") {print \$(i+1); exit}}'"));
    }
}

// fallback：PHP / Web server 變數
if ($serverTime === '') $serverTime = date('Y-m-d H:i:s');
if ($serverIp   === '') $serverIp   = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()) ?? '0.0.0.0';

$serverTime = htmlspecialchars($serverTime, ENT_QUOTES, 'UTF-8');
$serverIp   = htmlspecialchars($serverIp, ENT_QUOTES, 'UTF-8');


// Back URL：你可以改成 Dashboards/index 或 Dashboards
$backUrl = (defined('URLROOT') ? URLROOT : './') . '?url=Dashboards';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
<style>
:root{
  --bg:#070a0f;
  --panel:#0b1220cc;
  --border:#2bff8899;
  --green:#2bff88;
  --red:#ff5252;
  --cyan:#44d9ff;
  --text:#e8f1ff;
  --muted:#9aa7bd;
  --shadow: 0 20px 80px rgba(0,0,0,.55);
}
*{box-sizing:border-box}
body{
  margin:0;
  background: radial-gradient(1200px 800px at 20% 10%, #0d1b2f 0%, var(--bg) 60%),
              radial-gradient(900px 600px at 90% 90%, #112a1d 0%, transparent 55%),
              linear-gradient(180deg, #04060a, #070a0f);
  color:var(--text);
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
  overflow:hidden;
}
a{color:inherit}

/* ===== 背景掃描線 + 雜訊 ===== */
.scanlines::before{
  content:"";
  position:fixed; inset:0;
  background:
    linear-gradient(to bottom,
      rgba(255,255,255,.05) 0px,
      rgba(255,255,255,.02) 1px,
      rgba(0,0,0,0) 3px);
  background-size: 100% 6px;
  opacity:.25;
  pointer-events:none;
  mix-blend-mode:overlay;
}
.noise::after{
  content:"";
  position:fixed; inset:-20%;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='180' height='180' filter='url(%23n)' opacity='.25'/%3E%3C/svg%3E");
  opacity:.08;
  pointer-events:none;
  animation: noiseMove 6s infinite linear;
}
@keyframes noiseMove{
  0%{transform:translate3d(0,0,0)}
  100%{transform:translate3d(-10%, -10%, 0)}
}

/* ===== 主容器 ===== */
.wrap{
  height:100vh;
  display:grid;
  place-items:center;
  padding:24px;
}
.card{
  width:min(980px, 95vw);
  border:1px solid rgba(43,255,136,.25);
  background: linear-gradient(180deg, rgba(11,18,32,.75), rgba(11,18,32,.45));
  border-radius:18px;
  box-shadow: var(--shadow);
  position:relative;
  overflow:hidden;
}
.card::before{
  content:"";
  position:absolute; inset:0;
  background: radial-gradient(700px 240px at 10% 0%, rgba(68,217,255,.12), transparent 55%),
              radial-gradient(700px 240px at 90% 100%, rgba(43,255,136,.10), transparent 55%);
  pointer-events:none;
}
.topbar{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:14px 16px;
  border-bottom:1px solid rgba(255,255,255,.06);
}
.dots{
  display:flex; gap:8px;
}
.dot{
  width:10px; height:10px; border-radius:999px;
  background:rgba(255,255,255,.18);
}
.dot.red{background:rgba(255,82,82,.75)}
.dot.green{background:rgba(43,255,136,.75)}
.dot.cyan{background:rgba(68,217,255,.75)}
.badge{
  font-size:12px;
  padding:4px 10px;
  border:1px solid rgba(43,255,136,.35);
  border-radius:999px;
  color:var(--green);
  letter-spacing:.4px;
  background:rgba(0,0,0,.18);
}
.body{
  padding:22px 22px 18px;
  display:grid;
  grid-template-columns: 1.2fr .8fr;
  gap:18px;
}
@media (max-width:900px){
  .body{grid-template-columns:1fr}
}

/* ===== Glitch 404 ===== */
.glitch{
  font-size: clamp(56px, 8vw, 110px);
  margin: 6px 0 4px;
  font-weight:900;
  letter-spacing: 2px;
  position:relative;
  color: var(--red);
  text-shadow:
    0 0 18px rgba(255,82,82,.25),
    0 0 40px rgba(255,82,82,.12);
}
.glitch::before, .glitch::after{
  content: attr(data-text);
  position:absolute; left:0; top:0;
  width:100%;
  opacity:.7;
  clip-path: inset(0 0 0 0);
}
.glitch::before{
  transform: translate(2px,0);
  color: var(--cyan);
  animation: glitch1 2.2s infinite linear alternate-reverse;
}
.glitch::after{
  transform: translate(-2px,0);
  color: var(--green);
  animation: glitch2 1.7s infinite linear alternate-reverse;
}
@keyframes glitch1{
  0%{clip-path: inset(10% 0 78% 0)}
  20%{clip-path: inset(45% 0 40% 0)}
  40%{clip-path: inset(20% 0 60% 0)}
  60%{clip-path: inset(70% 0 10% 0)}
  80%{clip-path: inset(35% 0 45% 0)}
  100%{clip-path: inset(0 0 85% 0)}
}
@keyframes glitch2{
  0%{clip-path: inset(0 0 90% 0)}
  25%{clip-path: inset(60% 0 15% 0)}
  50%{clip-path: inset(30% 0 55% 0)}
  75%{clip-path: inset(80% 0 5% 0)}
  100%{clip-path: inset(15% 0 70% 0)}
}

.subtitle{
  margin:0;
  color:var(--muted);
  line-height:1.55;
  font-size:14px;
}
.kv{
  margin-top:14px;
  border:1px solid rgba(255,255,255,.07);
  border-radius:14px;
  overflow:hidden;
}
.kv .row{
  display:flex;
  gap:10px;
  padding:10px 12px;
  border-top:1px solid rgba(255,255,255,.06);
  background:rgba(0,0,0,.10);
}
.kv .row:first-child{border-top:0}
.kv .k{
  width:110px;
  color:#b7c3d8;
  opacity:.9;
}
.kv .v{
  flex:1;
  color:#e8f1ff;
  word-break:break-all;
}

/* ===== Terminal panel ===== */
.terminal{
  border:1px solid rgba(68,217,255,.18);
  border-radius:14px;
  background:rgba(0,0,0,.22);
  padding:14px 14px 12px;
  position:relative;
  min-height: 230px;
}
.termTitle{
  display:flex;
  align-items:center;
  justify-content:space-between;
  font-size:12px;
  color:#cfe7ff;
  opacity:.9;
  margin-bottom:8px;
}
.termTitle .right{
  color:var(--cyan);
  opacity:.8;
}
.term{
  font-size:12px;
  line-height:1.6;
  color:#cfe7ff;
}
.term .ok{color:var(--green)}
.term .warn{color:#ffd54f}
.term .err{color:var(--red)}
.cursor{
  display:inline-block;
  width:10px;
  height:16px;
  background:rgba(43,255,136,.9);
  vertical-align:middle;
  animation: blink 1s steps(1) infinite;
  margin-left:4px;
}
@keyframes blink{50%{opacity:0}}

.actions{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  margin-top:14px;
}
.btn{
  appearance:none;
  border:1px solid rgba(43,255,136,.45);
  background: rgba(0,0,0,.18);
  color:var(--green);
  padding:10px 14px;
  border-radius:12px;
  text-decoration:none;
  font-size:13px;
  cursor:pointer;
  transition:.15s ease;
}
.btn:hover{
  background: rgba(43,255,136,.18);
  transform: translateY(-1px);
}
.btn.secondary{
  border-color: rgba(68,217,255,.35);
  color: var(--cyan);
}
.footer{
  padding:12px 16px;
  border-top:1px solid rgba(255,255,255,.06);
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:10px;
  color: rgba(232,241,255,.6);
  font-size:12px;
}
.pill{
  padding:4px 10px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.10);
  background:rgba(0,0,0,.14);
  white-space:nowrap;
}
</style>
</head>
<body class="scanlines noise">
  <div class="wrap">
    <div class="card" role="main" aria-label="404 page">
      <div class="topbar">
        <div class="dots" aria-hidden="true">
          <div class="dot red"></div>
          <div class="dot cyan"></div>
          <div class="dot green"></div>
        </div>
        <div class="badge">NTCS / iDAS • ROUTER</div>
      </div>

      <div class="body">
        <!-- Left -->
        <div>
          <div class="glitch" data-text="404">404</div>
          <p class="subtitle">
            Controller / Method not found.<br/>
            The router could not resolve the requested route.
          </p>

          <div class="kv">
            <div class="row"><div class="k">Requested</div><div class="v"><?= $url ?></div></div>
            <div class="row"><div class="k">Time</div><div class="v"><?= htmlspecialchars($serverTime, ENT_QUOTES, 'UTF-8') ?></div></div>
            <div class="row"><div class="k">Server IP</div><div class="v"><?= $serverIp ?></div></div>
            <div class="row"><div class="k">User-Agent</div><div class="v"><?= $ua ?></div></div>
          </div>

          <div class="actions">
            <a class="btn" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Dashboard</a>
            <button class="btn secondary" type="button" onclick="history.back()">Go Back</button>
            <button class="btn secondary" type="button" onclick="copyRoute()">Copy Route</button>
          </div>
        </div>

        <!-- Right -->
        <div class="terminal">
          <div class="termTitle">
            <div>user@idas:~</div>
            <div class="right" id="countdown">router halted</div>
          </div>
          <div class="term" id="term">
            <div><span class="ok">[OK]</span> core.php loaded</div>
            <div><span class="ok">[OK]</span> login middleware checked</div>
            <div><span class="warn">[WARN]</span> route not resolved: <span style="color:#fff"><?= $url ?></span></div>
            <div><span class="err">[ERR]</span> controller/method missing → 404</div>
            <div><span class="ok">[HINT]</span> try: <span style="color:#fff">?url=Dashboards</span></div>
            <div> </div>
            <div>$ <span id="cmd"></span><span class="cursor"></span></div>
          </div>
        </div>
      </div>

      <div class="footer">
        <div class="pill">HTTP/1.1 404 Not Found</div>
        <div class="pill">Build: <?= htmlspecialchars(date('YmdHis'), ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    </div>
  </div>

<script>
(function typeCmd(){
  const text = "route --inspect <?= addslashes($url) ?>";
  const el = document.getElementById('cmd');
  let i = 0;
  const t = setInterval(() => {
    el.textContent = text.slice(0, i++);
    if (i > text.length) clearInterval(t);
  }, 28);
})();

function copyRoute(){
  const route = "<?= addslashes($url) ?>";
  const msg = "Copied: " + route;
  try{
    navigator.clipboard.writeText(route).then(() => {
      if (window.alertify?.success) IdasNotify.success(msg);
      else IdasNotify.alert(msg);
    });
  }catch(e){
    IdasNotify.alert(msg);
  }
}

</script>
</body>
</html>
