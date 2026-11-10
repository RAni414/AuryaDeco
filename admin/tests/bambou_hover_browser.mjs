import {browser,delay} from './browser-harness.mjs';
import {fileURLToPath} from 'node:url';
let app;
function check(ok,label){if(!ok)throw new Error(label);console.log('PASS '+label);}
try {
 app=await browser();const page=await app.page();
 await page.call('Emulation.setDeviceMetricsOverride',{width:1440,height:1000,deviceScaleFactor:1,mobile:false});
 for(const path of ['/boutique.php?q=Bambou','/']){
  await page.navigate('http://127.0.0.1:8000'+path);
  await page.until(`document.querySelector('[data-product-id="10"] .has-hover-photo')`);
  await page.evaluate(`document.querySelector('[data-product-id="10"]').scrollIntoView({block:'center'})`);
  await page.until(`Array.from(document.querySelectorAll('[data-product-id="10"] .card-photo')).every(i=>i.complete&&i.naturalWidth>0)`);
  check(await page.evaluate(`(()=>{const p=document.querySelectorAll('[data-product-id="10"] .card-photo');return p.length===2&&p[0].currentSrc!==p[1].currentSrc&&p[0].currentSrc.includes('fond-blanc')})()`),path+': two distinct real photos load');
  await page.call('Input.dispatchMouseEvent',{type:'mouseMoved',x:0,y:0});await delay(400);
  check(await page.evaluate(`getComputedStyle(document.querySelector('[data-product-id="10"] .card-photo')).opacity==='1'`),path+': primary visible');
  const box=await page.evaluate(`(()=>{const r=document.querySelector('[data-product-id="10"] .card-gallery').getBoundingClientRect();return{x:r.x+20,y:r.y+20,h:r.height}})()`);
  await page.call('Input.dispatchMouseEvent',{type:'mouseMoved',x:box.x,y:box.y});await delay(450);
  check(await page.evaluate(`(()=>{const p=document.querySelectorAll('[data-product-id="10"] .card-photo');return getComputedStyle(p[0]).opacity==='0'&&getComputedStyle(p[1]).opacity==='1'&&getComputedStyle(p[1]).objectFit==='contain'&&getComputedStyle(p[1]).transitionDuration==='0.3s'})()`),path+': real hover fades to salon photo');
  if(path==='/boutique.php?q=Bambou')await page.screenshot(fileURLToPath(new URL('../verification-bambou-survol.png',import.meta.url)));
  await page.call('Input.dispatchMouseEvent',{type:'mouseMoved',x:0,y:0});await delay(450);
  check(await page.evaluate(`getComputedStyle(document.querySelector('[data-product-id="10"] .card-photo')).opacity==='1'`),path+': primary restored on exit');
  check(await page.evaluate(`!!document.querySelector('[data-product-id="10"] [data-favorite-id="10"]')&&!!document.querySelector('[data-product-id="10"] [data-card-cart]')`),path+': cart and favorite controls retained');
 }
}finally{if(app)await app.close();}
