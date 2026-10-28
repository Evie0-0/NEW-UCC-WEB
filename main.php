<?php
declare(strict_types=1);

use Core\Token;

// DEVELOPMENT ONLY. Delete this file before going live.
// Put it where your current main.php is (same folder as bootstrap.php) so "api/..." resolves the same way.
require_once __DIR__ . '/bootstrap.php';

session_start();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Endpoint Tester</title>
<style>
  body { font: 14px/1.4 system-ui, sans-serif; max-width: 980px; margin: 24px auto; padding: 0 16px; color: #222; }
  fieldset { margin: 0 0 16px; border: 1px solid #ccc; border-radius: 8px; padding: 12px 16px; }
  legend { font-weight: 600; padding: 0 6px; }
  label { display: block; margin: 6px 0 2px; font-size: 12px; color: #555; }
  input[type=text], input[type=password], select, textarea { width: 100%; box-sizing: border-box; padding: 6px 8px; font: inherit; }
  textarea { font-family: ui-monospace, Consolas, monospace; min-height: 130px; }
  .row { display: flex; gap: 12px; } .row > * { flex: 1; }
  button { padding: 7px 14px; font: inherit; cursor: pointer; margin-top: 10px; margin-right: 6px; }
  pre { background: #111; color: #e6e6e6; padding: 12px; border-radius: 8px; overflow: auto; max-height: 420px; white-space: pre-wrap; }
  table { border-collapse: collapse; width: 100%; font-size: 13px; }
  td, th { border-bottom: 1px solid #ddd; padding: 4px 8px; text-align: left; vertical-align: top; }
  .ok { color: #0a7d2c; font-weight: 600; } .bad { color: #c0262d; font-weight: 600; } .warn { color: #b26a00; font-weight: 600; }
  small { color: #666; }
</style>
</head>
<body>
<h1>Endpoint Tester <small>(development only)</small></h1>
<input type="hidden" id="csrfKey" value="<?= htmlspecialchars(Token::generate()) ?>">

<fieldset>
  <legend>1. Log in first</legend>
  <div class="row">
    <div><label>Username</label><input type="text" id="username" value="mky"></div>
    <div><label>Password</label><input type="password" id="password" value="admin123"></div>
  </div>
  <button type="button" id="loginBtn">Log in</button>
  <small>Log in as a superadmin, then as a dean / program head to test their restricted endpoints.
  If a POST returns 403 "Invalid CSRF token" after logging in, reload this page to get a fresh token.</small>
</fieldset>

<fieldset>
  <legend>2. Send one request</legend>
  <label>Preset</label>
  <select id="preset"></select>
  <div class="row">
    <div style="flex:0 0 110px"><label>Method</label>
      <select id="method"><option>GET</option><option>POST</option></select></div>
    <div><label>URL</label><input type="text" id="url"></div>
  </div>
  <label>Parameters (one <code>key=value</code> per line, lines starting with # are ignored; <code>csrf_key</code> is added automatically on POST)</label>
  <textarea id="params" spellcheck="false"></textarea>
  <div class="row">
    <div><label>Image file (optional, POST only)</label><input type="file" id="file"></div>
    <div><label>Image field name</label><input type="text" id="fileField" value="featured_image"></div>
  </div>
  <button type="button" id="sendBtn">Send</button>
  <pre id="out">Response will appear here.</pre>
</fieldset>

<fieldset>
  <legend>3. Smoke test (every list / read endpoint)</legend>
  <small>Calls each GET endpoint with default parameters. 200 means the route, permissions, query and database columns all work.
  A 500 almost always means a column or table is missing: run <code>admin-api-schema.sql</code> and check <code>storage/logs/error.log</code>.</small><br>
  <button type="button" id="smokeBtn">Run smoke test</button>
  <table id="smoke"></table>
</fieldset>

<script>
const csrf = document.getElementById('csrfKey').value;
const $ = (id) => document.getElementById(id);
const FUTURE = '2026-11-20';

// ---- presets ---------------------------------------------------------------
const std = (dir, label, save, extraFilter = '') => [
  [`${label} · filter`, 'GET', `api/${dir}/filter.php`, `search=\nfilter=all\nsort=newest\npage=1${extraFilter}`],
  [`${label} · item`,   'GET', `api/${dir}/item.php`, 'id=1'],
  [`${label} · save (create; set id=N to update)`, 'POST', `api/${dir}/save.php`, `id=0\n${save}`],
  [`${label} · delete`, 'POST', `api/${dir}/delete.php`, 'id=1'],
];
const PRESETS = [
  ...std('about', 'About', 'category=9\nname=Test about\ndescription=Test description\nstatus=active'),
  ...std('calendar', 'Calendar', `title=Test event\ndescription=Test\nstatus=draft\npublish_date=${FUTURE}\nstart_date=${FUTURE}\nend_date=${FUTURE}`),
  ...std('campuses', 'Campuses', 'name=Test campus\naddress=Test street\nemail=test@ucc.edu.ph\ncontacts=(02) 1234 5678\ngoogle_maps_url=https://maps.google.com\nstatus=active'),
  ...std('contacts', 'Contacts', 'category=Offices\nname=Test office\nemail=test@ucc.edu.ph\nphone_number=\nlocation=\nstatus=active'),
  ...std('eservices', 'E-Services', 'name=Test service\ndescription=Test\nurl=https://example.com\nstatus=active'),
  ...std('quicklinks', 'Quick links', 'name=Test link\nurl=https://example.com\norder_number=1\nstatus=active'),
  ...std('officials', 'Executive officials', 'name=Test official\nposition=Test position\nbiography=\norder_number=1\nstatus=active'),
  ...std('regents', 'Regents', 'name=Test regent\nposition=Member\nbiography=\norder_number=1\nstatus=active\nexpires_at='),
  ...std('units', 'Units', 'name=Test unit\ndean=Dr. Test\nstatus=active'),
  ['Units · options', 'GET', 'api/units/options.php', ''],
  ...std('unit-info', 'Unit info', 'unit=1\nmission=Test\nvision=Test\ndescription=Test\nstatus=active', '\nunit='),
  ...std('programs', 'Programs', 'unit=1\nname=Test program\nmission=Test\nvision=Test\nstatus=active', '\nunit='),
  ['Programs · options', 'GET', 'api/programs/options.php', ''],
  ['Unit posts · filter', 'GET', 'api/unit-posts/filter.php', 'search=\nfilter=all\nsort=newest\npage=1\nunit='],
  ['Unit posts · item', 'GET', 'api/unit-posts/item.php', 'id=1'],
  ['Unit posts · save (create)', 'POST', 'api/unit-posts/save.php', `id=0\nunit=1\ntitle=Test unit post\nexcerpt=Test excerpt\ncontent=\nstatus=draft\npublish_date=${FUTURE}\nexpiration_date=`],
  ['Unit posts · archive', 'POST', 'api/unit-posts/archive.php', 'id=1'],
  ['Program posts · filter', 'GET', 'api/program-posts/filter.php', 'search=\nfilter=all\nsort=newest\npage=1\nprogram='],
  ['Program posts · item', 'GET', 'api/program-posts/item.php', 'id=1'],
  ['Program posts · save (create)', 'POST', 'api/program-posts/save.php', `id=0\nprogram=1\ntitle=Test program post\nexcerpt=Test excerpt\ncontent=\nstatus=draft\npublish_date=${FUTURE}\nexpiration_date=`],
  ['Program posts · archive', 'POST', 'api/program-posts/archive.php', 'id=1'],
  ['Users · filter', 'GET', 'api/users/filter.php', 'search=\nfilter=all\nrole=all\nsort=username-asc\npage=1'],
  ['Users · item', 'GET', 'api/users/item.php', 'id=1'],
  ['Users · save (create dean; unit must exist)', 'POST', 'api/users/save.php', 'id=0\nusername=testdean\nemail=testdean@ucc.edu.ph\npassword=Test12345\nrole=dean\nunit=1\nprogram=\nstatus=active'],
  ['Users · delete', 'POST', 'api/users/delete.php', 'id=2'],
  ['Users · change password', 'POST', 'api/users/change-password.php', 'current_password=\nnew_password=\nconfirm_password='],
  ['Users · logout', 'POST', 'api/users/logout.php', ''],
  ['Audit trail · filter', 'GET', 'api/auditsTrails/filter.php', 'search=\nfilter=all\nsort=created-desc\ndate=\npage=1'],
  ['Dashboard · summary', 'GET', 'api/dashboard/summary.php', ''],
];

const preset = $('preset');
PRESETS.forEach((p, i) => preset.add(new Option(p[0], i)));
function loadPreset() {
  const [, method, url, params] = PRESETS[preset.value];
  $('method').value = method; $('url').value = url; $('params').value = params;
}
preset.addEventListener('change', loadPreset);
loadPreset();

// ---- request helper ----------------------------------------------------------
function parseParams(text) {
  const pairs = [];
  for (const raw of text.split('\n')) {
    const line = raw.trim();
    if (!line || line.startsWith('#')) continue;
    const i = line.indexOf('=');
    pairs.push(i === -1 ? [line, ''] : [line.slice(0, i).trim(), line.slice(i + 1)]);
  }
  return pairs;
}

async function request(method, url, pairs, file, fileField) {
  let res;
  if (method === 'GET') {
    const qs = new URLSearchParams(pairs);
    res = await fetch(url + (qs.toString() ? '?' + qs : ''));
  } else {
    const fd = new FormData();
    fd.append('csrf_key', csrf);
    pairs.forEach(([k, v]) => fd.append(k, v));
    if (file) fd.append(fileField, file);
    res = await fetch(url, { method: 'POST', body: fd });
  }
  const text = await res.text();
  let json = null;
  try { json = JSON.parse(text); } catch (e) { /* not JSON: PHP error or HTML page */ }
  return { status: res.status, json, text };
}

// ---- 1. login ------------------------------------------------------------------
$('loginBtn').addEventListener('click', async () => {
  $('out').textContent = 'Loading...';
  try {
    const r = await request('POST', 'api/users/login.php',
      [['username', $('username').value], ['password', $('password').value]]);
    $('out').textContent = `HTTP ${r.status}\n` + (r.json ? JSON.stringify(r.json, null, 2) : r.text);
  } catch (e) { $('out').textContent = 'Request failed: ' + e.message; }
});

// ---- 2. single request -----------------------------------------------------------
$('sendBtn').addEventListener('click', async () => {
  $('out').textContent = 'Loading...';
  try {
    const r = await request($('method').value, $('url').value, parseParams($('params').value),
      $('file').files[0], $('fileField').value.trim() || 'featured_image');
    $('out').textContent = `HTTP ${r.status}\n` + (r.json ? JSON.stringify(r.json, null, 2)
      : 'Not JSON (probably a PHP error, a wrong path or a login redirect):\n' + r.text.slice(0, 3000));
  } catch (e) { $('out').textContent = 'Request failed: ' + e.message; }
});

// ---- 3. smoke test ----------------------------------------------------------------
$('smokeBtn').addEventListener('click', async () => {
  const table = $('smoke');
  table.innerHTML = '<tr><th>Endpoint</th><th>HTTP</th><th>Result</th></tr>';
  const targets = PRESETS.filter(p => p[1] === 'GET' && !p[2].endsWith('item.php'));
  for (const [label, , url, params] of targets) {
    const tr = table.insertRow();
    tr.insertCell().textContent = url;
    const code = tr.insertCell(), msg = tr.insertCell();
    code.textContent = '...';
    try {
      const r = await request('GET', url, parseParams(params));
      code.textContent = r.status;
      code.className = r.status === 200 ? 'ok' : (r.status === 401 || r.status === 403 ? 'warn' : 'bad');
      msg.textContent = r.json ? r.json.message
        : 'Not JSON: ' + r.text.replace(/<[^>]+>/g, ' ').trim().slice(0, 200);
      if (r.status === 401) msg.textContent += ' (not logged in, or this role is not allowed here)';
    } catch (e) { code.textContent = 'ERR'; code.className = 'bad'; msg.textContent = e.message; }
  }
});
</script>
</body>
</html><?php
