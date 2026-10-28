<?php
require_once __DIR__ . '/config.php';

$port = strtoupper(trim(ARDUINO_PORT));
$output = [];
$exitCode = 0;

$command = 'mode ' . $port .
           ' BAUD=' . (int) ARDUINO_BAUD .
           ' PARITY=N DATA=8 STOP=1 XON=OFF';

exec($command . ' 2>&1', $output, $exitCode);

// Windows device path: \\.\COM3
$device = '\\\\.\\' . $port;

$handle = @fopen($device, 'r+b');
$opened = ($handle !== false);

if ($opened) {
    fclose($handle);
}

$ok = $opened && $exitCode === 0;
$state = loadState();
recordEvent($state, 'DIAG', $ok ? "Diagnostic passed on $port" : "Diagnostic FAILED on $port", $ok);
saveState($state);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ALC-1 // COM Diagnostic</title>
<style>
:root{
  --bg:#d7d4cc; --panel:#efede7; --panel2:#e4e1d9;
  --ink:#14130f; --dim:#6b675d; --rule:#14130f;
  --green:#1f7a41; --red:#a9271d;
  --mono:ui-monospace,"Cascadia Mono","Segoe UI Mono","Roboto Mono",Consolas,monospace;
}
*{box-sizing:border-box}
body{
  margin:0;background:var(--bg);color:var(--ink);font-family:var(--mono);
  font-size:14px;line-height:1.45;padding:22px;
  background-image:linear-gradient(rgba(20,19,15,.045) 1px,transparent 1px);
  background-size:100% 4px;
}
.shell{max-width:1000px;margin:0 auto}
.head{
  background:var(--ink);color:#f4f2ec;padding:14px 18px;border:2px solid var(--ink);
  display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;
}
.head h1{margin:0;font-size:15px;letter-spacing:.22em}
.head .model{color:#9b978c;font-size:11px;letter-spacing:.18em;margin-top:4px}
.panel{
  background:var(--panel);border:2px solid var(--rule);margin-top:16px;
  box-shadow:5px 5px 0 rgba(20,19,15,.16);
}
.panel > h2{
  margin:0;padding:9px 14px;font-size:11px;letter-spacing:.2em;font-weight:700;
  background:var(--panel2);border-bottom:2px solid var(--rule);
  display:flex;justify-content:space-between;
}
.panel > h2 span{color:var(--dim);font-weight:400}
.body{padding:20px}
.verdict{
  display:flex;align-items:center;gap:14px;padding:16px;border:2px solid var(--rule);
  font-size:20px;font-weight:700;letter-spacing:.06em;margin-bottom:18px;
}
.verdict.ok{background:#dcefe2;color:var(--green)}
.verdict.fail{background:#f6ddd9;color:var(--red)}
.verdict i{width:14px;height:14px;border-radius:50%;background:currentColor;flex:none;box-shadow:0 0 10px currentColor}
pre{
  margin:0;background:#14130f;color:#d7d4cc;padding:18px;border:2px solid var(--rule);
  font-size:12.5px;line-height:1.6;white-space:pre-wrap;word-break:break-word;
}
pre b{color:#2fd06a}
pre u{color:#e0453a;text-decoration:none}
.kv{display:flex;justify-content:space-between;font-size:12px;padding:6px 0;border-bottom:1px dotted #b9b5ab}
.kv b{font-weight:700}
.strip{
  margin-top:16px;border:2px solid var(--rule);background:var(--panel);padding:12px 16px;
  display:flex;flex-wrap:wrap;gap:10px 24px;justify-content:space-between;font-size:12px;
  box-shadow:5px 5px 0 rgba(20,19,15,.16);
}
.strip a{color:var(--ink);text-underline-offset:3px}
.strip .note{color:var(--dim);font-size:11px}
</style>
</head>
<body>
<div class="shell">

  <header class="head">
    <div>
      <h1>COM PORT DIAGNOSTIC</h1>
      <div class="model">MODEL ALC-1 // SERIAL LINK TEST</div>
    </div>
    <div class="model" style="align-self:center">PORT <?php echo htmlspecialchars($port); ?> &middot; <?php echo (int) ARDUINO_BAUD; ?> 8N1</div>
  </header>

  <section class="panel">
    <h2>01 — VERDICT <span>MODE + FOPEN</span></h2>
    <div class="body">
      <div class="verdict <?php echo $ok ? 'ok' : 'fail'; ?>">
        <i></i>
        <?php echo $ok ? 'PORT RESPONSIVE' : 'PORT UNAVAILABLE'; ?>
      </div>

      <div class="kv"><span>CONFIGURED PORT</span><b><?php echo htmlspecialchars($port); ?></b></div>
      <div class="kv"><span>MODE EXIT CODE</span><b><?php echo (int) $exitCode; ?></b></div>
      <div class="kv"><span>DEVICE PATH</span><b><?php echo htmlspecialchars($device); ?></b></div>
      <div class="kv"><span>PHP FOPEN</span><b><?php echo $opened ? 'SUCCESS' : 'FAILED'; ?></b></div>

      <pre><?php
echo "MODE OUTPUT\n";
echo "----------------------------------------\n";
echo $output ? htmlspecialchars(implode("\n", $output)) : "(no output)";
echo "\n\n";
echo $ok
    ? "<b>PASS</b> PHP opened $port. The serial link is usable.\n"
    : "<u>FAIL</u> PHP could not open $port.\n     Close the Arduino Serial Monitor and any other\n     program using the port, then re-run this test.";
?></pre>
    </div>
  </section>

  <div class="strip">
    <span><a href="index.php">&larr; BACK TO CONTROL UNIT</a></span>
    <span class="note">A failed fopen almost always means the Serial Monitor holds the port.</span>
  </div>

</div>
</body>
</html>
