<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sistem tanılama</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f3f6f4;color:#20332c;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
.wrap{width:min(1180px,100% - 36px);margin:30px auto 48px}
.head{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:22px}
h1{font-size:clamp(24px,3vw,32px);margin:0 0 7px;letter-spacing:-.7px}
.sub,.muted{color:#718078;font-size:13px;line-height:1.55}
.actions{display:flex;gap:9px;flex-wrap:wrap}
.btn{appearance:none;border:1px solid #d8e3dc;background:#fff;color:#176b50;border-radius:10px;padding:10px 14px;font:700 13px inherit;cursor:pointer}
.btn.primary{background:#167c80;border-color:#167c80;color:#fff}
.btn:disabled{opacity:.55;cursor:wait}
.cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin:20px 0}
.card,.panel{background:#fff;border:1px solid #e1e9e4;border-radius:15px;box-shadow:0 5px 20px rgba(26,54,40,.035)}
.card{padding:17px}
.card .label{font-size:12px;color:#748179;font-weight:700}
.card .value{font-size:27px;line-height:1.2;font-weight:850;margin-top:7px;color:#203b31;font-variant-numeric:tabular-nums}
.card .hint{font-size:11px;color:#8a9690;margin-top:4px}
.panel{margin:16px 0;overflow:hidden}
.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid #edf1ee}
.panel-head h2{font-size:17px;margin:0}
.panel-head p{margin:4px 0 0;color:#77847d;font-size:12px}
.pill{display:inline-flex;align-items:center;border-radius:99px;background:#eef6f1;color:#176b50;font-size:11px;font-weight:800;padding:5px 9px;white-space:nowrap}
.table-wrap{width:100%;overflow-x:auto}
table{border-collapse:collapse;width:100%;min-width:760px}
th,td{text-align:left;padding:12px 15px;border-bottom:1px solid #edf1ee;font-size:12px;vertical-align:top}
th{background:#f8faf8;color:#6c7971;font-size:11px;text-transform:uppercase;letter-spacing:.04em}
td{color:#35483e}
td.path{font-weight:750;color:#243b30;overflow-wrap:anywhere;max-width:340px}
.num{font-variant-numeric:tabular-nums;white-space:nowrap;font-weight:750}
.status{display:inline-flex;border-radius:7px;padding:4px 7px;background:#f1f4f2;color:#506158;font-weight:800;font-size:11px}
.status.warn{background:#fff4df;color:#8a5a00}
.status.bad{background:#fdebea;color:#a12d28}
.empty{padding:26px;text-align:center;color:#819087;font-size:13px}
.filter{width:min(310px,100%);padding:9px 11px;border:1px solid #dce6df;border-radius:9px;font:inherit;font-size:12px}
.detail{margin-top:7px}
.detail summary{color:#176b50;cursor:pointer;font-weight:750;font-size:11px}
pre{white-space:pre-wrap;overflow-wrap:anywhere;max-height:240px;overflow:auto;background:#f6f8f6;border:1px solid #e5ece7;border-radius:8px;padding:10px;font-size:11px;color:#46564d}
.note{padding:14px 17px;background:#f1f7f3;border:1px solid #dcebe0;border-radius:12px;color:#52665a;font-size:12px;line-height:1.6}
.loading{padding:24px;text-align:center;color:#77847d}
@media(max-width:760px){.wrap{width:calc(100% - 24px);margin:20px auto 34px}.head{display:block}.actions{margin-top:14px}.cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.card{padding:13px}.card .value{font-size:23px}.panel-head{align-items:flex-start;flex-direction:column;padding:15px}.filter{width:100%}}
@media(max-width:380px){.cards{grid-template-columns:1fr 1fr}.card .value{font-size:21px}}
</style>
</head>
<body>
<main class="wrap">
  <header class="head">
    <div><h1>Sistem tanılama</h1><div class="sub">Yavaş istekleri ve SQL sorgularını okunabilir şekilde inceleyin. Kayıtlar sunucudaki son günlük satırlarından alınır.</div></div>
    <div class="actions"><button class="btn" type="button" id="refresh">Yenile</button><a class="btn" href="<?= e(app_url('/admin')) ?>">Yönetim paneline dön</a></div>
  </header>
  <section class="cards" aria-label="Tanılama özeti">
    <article class="card"><div class="label">Yavaş istek kaydı</div><div class="value" id="requestCount">—</div><div class="hint">Son günlük kayıtları</div></article>
    <article class="card"><div class="label">Yavaş SQL kaydı</div><div class="value" id="sqlCount">—</div><div class="hint">Son günlük kayıtları</div></article>
    <article class="card"><div class="label">En uzun istek</div><div class="value" id="maxRequest">—</div><div class="hint">İstek süresi</div></article>
    <article class="card"><div class="label">En uzun SQL</div><div class="value" id="maxSql">—</div><div class="hint">Sorgu süresi</div></article>
  </section>
  <div class="note" id="diagnosticNote">Tanılama kayıtları yükleniyor…</div>
  <section class="panel">
    <div class="panel-head"><div><h2>Yavaş HTTP istekleri</h2><p>İstek yolu, süre, HTTP durumu, bellek ve son ölçülen aşama</p></div><input class="filter" id="requestFilter" type="search" placeholder="İstek yolu veya aşama ara…"></div>
    <div class="table-wrap" id="requestTable"><div class="loading">Kayıtlar yükleniyor…</div></div>
  </section>
  <section class="panel">
    <div class="panel-head"><div><h2>Yavaş SQL sorguları</h2><p>Veritabanı sorgu performansı kayıtları</p></div><input class="filter" id="sqlFilter" type="search" placeholder="Sorgu veya tablo ara…"></div>
    <div class="table-wrap" id="sqlTable"><div class="loading">Kayıtlar yükleniyor…</div></div>
  </section>
  <div class="sub" id="updatedAt"></div>
</main>
<script>
(() => {
  const endpoint = <?= json_encode(app_url('/admin/performance/diagnostics'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  const $ = id => document.getElementById(id);
  let requests = [], sqlRows = [];
  const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  const parseRows = rows => (Array.isArray(rows) ? rows : []).map(row => {
    if (row && typeof row === 'object') return row;
    if (typeof row !== 'string') return {raw:String(row)};
    try { const parsed = JSON.parse(row); return parsed && typeof parsed === 'object' ? parsed : {raw:row}; }
    catch (_) { return {raw:row}; }
  });
  const ms = value => {
    const n = Number(value);
    if (!Number.isFinite(n)) return '—';
    if (n >= 60000) return (n / 60000).toFixed(1) + ' dk';
    if (n >= 1000) return (n / 1000).toFixed(2) + ' sn';
    return n.toFixed(1) + ' ms';
  };
  const firstNumber = (row, keys) => {
    for (const key of keys) if (row[key] !== undefined && Number.isFinite(Number(row[key]))) return Number(row[key]);
    return 0;
  };
  const duration = row => firstNumber(row,['total_ms','duration_ms','elapsed_ms','query_ms','sql_ms','execution_ms']);
  const details = row => '<details class="detail"><summary>Ham kaydı göster</summary><pre>'+esc(JSON.stringify(row,null,2))+'</pre></details>';
  const table = (rows, kind, query) => {
    const filtered = rows.filter(row => JSON.stringify(row).toLocaleLowerCase('tr').includes(query.toLocaleLowerCase('tr')));
    if (!filtered.length) return '<div class="empty">'+(rows.length ? 'Aramanızla eşleşen kayıt bulunamadı.' : 'Henüz kayıt bulunmuyor.')+'</div>';
    if (kind === 'requests') return '<table><thead><tr><th>İstek</th><th>Süre</th><th>HTTP</th><th>Oturum bekleme</th><th>Bellek</th><th>Son aşama</th></tr></thead><tbody>'+filtered.map(row => {
      const time=duration(row), status=Number(row.http_status||0);
      return '<tr><td class="path">'+esc(row.method||'')+' '+esc(row.path||row.url||'—')+details(row)+'</td><td class="num">'+ms(time)+'</td><td><span class="status '+(status>=500?'bad':status>=400?'warn':'')+'">'+(status||'—')+'</span></td><td class="num">'+ms(row.session_start_ms||0)+'</td><td class="num">'+(row.peak_memory_mb!==undefined?esc(row.peak_memory_mb)+' MB':'—')+'</td><td>'+esc(row.last_stage||row.stage||'—')+'</td></tr>';
    }).join('')+'</tbody></table>';
    return '<table><thead><tr><th>Sorgu / açıklama</th><th>Süre</th><th>Ek bilgiler</th></tr></thead><tbody>'+filtered.map(row => {
      const description=row.query||row.sql||row.statement||row.message||row.raw||row.type||'SQL kaydı';
      const extras=Object.keys(row).filter(key=>!['query','sql','statement','message','raw','type','duration_ms','elapsed_ms','query_ms','sql_ms','execution_ms'].includes(key)).map(key=>key+': '+row[key]).join(' · ');
      return '<tr><td class="path">'+esc(description)+details(row)+'</td><td class="num">'+ms(duration(row))+'</td><td>'+esc(extras||'—')+'</td></tr>';
    }).join('')+'</tbody></table>';
  };
  const render = () => {
    $('requestCount').textContent=requests.length;
    $('sqlCount').textContent=sqlRows.length;
    $('maxRequest').textContent=requests.length?ms(Math.max(...requests.map(duration))):'—';
    $('maxSql').textContent=sqlRows.length?ms(Math.max(...sqlRows.map(duration))):'—';
    $('requestTable').innerHTML=table(requests,'requests',$('requestFilter').value);
    $('sqlTable').innerHTML=table(sqlRows,'sql',$('sqlFilter').value);
  };
  async function load() {
    const button=$('refresh'); button.disabled=true; button.textContent='Yükleniyor…';
    try {
      const response=await fetch(endpoint,{credentials:'same-origin',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},cache:'no-store'});
      if(!response.ok) throw new Error('Sunucu '+response.status+' yanıtı verdi. Yetkinizi ve oturumunuzu kontrol edin.');
      const data=await response.json();
      requests=parseRows(data.performance); sqlRows=parseRows(data.slow_sql);
      $('diagnosticNote').textContent=data.note||'Performans kayıtları başarıyla yüklendi.';
      render();
      $('updatedAt').textContent='Son güncelleme: '+new Date().toLocaleString('tr-TR');
    } catch(error) {
      $('diagnosticNote').textContent='Tanılama verileri alınamadı: '+error.message;
      $('requestTable').innerHTML='<div class="empty">Veriler yüklenemedi.</div>';
      $('sqlTable').innerHTML='<div class="empty">Veriler yüklenemedi.</div>';
    } finally {button.disabled=false;button.textContent='Yenile';}
  }
  $('refresh').addEventListener('click',load);
  $('requestFilter').addEventListener('input',()=>{$('requestTable').innerHTML=table(requests,'requests',$('requestFilter').value);});
  $('sqlFilter').addEventListener('input',()=>{$('sqlTable').innerHTML=table(sqlRows,'sql',$('sqlFilter').value);});
  load();
})();
</script>
</body>
</html>
