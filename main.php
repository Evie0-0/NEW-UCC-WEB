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
  .row.end { align-items: flex-end; }
  button { padding: 7px 14px; font: inherit; cursor: pointer; margin-top: 10px; margin-right: 6px; }
  .row.end button { margin-top: 0; }
  pre { background: #111; color: #e6e6e6; padding: 12px; border-radius: 8px; overflow: auto; max-height: 420px; white-space: pre-wrap; }
  table { border-collapse: collapse; width: 100%; font-size: 13px; }
  td, th { border-bottom: 1px solid #ddd; padding: 4px 8px; text-align: left; vertical-align: top; }
  .ok { color: #0a7d2c; font-weight: 600; } .bad { color: #c0262d; font-weight: 600; } .warn { color: #b26a00; font-weight: 600; }
  small { color: #666; }
  .unit-box { background: #f6f8fa; border: 1px solid #e1e4e8; border-radius: 6px; padding: 8px 12px; margin: 10px 0 4px; }
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

  <div class="unit-box">
    <div class="row end">
      <div>
        <label>Unit (for <code>unit_id</code>)</label>
        <select id="unitSelect"><option value="">Click "Load units" first</option></select>
      </div>
      <div style="flex:0 0 auto">
        <button type="button" id="loadUnitsBtn">Load units</button>
        <button type="button" id="applyUnitBtn">Put unit_id in parameters</button>
      </div>
    </div>
    <small id="unitStatus">Lists the units your API accepts (the ones that exist and are not deleted). Picking one fills in
    <code>unit_id</code> below. If the preset uses <code>unit</code> instead, that line is updated; otherwise a <code>unit_id</code> line is added.</small>
  </div>

  <label>Parameters (one <code>key=value</code> per line, lines starting with # are ignored; <code>csrf_key</code> is added automatically on POST)</label>
  <textarea id="params" spellcheck="false"></textarea>
  <label>Images (optional, POST only). One row per image field, e.g. Units uses <code>logo</code> and <code>dean_image</code>.
    A row with no file chosen is skipped. To clear an image on update, add <code>remove_logo=true</code> in the parameters.</label>
  <div id="fileRows"></div>
  <button type="button" id="addFileBtn">+ Add image field</button>
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

// A date 30 days from today (YYYY-MM-DD), so publish / start dates in the presets are never in the past
const isoDay = (offsetDays) => {
  const d = new Date();
  d.setDate(d.getDate() + offsetDays);
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
};
const FUTURE = isoDay(30);

// ---- presets ---------------------------------------------------------------
// Each preset is [label, method, url, params, images]. std() builds the usual four for one module.
const std = ({ dir, label, save, filter = '', images = ['featured_image'] }) => [
  [`${label} · filter`, 'GET', `api/${dir}/filter.php`, `search=\nfilter=all\nsort=newest\npage=1${filter}`],
  [`${label} · item`,   'GET', `api/${dir}/item.php`, 'id=1'],
  [`${label} · save (create; set id=N to update)`, 'POST', `api/${dir}/save.php`, `id=0\n${save}`, images],
  [`${label} · delete`, 'POST', `api/${dir}/delete.php`, 'id=1'],
];

const PRESETS = [
  ...std({ dir: 'about', label: 'About', save: 'category=9\nname=Test about\ndescription=Test description\nstatus=active' }),
  ...std({ dir: 'calendar', label: 'Calendar', save: `title=Test event\ndescription=Test\nstatus=draft\npublish_date=${FUTURE}\nstart_date=${FUTURE}\nend_date=${FUTURE}` }),
  ...std({ dir: 'campuses', label: 'Campuses', save: 'name=Test campus\naddress=Test street\nemail=test@ucc.edu.ph\ncontacts=(02) 1234 5678\ngoogle_maps_url=https://maps.google.com\nstatus=active' }),
  ...std({ dir: 'contacts', label: 'Contacts', save: 'name=Test office\nemail=test@ucc.edu.ph\nphone_number=(02) 5310 1234\nstatus=active', images: [] }),
  ...std({ dir: 'eservices', label: 'E-Services', save: 'name=Test service\ndescription=Test\nurl=https://example.com\nstatus=active' }),
  ...std({ dir: 'quicklinks', label: 'Quick links', save: 'name=Test link\nurl=https://example.com\norder_number=1\nstatus=active' }),
  ...std({ dir: 'officials', label: 'Executive officials', save: 'name=Test official\nposition=Test position\nbiography=\norder_number=1\nstatus=active' }),
  ...std({ dir: 'regents', label: 'Regents', save: 'name=Test regent\nposition=Member\nbiography=\norder_number=1\nstatus=active\nexpires_at=' }),
  ...std({ dir: 'units', label: 'Units', save: 'name=Test unit\ndean=Dr. Test\nstatus=active', images: ['logo', 'dean_image'] }),
  ['Units · options', 'GET', 'api/units/options.php', ''],
  ...std({ dir: 'unit-info', label: 'Unit info', save: 'unit=1\nmission=Test\nvision=Test\ndescription=Test\nstatus=active', filter: '\nunit=', images: [] }),
  ...std({ dir: 'programs', label: 'Programs', save: 'unit_id=1\nname=Test program\nmission=Test\nvision=Test\nstatus=active', filter: '\nunit_id=', images: [] }),
  ['Programs · options', 'GET', 'api/programs/options.php', ''],
  ['Unit posts · filter', 'GET', 'api/unit-posts/filter.php', 'search=\nfilter=all\nsort=newest\npage=1\nunit_id='],
  ['Unit posts · item', 'GET', 'api/unit-posts/item.php', 'id=1'],
  ['Unit posts · save (create; set id=N to update)', 'POST', 'api/unit-posts/save.php', `id=0\nunit_id=1\ntitle=Test unit post\nexcerpt=Test excerpt\ncontent=\nstatus=draft\npublish_date=${FUTURE}\nexpiration_date=`, ['featured_image']],
  ['Unit posts · delete', 'POST', 'api/unit-posts/delete.php', 'id=1'],
  ['Unit posts · archive', 'POST', 'api/unit-posts/archive.php', 'id=1'],
  ['Program posts · filter', 'GET', 'api/program-posts/filter.php', 'search=\nfilter=all\nsort=newest\npage=1\nprogram_id='],
  ['Program posts · item', 'GET', 'api/program-posts/item.php', 'id=1'],
  ['Program posts · save (create; set id=N to update)', 'POST', 'api/program-posts/save.php', `id=0\nprogram_id=1\ntitle=Test program post\nexcerpt=Test excerpt\ncontent=\nstatus=draft\npublish_date=${FUTURE}\nexpiration_date=`, ['featured_image']],
  ['Program posts · archive', 'POST', 'api/program-posts/archive.php', 'id=1'],
  ['Program posts · unarchive (restores as draft)', 'POST', 'api/program-posts/unarchive.php', 'id=1'],
  ['Users · filter', 'GET', 'api/users/filter.php', 'search=\nfilter=all\nrole=all\nsort=username-asc\npage=1'],
  ['Users · item', 'GET', 'api/users/item.php', 'id=1'],
  ['Users · save (create dean; unit must exist)', 'POST', 'api/users/save.php', 'id=0\nusername=testdean\nemail=testdean@ucc.edu.ph\npassword=Test12345\nrole=dean\nunit=1\nprogram=\nstatus=active'],
  ['Users · delete', 'POST', 'api/users/delete.php', 'id=2'],
  ['Users · change password', 'POST', 'api/users/change-password.php', 'current_password=\nnew_password=\nconfirm_password='],
  ['Users · logout', 'POST', 'api/users/logout.php', ''],
  ['Audit trail · filter', 'GET', 'api/auditsTrails/filter.php', 'search=\nfilter=all\nsort=created-desc\ndate=\npage=1'],
  ['Dashboard · summary', 'GET', 'api/dashboard/summary.php', ''],
];

// ---- parameter helpers -------------------------------------------------------
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

// Replaces the value of `key` if a line for it exists, otherwise adds the line at the end
function setParam(text, key, value) {
  let found = false;
  const lines = text.split('\n').map((raw) => {
    const line = raw.trim();
    if (!line || line.startsWith('#')) return raw;
    const i = line.indexOf('=');
    const k = (i === -1 ? line : line.slice(0, i)).trim();
    if (k !== key) return raw;
    found = true;
    return key + '=' + value;
  });
  if (!found) {
    if (lines.length && lines[lines.length - 1].trim() === '') lines[lines.length - 1] = key + '=' + value;
    else lines.push(key + '=' + value);
  }
  return lines.join('\n');
}

// The unit key a preset already uses: unit_id (new APIs) or unit (older ones)
const UNIT_KEYS = ['unit_id', 'unit'];
function findUnitKey(text) {
  const hit = parseParams(text).find(([k]) => UNIT_KEYS.includes(k));
  return hit ? hit[0] : null;
}

// ---- preset + image rows -------------------------------------------------------
const preset = $('preset');
PRESETS.forEach((p, i) => preset.add(new Option(p[0], i)));
const fileRows = $('fileRows');

function addFileRow(fieldName = '') {
  const row = document.createElement('div');
  row.className = 'row';
  row.style.alignItems = 'flex-end';
  row.innerHTML = '<div><label>Image file</label><input type="file" class="f-file"></div>'
    + '<div><label>Field name</label><input type="text" class="f-name"></div>'
    + '<div style="flex:0 0 90px"><button type="button" class="f-remove">Remove</button></div>';
  row.querySelector('.f-name').value = fieldName;
  row.querySelector('.f-remove').addEventListener('click', () => row.remove());
  fileRows.appendChild(row);
}
$('addFileBtn').addEventListener('click', () => addFileRow(''));

function loadPreset() {
  const [, method, url, params, images] = PRESETS[preset.value];
  $('method').value = method; $('url').value = url; $('params').value = params;
  fileRows.innerHTML = '';
  (images || []).forEach((name) => addFileRow(name));
  applySelectedUnit(false); // keep the chosen unit when switching presets
}
preset.addEventListener('change', loadPreset);

// Every row that has a file chosen: [[fieldName, File], ...]
function collectFiles() {
  const out = [];
  fileRows.querySelectorAll('.row').forEach((row) => {
    const file = row.querySelector('.f-file').files[0];
    const name = row.querySelector('.f-name').value.trim();
    if (file && name) out.push([name, file]);
  });
  return out;
}

// ---- request helper ----------------------------------------------------------
async function request(method, url, pairs, files = []) {
  let res;
  if (method === 'GET') {
    const qs = new URLSearchParams(pairs);
    res = await fetch(url + (qs.toString() ? '?' + qs : ''));
  } else {
    const fd = new FormData();
    fd.append('csrf_key', csrf);
    pairs.forEach(([k, v]) => fd.append(k, v));
    files.forEach(([field, file]) => fd.append(field, file));
    res = await fetch(url, { method: 'POST', body: fd });
  }
  const text = await res.text();
  let json = null;
  try { json = JSON.parse(text); } catch (e) { /* not JSON: PHP error or HTML page */ }
  return { status: res.status, json, text };
}

// ---- unit picker ----------------------------------------------------------------
const unitSelect = $('unitSelect');
const unitStatus = $('unitStatus');

// Puts the selected unit's id into the parameters. With addIfMissing = false it only updates a line
// the preset already has (unit_id or unit); with true it adds `unit_id=<id>` when there is none.
function applySelectedUnit(addIfMissing) {
  const id = unitSelect.value;
  if (!id) {
    if (addIfMissing) unitStatus.textContent = 'Pick a unit first (click "Load units" if the list is empty).';
    return;
  }
  const params = $('params');
  const existing = findUnitKey(params.value);
  if (!existing && !addIfMissing) return;
  params.value = setParam(params.value, existing || 'unit_id', id);
}

async function loadUnits() {
  unitStatus.textContent = 'Loading units...';
  const units = [];
  let page = 1;
  let totalPages = 1;
  try {
    do {
      const r = await request('GET', 'api/units/filter.php', [['search', ''], ['filter', 'all'], ['sort', 'name-asc'], ['page', String(page)]]);
      if (r.status !== 200 || !r.json || !r.json.data || !Array.isArray(r.json.data.units)) {
        unitStatus.textContent = `Could not load units (HTTP ${r.status}): `
          + (r.json ? r.json.message : 'not JSON, check that api/units/filter.php exists and that you are logged in.');
        return;
      }
      units.push(...r.json.data.units);
      totalPages = r.json.data.total_pages;
      page++;
    } while (page <= totalPages && page <= 50);
  } catch (e) {
    unitStatus.textContent = 'Request failed: ' + e.message;
    return;
  }

  const previous = unitSelect.value;
  unitSelect.innerHTML = '';
  unitSelect.add(new Option(units.length ? 'Select a unit' : 'No units found', ''));
  units.forEach((u) => {
    const hidden = u.status && u.status !== 'active' ? ' (hidden)' : '';
    unitSelect.add(new Option(`${u.id} - ${u.name}${hidden}`, u.id));
  });
  if (previous && units.some((u) => String(u.id) === previous)) unitSelect.value = previous;
  unitStatus.textContent = `${units.length} unit(s) loaded. These are the ids the API accepts for unit_id; a deleted unit will not appear.`;
}

$('loadUnitsBtn').addEventListener('click', loadUnits);
$('applyUnitBtn').addEventListener('click', () => applySelectedUnit(true));
unitSelect.addEventListener('change', () => applySelectedUnit(false));

loadPreset();

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
    const r = await request($('method').value, $('url').value, parseParams($('params').value), collectFiles());
    $('out').textContent = `HTTP ${r.status}\n` + (r.json ? JSON.stringify(r.json, null, 2)
      : 'Not JSON (probably a PHP error, a wrong path or a login redirect):\n' + r.text.slice(0, 3000));
  } catch (e) { $('out').textContent = 'Request failed: ' + e.message; }
});

// ---- 3. smoke test ----------------------------------------------------------------
$('smokeBtn').addEventListener('click', async () => {
  const table = $('smoke');
  table.innerHTML = '<tr><th>Endpoint</th><th>HTTP</th><th>Result</th></tr>';
  const targets = PRESETS.filter((p) => p[1] === 'GET' && !p[2].endsWith('item.php'));
  for (const [, , url, params] of targets) {
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
</html>