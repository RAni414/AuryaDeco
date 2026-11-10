import {browser} from './browser-harness.mjs';
const base='http://127.0.0.1:8000';let app;
try{
 app=await browser();const page=await app.page();
 for(const path of ['/','/boutique.php']){
  await page.navigate(base+path);await page.until(`document.querySelector('[data-card-ready]')`);
  console.log(JSON.stringify(await page.evaluate(`(()=>{const card=document.querySelector('[data-product-id="1"]');const css=Array.from(document.styleSheets).find(s=>s.href&&s.href.includes('product-cards.css'));return{page:location.pathname,product:'Palmier Raphis (1)',cardPresent:!!card,images:card?Array.from(card.querySelectorAll('.card-photo')).map(i=>({src:i.currentSrc,loaded:i.complete&&i.naturalWidth>0})):[],cssLoaded:!!css&&css.cssRules.length>0,cssURL:css?.href,hoverRules:css?Array.from(css.cssRules).filter(r=>r.cssText.includes('has-hover-photo')).map(r=>r.cssText):[]}})()`)));
 }
 console.log('No real product with two photos is available: two-photo visual verification remains blocked.');
}finally{if(app)await app.close();}
