<?php
require_once __DIR__ . '/config.php';
$state = loadState();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ALC-1 // Light Control Unit</title>
<style>
:root{
  --bg:#d7d4cc; --panel:#efede7; --panel2:#e4e1d9;
  --ink:#14130f; --dim:#6b675d; --rule:#14130f;
  --green:#1f7a41; --red:#a9271d; --amber:#b06f00;
  --mono:ui-monospace,"Cascadia Mono","Segoe UI Mono","Roboto Mono",Consolas,monospace;
}
*{box-sizing:border-box}
html,body{margin:0}
body{
  background:var(--bg); color:var(--ink); font-family:var(--mono);
  font-size:14px; line-height:1.45; padding:22px;
  background-image:linear-gradient(rgba(20,19,15,.045) 1px,transparent 1px);
  background-size:100% 4px;
}
.shell{max-width:1000px;margin:0 auto}

/* ---- header ---- */
.head{
  background:var(--ink); color:#f4f2ec; padding:14px 18px;
  display:flex; flex-wrap:wrap; gap:12px 26px; align-items:baseline;
  justify-content:space-between; border:2px solid var(--ink);
}
.head h1{margin:0;font-size:15px;letter-spacing:.22em;font-weight:700}
.head .model{color:#9b978c;font-size:11px;letter-spacing:.18em}
.meta{display:flex;gap:22px;font-size:11px;letter-spacing:.14em;color:#c9c5ba}
.meta b{color:#f4f2ec;font-weight:700}
.lamp{display:inline-flex;align-items:center;gap:8px}
.dot{
  width:11px;height:11px;border-radius:50%;background:#3a3831;
  border:1px solid #000;box-shadow:inset 0 1px 2px rgba(0,0,0,.6);
}
.dot.on{background:#2fd06a;box-shadow:0 0 8px rgba(47,208,106,.85),inset 0 1px 1px rgba(255,255,255,.5)}
.dot.off{background:#4a473f}
.dot.err{background:#e0453a;box-shadow:0 0 8px rgba(224,69,58,.8)}

/* ---- grid ---- */
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
@media(max-width:760px){.grid{grid-template-columns:1fr}}

.panel{background:var(--panel);border:2px solid var(--rule);box-shadow:5px 5px 0 rgba(20,19,15,.16)}
.panel > h2{
  margin:0;padding:9px 14px;font-size:11px;letter-spacing:.2em;font-weight:700;
  background:var(--panel2);border-bottom:2px solid var(--rule);
  display:flex;justify-content:space-between;
}
.panel > h2 span{color:var(--dim);font-weight:400}
.body{padding:20px}

/* ---- rocker switch ---- */
.switchrow{display:flex;gap:26px;align-items:flex-start;flex-wrap:wrap}
.rocker{
  width:168px;height:224px;padding:0;cursor:pointer;font-family:var(--mono);
  border:2px solid var(--rule);background:var(--panel2);
  display:grid;grid-template-rows:1fr 1fr;flex:none;
}
.rocker span{
  display:flex;align-items:center;justify-content:center;
  font-size:19px;font-weight:700;letter-spacing:.28em;color:var(--dim);
  background:#dcd9d1;transition:all .1s linear;
}
.rocker span:first-child{border-bottom:2px solid var(--rule)}
.rocker .up{box-shadow:inset 0 -6px 0 rgba(20,19,15,.10)}
.rocker .dn{box-shadow:inset 0 6px 0 rgba(20,19,15,.10)}
body[data-light="ON"] .rocker .up{
  background:var(--green);color:#fff;box-shadow:inset 0 4px 8px rgba(0,0,0,.4);
}
body[data-light="OFF"] .rocker .dn{
  background:var(--red);color:#fff;box-shadow:inset 0 -4px 8px rgba(0,0,0,.4);
}
.readout{flex:1;min-width:180px}
.state{
  font-size:44px;font-weight:700;letter-spacing:.06em;line-height:1;
  border-bottom:2px solid var(--rule);padding-bottom:12px;margin-bottom:12px;
}
.state small{display:block;font-size:10px;letter-spacing:.24em;color:var(--dim);font-weight:400;margin-bottom:7px}
.kv{display:flex;justify-content:space-between;font-size:12px;padding:5px 0;border-bottom:1px dotted #b9b5ab}
.kv b{font-weight:700}
.hint{margin-top:16px;font-size:11px;color:var(--dim);letter-spacing:.06em}
kbd{
  font-family:var(--mono);border:1px solid var(--rule);background:#fff;
  padding:1px 6px;font-size:11px;box-shadow:1px 1px 0 var(--rule);
}

/* ---- log ---- */
#log{height:306px;overflow-y:auto;padding:0;margin:0;list-style:none}
#log li{
  display:grid;grid-template-columns:64px 46px 1fr;gap:10px;
  padding:7px 14px;font-size:12px;border-bottom:1px solid #d5d1c8;
}
#log li:nth-child(odd){background:#e9e7e0}
#log .t{color:var(--dim)}
#log .tag{
  font-size:10px;letter-spacing:.1em;text-align:center;
  border:1px solid var(--rule);height:16px;line-height:14px;
}
#log .tag.ok{background:#dcefe2}
#log .tag.fail{background:#f6ddd9;color:var(--red);border-color:var(--red)}
#log .m{word-break:break-word}
#log .empty{display:block;text-align:center;color:var(--dim);padding:40px 0;letter-spacing:.2em;font-size:12px}

/* ---- status strip ---- */
.strip{
  margin-top:16px;border:2px solid var(--rule);background:var(--panel);
  padding:12px 16px;display:flex;flex-wrap:wrap;gap:10px 24px;
  align-items:center;justify-content:space-between;font-size:12px;
  box-shadow:5px 5px 0 rgba(20,19,15,.16);
}
.strip a{color:var(--ink);text-underline-offset:3px}
.strip .note{color:var(--dim);font-size:11px}
#msg{font-weight:700}
#msg.ok{color:var(--green)}
#msg.fail{color:var(--red)}
</style>
</head>
<body data-light="<?php echo htmlspecialchars($state['light']); ?>">
<div class="shell">

  <header class="head">
    <div>
      <h1>LIGHT CONTROL UNIT</h1>
      <div class="model">MODEL ALC-1 // BUTTON + SERIAL</div>
    </div>
    <div class="meta">
      <span>PORT <b><?php echo htmlspecialchars(ARDUINO_PORT); ?></b></span>
      <span>BAUD <b><?php echo (int) ARDUINO_BAUD; ?> 8N1</b></span>
      <span class="lamp"><i class="dot on" id="link"></i>LINK</span>
    </div>
  </header>

  <div class="grid">

    <section class="panel">
      <h2>01 — CONTROL <span>PIN 13 / RELAY PIN 7</span></h2>
      <div class="body">
        <div class="switchrow">
          <button class="rocker" id="rocker" type="button" aria-label="Toggle light">
            <span class="up">ON</span>
            <span class="dn">OFF</span>
          </button>
          <div class="readout">
            <div class="state"><small>LIGHT OUTPUT</small><span id="stateText"><?php echo htmlspecialchars($state['light']); ?></span></div>
            <div class="kv"><span>COMMAND BYTE</span><b id="byteText"><?php echo $state['light'] === 'ON' ? '0x31' : '0x30'; ?></b></div>
            <div class="kv"><span>PHYSICAL BUTTON</span><b>pin 2, ACTIVE LOW</b></div>
            <div class="kv"><span>DEBOUNCE</span><b>50 ms</b></div>
          </div>
        </div>
        <div class="hint">CLICK A SWITCH POSITION &middot; OR PRESS <kbd>1</kbd> <kbd>0</kbd> ON THE KEYBOARD</div>
      </div>
    </section>

    <section class="panel">
      <h2>02 — EVENT LOG <span id="count"><?php echo count($state['events']); ?> ENTRIES</span></h2>
      <ul id="log"></ul>
    </section>

  </div>

  <div class="strip">
    <span id="msg">&nbsp;</span>
    <span><a href="test_com_port.php">RUN COM PORT DIAGNOSTIC &rarr;</a></span>
    <span class="note">Close the Arduino Serial Monitor before sending commands. State resets when the board resets.</span>
  </div>

</div>

<script>
(function () {
  var body = document.body;
  var logEl = document.getElementById('log');
  var msgEl = document.getElementById('msg');
  var countEl = document.getElementById('count');
  var stateText = document.getElementById('stateText');
  var byteText = document.getElementById('byteText');
  var link = document.getElementById('link');
  var events = <?php echo json_encode($state['events']); ?>;
  var busy = false;

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function renderLog() {
    countEl.textContent = events.length + ' ENTRIES';
    if (!events.length) {
      logEl.innerHTML = '<li><span class="empty">NO EVENTS RECORDED</span></li>';
      return;
    }
    logEl.innerHTML = events.map(function (e) {
      return '<li><span class="t">' + esc(e.time) + '</span>' +
             '<span class="tag ' + (e.ok ? 'ok' : 'fail') + '">' + (e.ok ? 'OK' : 'ERR') + '</span>' +
             '<span class="m">' + esc(e.message) + '</span></li>';
    }).join('');
  }

  function renderState(light) {
    body.setAttribute('data-light', light);
    stateText.textContent = light;
    byteText.textContent = light === 'ON' ? '0x31' : '0x30';
  }

  function notify(text, ok) {
    msgEl.textContent = text;
    msgEl.className = ok ? 'ok' : 'fail';
    link.className = ok ? 'on' : 'err';
    setTimeout(function () { link.className = 'on'; }, 2500);
  }

  function send(cmd) {
    if (busy) { return; }
    busy = true;
    fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ command: cmd })
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        busy = false;
        if (data.events) { events = data.events; renderLog(); }
        if (data.light) { renderState(data.light); }
        notify(data.message || 'No response', !!data.success);
      })
      .catch(function (err) {
        busy = false;
        notify('Request failed: ' + err.message, false);
      });
  }

  document.getElementById('rocker').addEventListener('click', function () {
    send(body.getAttribute('data-light') === 'ON' ? 'OFF' : 'ON');
  });

  document.addEventListener('keydown', function (e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') { return; }
    if (e.key === '1') { send('ON'); }
    if (e.key === '0') { send('OFF'); }
  });

  // keep in sync with the physical button on pin 2
  setInterval(function () {
    if (busy || document.hidden) { return; }
    fetch('api.php?action=state')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.light) { renderState(data.light); }
        if (data.events) { events = data.events; renderLog(); }
      })
      .catch(function () { link.className = 'err'; });
  }, 3000);

  renderLog();
})();
</script>
</body>
</html>
