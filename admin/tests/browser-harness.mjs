import { spawn } from 'node:child_process';
import { existsSync, mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
export const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

export async function browser() {
  const executable = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
  if (!existsSync(executable)) throw new Error('Chrome introuvable.');
  const profile = mkdtempSync(path.join(tmpdir(), 'aurya-favorites-check-'));
  const child = spawn(executable, ['--headless=new', '--no-first-run', '--no-default-browser-check', '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank'], { windowsHide: true, stdio: ['ignore','ignore','pipe'] });
  let ws;
  try {
    const address = await new Promise((resolve, reject) => {
      let text = ''; const timeout = setTimeout(() => reject(new Error('Chrome ne demarre pas.')), 15000);
      child.on('error', e => { clearTimeout(timeout); reject(e); });
      child.on('exit', code => { clearTimeout(timeout); reject(new Error(`Chrome arrete (${code}).`)); });
      child.stderr.on('data', data => { text += data; const match = text.match(/DevTools listening on (ws:\/\/[^\s]+)/); if (match) { clearTimeout(timeout); resolve(match[1]); } });
    });
    ws = new WebSocket(address);
    await new Promise((resolve,reject) => { ws.addEventListener('open',resolve,{once:true}); ws.addEventListener('error',reject,{once:true}); });
    let id = 0; const pending = new Map();
    ws.addEventListener('message', event => { const result=JSON.parse(event.data), task=pending.get(result.id); if (!task) return; pending.delete(result.id); clearTimeout(task.timer); result.error ? task.reject(new Error(result.error.message)) : task.resolve(result.result); });
    function send(method, params={}, sessionId) {
      const token=++id;
      return new Promise((resolve,reject) => { const timer=setTimeout(()=>{pending.delete(token);reject(new Error('Delai CDP '+method));},15000); pending.set(token,{resolve,reject,timer}); ws.send(JSON.stringify({id:token,method,params,...(sessionId?{sessionId}:{})})); });
    }
    async function page() {
      const {targetId}=await send('Target.createTarget',{url:'about:blank'});
      const {sessionId}=await send('Target.attachToTarget',{targetId,flatten:true});
      const call=(method,params={})=>send(method,params,sessionId);
      await call('Page.enable'); await call('Runtime.enable');
      const evaluate=async expression=>{const result=await call('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true}); if(result.exceptionDetails)throw new Error(result.exceptionDetails.text); return result.result.value;};
      return {call,evaluate,
        async navigate(url){await call('Page.navigate',{url});await delay(600);},
        async until(expression){for(let i=0;i<80;i++){if(await evaluate(expression))return;await delay(100);}throw new Error('Condition non satisfaite : '+expression);},
        async screenshot(file){const data=await call('Page.captureScreenshot',{format:'png'});writeFileSync(file,Buffer.from(data.data,'base64'));}};
    }
    return {page, async close(){try{await send('Browser.close');}catch{}ws.close();child.kill();}};
  } catch(error) { if(ws)ws.close();child.kill();throw error; }
}
