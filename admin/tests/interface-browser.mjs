// Standalone local QA using the installed Chrome and Node 22+. No dependencies.
import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const base = new URL(process.argv[2] || 'http://127.0.0.1:8081/');
if (!['127.0.0.1', 'localhost'].includes(base.hostname)) throw new Error('Ce test est reserve au site local.');
const chrome = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
if (!existsSync(chrome)) throw new Error('Chrome introuvable : adaptez le chemin dans interface-browser.mjs.');
const output = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../verification');
mkdirSync(output, { recursive: true });
const profile = mkdtempSync(path.join(tmpdir(), 'aurya-browser-check-'));
const processChrome = spawn(chrome, ['--headless=new', '--remote-debugging-port=0', '--no-first-run', '--no-default-browser-check', '--force-device-scale-factor=1', `--user-data-dir=${profile}`, 'about:blank'], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
const report = [];
let socket;
try {
  const address = await new Promise((resolve, reject) => {
    let logs = '';
    const timeout = setTimeout(() => reject(new Error('Chrome ne demarre pas.')), 15000);
    processChrome.on('error', error => { clearTimeout(timeout); reject(error); });
    processChrome.on('exit', code => { clearTimeout(timeout); reject(new Error(`Chrome arrete (${code}).`)); });
    processChrome.stderr.on('data', chunk => { logs += chunk.toString(); const match = logs.match(/DevTools listening on (ws:\/\/[^\s]+)/); if (match) { clearTimeout(timeout); resolve(match[1]); } });
  });
  socket = new WebSocket(address);
  await new Promise((resolve, reject) => { socket.addEventListener('open', resolve, { once: true }); socket.addEventListener('error', reject, { once: true }); });
  let next = 0; const pending = new Map();
  socket.addEventListener('message', event => {
    const data = JSON.parse(event.data); const item = pending.get(data.id);
    if (!item) return;
    pending.delete(data.id); clearTimeout(item.timeout);
    if (data.error) item.reject(new Error(data.error.message)); else item.resolve(data.result);
  });
  function send(method, params = {}, sessionId) {
    const id = ++next;
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => { pending.delete(id); reject(new Error(`Delai CDP : ${method}`)); }, 15000);
      pending.set(id, { resolve, reject, timeout }); socket.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
    });
  }
  const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
  const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
  const call = (method, params = {}) => send(method, params, sessionId);
  const evaluate = async expression => {
    const value = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (value.exceptionDetails) throw new Error(value.exceptionDetails.text);
    return value.result.value;
  };
  await call('Page.enable'); await call('Runtime.enable');
  async function navigate(url) { await call('Page.navigate', { url }); await wait(1400); }
  for (const [width, height] of [[390,844], [768,1024], [1440,900]]) {
    await call('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });
    await navigate(base.href);
    await evaluate(`Promise.all([...document.images].map(img=>{img.loading='eager'; return img.decode().catch(()=>{});})).then(()=>true)`);
    await evaluate(`document.querySelectorAll('video').forEach(video=>video.pause())`);
    const checks = await evaluate(`(() => {
      const hero=document.querySelector('.home-hero'), button=hero?.querySelector('.home-hero__toggle');
      const h=hero?.getBoundingClientRect(), b=button?.getBoundingClientRect();
      const links=[...document.querySelectorAll('.home-universe')];
      return { noOverflow:document.documentElement.scrollWidth<=innerWidth+1,
        oneAssistant:document.querySelectorAll('.deco-launch').length===1,
        fourCategories:links.length===4, fourPhotos:document.querySelectorAll('.home-universe__photo').length===4,
        categoryLinks:links.every((a,i)=>new URL(a.href).searchParams.get('cat')===['plante','pot','plante','acc'][i]),
        fourDiscover:document.querySelectorAll('.home-universe__cta').length===4,
        loadedImages:[...document.images].every(img=>img.complete&&img.naturalWidth>0),
        videoControlVisible:!!b&&b.width>0&&b.left>=h.left&&b.right<=h.right&&b.bottom<=h.bottom,
        titleFontPx:parseFloat(getComputedStyle(hero.querySelector('h1')).fontSize),heroHeight:h.height };
    })()`);
    if (width < 861) {
      checks.mobileMenu = await evaluate(`(()=>{const b=document.getElementById('menuBtn'); b.click(); const good=b.getAttribute('aria-expanded')==='true'&&getComputedStyle(document.getElementById('mainNav')).display!=='none'; b.click(); return good;})()`);
    }
    const screenshot = await call('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
    writeFileSync(path.join(output, `accueil-${width}.png`), Buffer.from(screenshot.data, 'base64'));
    await evaluate(`document.querySelector('.home-universes')?.scrollIntoView({block:'center'})`); await wait(250);
    const universes = await call('Page.captureScreenshot', { format: 'png' });
    writeFileSync(path.join(output, `univers-${width}.png`), Buffer.from(universes.data, 'base64'));
    await evaluate(`window.scrollTo(0,0)`); await wait(200);
    await evaluate(`document.querySelector('.deco-launch').click()`); await wait(800);
    checks.assistantOpens = await evaluate(`(()=>{const p=document.querySelector('.deco-panel');return !p.hidden&&getComputedStyle(p).visibility==='visible';})()`);
    const chat = await call('Page.captureScreenshot', { format: 'png' });
    writeFileSync(path.join(output, `assistant-${width}.png`), Buffer.from(chat.data, 'base64'));
    await evaluate(`document.querySelector('[data-deco-close]').click()`);
    checks.assistantCloses = await evaluate(`document.querySelector('.deco-panel').hidden`);
    await evaluate(`(()=>{const f=[...document.querySelectorAll('form.search')].find(f=>f.getBoundingClientRect().width>0);f.querySelector('input').value='Kentia';f.requestSubmit();})()`); await wait(1000);
    checks.search = await evaluate(`location.pathname.endsWith('/boutique.php')&&new URL(location.href).searchParams.get('q')==='Kentia'&&document.body.textContent.includes('Kentia')`);
    await navigate(new URL('panier.php', base).href);
    checks.cart = await evaluate(`document.body.textContent.includes('Votre panier')&&getComputedStyle(document.querySelector('.site-tools')).position==='static'`);
    report.push({ width, height, checks });
    console.log(`${width}px : ${Object.entries(checks).filter(([,v])=>v===false).map(([key])=>key).join(', ') || 'OK'}`);
  }
  writeFileSync(path.join(output, 'rapport.json'), JSON.stringify(report, null, 2));
  if (report.some(item => Object.values(item.checks).includes(false))) process.exitCode = 1;
  await send('Browser.close');
} catch (error) {
  writeFileSync(path.join(output, 'rapport.json'), JSON.stringify({ error: error.message, completed: report }, null, 2));
  console.error(error.message); process.exitCode = 1;
} finally {
  if (socket) socket.close();
  if (!processChrome.killed) processChrome.kill();
}
