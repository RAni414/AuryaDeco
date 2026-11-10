/* AuryaDeco : illustrations des produits sans photo et toise de la page d'accueil. */
(function(){
const BANDS = {petites:"Petites tailles",moyennes:"Moyennes tailles",grandes:"Grandes tailles",geantes:"Plantes géantes",tombantes:"Plantes tombantes"};
/* ================= Illustrations SVG ================= */
let GID=0;
function rng(seed){let s=0;for(const c of String(seed))s=(s*31+c.charCodeAt(0))%2147483647;s=s||7;return()=>{s=(s*16807)%2147483647;return s/2147483647}}
const GREENS=["#4E7A4A","#5E8C55","#3D6440","#6C9A5C","#2F5535"];
function qp(a,b,c,t){return (1-t)*(1-t)*a+2*(1-t)*t*b+t*t*c}
function frond(x0,y0,x1,y1,x2,y2,n,len,col,spread=58,w=8){
  let o=`<path d="M${x0} ${y0}Q${x1} ${y1} ${x2} ${y2}" stroke="${col}" stroke-width="1.4" fill="none"/>`;
  for(let i=1;i<=n;i++){const t=i/(n+1);const x=qp(x0,x1,x2,t),y=qp(y0,y1,y2,t);
    const dx=2*(1-t)*(x1-x0)+2*t*(x2-x1),dy=2*(1-t)*(y1-y0)+2*t*(y2-y1);
    const a=Math.atan2(dy,dx)*180/Math.PI;const L=len*(1-t*.55);
    for(const s of[-1,1])o+=`<ellipse cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" rx="${(L/2).toFixed(1)}" ry="${(L/w).toFixed(1)}" transform="rotate(${(a+s*spread).toFixed(1)} ${x.toFixed(1)} ${y.toFixed(1)}) translate(${(L/2).toFixed(1)} 0)" fill="${col}"/>`;}
  return o;
}
function fan(x,y,L,col,n=7,from=-160,to=-20){let o="";for(let k=0;k<n;k++){const a=from+(to-from)*k/(n-1);o+=`<ellipse cx="${x}" cy="${y}" rx="${L/2}" ry="${L/9}" transform="rotate(${a} ${x} ${y}) translate(${L/2} 0)" fill="${col}"/>`}return o}
function leafAt(x,y,a,L,W,col,vein){return `<g transform="translate(${x.toFixed(1)} ${y.toFixed(1)}) rotate(${a.toFixed(1)})"><path d="M0 0C${-W} ${-L*.25} ${-W*.9} ${-L*.8} 0 ${-L}C${W*.9} ${-L*.8} ${W} ${-L*.25} 0 0Z" fill="${col}"/>${vein?`<path d="M0 0L0 ${-L*.92}" stroke="${vein}" stroke-width="1"/>`:""}</g>`}

function drawPot(shape,color,cx,ty,w,h){
  const id="g"+(GID++),l=cx-w/2,r=cx+w/2,b=ty+h;let d,extra="";
  switch(shape){
    case"cyl":d=`M${l} ${ty}H${r}V${b-4}Q${r} ${b} ${r-4} ${b}H${l+4}Q${l} ${b} ${l} ${b-4}Z`;break;
    case"taper":case"nursery":d=`M${l} ${ty}H${r}L${r-w*.15} ${b}H${l+w*.15}Z`;break;
    case"tall":d=`M${l} ${ty}H${r}L${r-w*.12} ${b}H${l+w*.12}Z`;break;
    case"bowl":d=`M${l} ${ty}H${r}Q${r-2} ${b} ${cx} ${b}Q${l+2} ${b} ${l} ${ty}Z`;break;
    case"stone":d=`M${l+4} ${ty}H${r-4}Q${r+6} ${ty+h*.45} ${r-w*.14} ${b}H${l+w*.14}Q${l-6} ${ty+h*.45} ${l+4} ${ty}Z`;
      for(let i=0;i<14;i++){const R=rng(cx+ty+i);extra+=`<circle cx="${(l+w*.15+R()*w*.7).toFixed(1)}" cy="${(ty+8+((i*37)%100)/100*(h-14)).toFixed(1)}" r="${(0.8+R()*1.4).toFixed(1)}" fill="#000" opacity=".12"/>`}break;
    case"flare":d=`M${l} ${ty}H${r}Q${r-w*.22} ${ty+h*.5} ${r-w*.24} ${b}H${l+w*.24}Q${l+w*.22} ${ty+h*.5} ${l} ${ty}Z`;break;
    case"basket":d=`M${l} ${ty}H${r}L${r-w*.1} ${b}H${l+w*.1}Z`;
      for(let y=ty+6;y<b-2;y+=6){const t=(y-ty)/h,ins=w*.1*t;extra+=`<path d="M${l+ins+1} ${y}H${r-ins-1}" stroke="#000" stroke-opacity=".16" stroke-width="1.4"/>`}
      for(let x=l+8;x<r-4;x+=9)extra+=`<path d="M${x} ${ty+3}L${x+(cx-x)*.2} ${b-1}" stroke="#fff" stroke-opacity=".12" stroke-width="1"/>`;break;
  }
  const rim=shape==="bowl"||shape==="basket"?"":`<rect x="${l-2}" y="${ty-3}" width="${w+4}" height="6" rx="2" fill="${color}"/><rect x="${l-2}" y="${ty-3}" width="${w+4}" height="6" rx="2" fill="url(#${id})"/>`;
  return `<defs><linearGradient id="${id}" x1="0" x2="1"><stop offset="0" stop-color="#fff" stop-opacity=".2"/><stop offset=".5" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".25"/></linearGradient></defs>
  <ellipse cx="${cx}" cy="${b+2}" rx="${w*.55}" ry="3.5" fill="#000" opacity=".13"/>
  <path d="${d}" fill="${color}"/><path d="${d}" fill="url(#${id})"/>${extra}${rim}`;
}

function drawPlant(type,cx,by,s,R){
  let o="";const g=()=>GREENS[Math.floor(R()*GREENS.length)];
  if(type==="palm"){
    const n=7;for(let i=0;i<n;i++){const x=cx+(i-(n-1)/2)*s*.035,top=by-s*(.55+R()*.45),lean=(i-(n-1)/2)*s*.03;
      o+=`<path d="M${x} ${by}Q${x} ${(by+top)/2} ${x+lean} ${top}" stroke="#6B5A3E" stroke-width="2" fill="none"/>`;
      const L=s*.2;o+=fan(x+lean,top,L,g(),7,-170,-10);
      if(R()>.3){const mt=by-(by-top)*(.45+R()*.2);o+=fan(x+lean*.55,mt,L*.8,g(),6,R()>.5?-200:-160,R()>.5?-60:20)}}
  }else if(type==="kentia"){
    for(let i=0;i<9;i++){const dir=i%2?1:-1,sp=.2+R()*.35,ht=.75+R()*.3;
      o+=frond(cx,by,cx+dir*s*.12,by-s*ht*1.05,cx+dir*s*sp,by-s*ht*.62,13,s*.2,g(),62,10)}
  }else if(type==="tree"||type==="olive"){
    const ol=type==="olive",tc=ol?"#7C6E5C":"#7A6449",ch=s*(ol?.42:.5);
    const trunks=ol?[-.12,0,.1]:[-.02,.02];
    trunks.forEach((d,i)=>{const tx=cx+d*s*.6;o+=`<path d="M${cx+d*s*.15} ${by}C${tx-6} ${by-ch*.4} ${tx+8} ${by-ch*.7} ${tx} ${by-ch}" stroke="${tc}" stroke-width="${ol?5:3}" fill="none" stroke-linecap="round"/>`});
    const cols=ol?["#8C9B74","#A1AD86","#76875F","#B4BD9B"]:GREENS;
    const cy=by-s*.68,rx=s*.32,ry=s*.3;
    for(let i=0;i<(ol?120:150);i++){const a=R()*Math.PI*2,rr=Math.sqrt(R());const x=cx+Math.cos(a)*rx*rr,y=cy+Math.sin(a)*ry*rr;
      o+=`<ellipse cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" rx="${ol?s*.032:s*.026}" ry="${ol?s*.009:s*.014}" transform="rotate(${(R()*180).toFixed(0)} ${x.toFixed(1)} ${y.toFixed(1)})" fill="${cols[Math.floor(R()*cols.length)]}"/>`}
  }else if(type==="monstera"){
    const n=7;for(let i=0;i<n;i++){const a=-58+i*(116/(n-1))+(R()-.5)*8,len=s*(.42+R()*.3);const rad=(a-90)*Math.PI/180;
      const x=cx+Math.cos(rad)*len*.85,y=by+Math.sin(rad)*len*.85;
      o+=`<path d="M${cx} ${by}Q${cx+(x-cx)*.2} ${y+(by-y)*.4} ${x} ${y}" stroke="#4B7140" stroke-width="2" fill="none"/>`;
      o+=leafAt(x,y,a*.6+(a>0?25:-25),s*.32,s*.2,g(),"rgba(255,255,255,.25)")}
  }else if(type==="snake"){
    for(let i=0;i<9;i++){const x=cx+(i-4)*s*.045,h=s*(.6+R()*.4),tx=x+(i-4)*s*.03;
      o+=`<path d="M${x-5} ${by}Q${x-7} ${by-h*.6} ${tx} ${by-h}Q${x+7} ${by-h*.6} ${x+5} ${by}Z" fill="${["#3E6640","#2F5535","#4E7A4A"][i%3]}" stroke="#C9B85A" stroke-width="1.5"/>
      <path d="M${x} ${by-4}Q${x} ${by-h*.55} ${tx} ${by-h+8}" stroke="#7FA36D" stroke-width="1" fill="none" stroke-dasharray="4 5"/>`}
  }else if(type==="strelitzia"){
    for(let i=0;i<7;i++){const a=(i-3)*13+(R()-.5)*6,len=s*(.55+R()*.4),rad=(a-90)*Math.PI/180;const x=cx+Math.cos(rad)*len,y=by+Math.sin(rad)*len;
      o+=`<path d="M${cx} ${by}L${x} ${y}" stroke="#56803F" stroke-width="2.2"/>`+leafAt(x+Math.cos(rad)*-2,y,a,s*.32,s*.075,g(),"rgba(255,255,255,.3)")}
  }else if(type==="yucca"){
    [[-.07,.55],[.07,.78]].forEach(([d,h])=>{const x=cx+d*s,top=by-s*h;
      o+=`<path d="M${x-4} ${by}L${x-3} ${top}H${x+3}L${x+4} ${by}Z" fill="#8A7457"/>`;
      for(let k=0;k<22;k++){const a=-180+k*(180/21)+(R()-.5)*8;o+=`<ellipse cx="${x}" cy="${top}" rx="${s*.11}" ry="${s*.011}" transform="rotate(${a} ${x} ${top}) translate(${s*.1} 0)" fill="${g()}"/>`}})
  }else if(type==="agave"){
    const cols=["#7C9C93","#6A8C84","#8FAEA3"];
    for(let k=0;k<13;k++){const a=-80+k*(160/12),L=s*(.55+R()*.35)*(1-Math.abs(a)/220);
      o+=`<g transform="translate(${cx} ${by}) rotate(${a})"><path d="M-8 0Q-7 ${-L*.6} 0 ${-L}Q7 ${-L*.6} 8 0Z" fill="${cols[k%3]}"/><path d="M0 -4L0 ${-L*.8}" stroke="#fff" stroke-opacity=".2"/></g>`}
  }else if(type==="bamboo"){
    for(let i=0;i<5;i++){const x=cx+(i-2)*s*.04,top=by-s*(.75+R()*.25),lean=(i-2)*s*.03;
      o+=`<path d="M${x} ${by}L${x+lean} ${top}" stroke="#9AA65A" stroke-width="3"/>`;
      for(let y=by-12;y>top;y-=s*.08){const t=(by-y)/(by-top);o+=`<rect x="${x+lean*t-2.5}" y="${y}" width="5" height="1.6" fill="#6E7A3A"/>`}
      for(let k=0;k<6;k++){const t=.35+R()*.65,px=x+lean*t,py=by-(by-top)*t;for(let j=0;j<4;j++){const a=(R()>.5?-1:1)*(20+R()*60)-90;o+=`<ellipse cx="${px}" cy="${py}" rx="${s*.055}" ry="${s*.012}" transform="rotate(${a+90+(R()>.5?0:180)} ${px} ${py}) translate(${s*.05} 0)" fill="${g()}"/>`}}}
  }
  return o;
}
function drawHanging(type,cx,ty,w,R){
  let o="";const g=()=>GREENS[Math.floor(R()*GREENS.length)];
  if(type==="hanging"){
    for(let i=0;i<7;i++){const x0=cx+(i-3)*w*.13,len=60+R()*80,dx=(i-3)*6+(R()-.5)*14;const x2=x0+dx,y2=ty+len;
      o+=`<path d="M${x0} ${ty}Q${x0+dx*1.6} ${ty+len*.35} ${x2} ${y2}" stroke="#5E8C55" stroke-width="1.3" fill="none"/>`;
      for(let t=.12;t<1;t+=.1){const x=qp(x0,x0+dx*1.6,x2,t),y=qp(ty,ty+len*.35,y2,t);o+=leafAt(x,y,(R()>.5?1:-1)*(60+R()*50)+180,9,6,g())}}
    for(let k=0;k<12;k++)o+=leafAt(cx+(R()-.5)*w*.8,ty+2,(R()-.5)*120,12,8,g());
  }else{
    for(let i=0;i<12;i++){const dir=i%2?1:-1,sp=.3+R()*.5,dy=(R()-.2)*70;
      o+=frond(cx+dir*4,ty,cx+dir*w*sp*.8,ty-30+R()*10,cx+dir*w*(sp+.35),ty+dy,12,13,g(),62,6)}
  }
  return o;
}

function art(p,big){
  GID+=0;const R=rng(p.id);let inner="";
  if(p.kind==="plante"){
    if(p.tomb){const w=74,h=46,ty=72;inner+=`<path d="M${100-w/2+6} ${ty}L100 18L${100+w/2-6} ${ty}" stroke="#8B7B60" stroke-width="1.2" fill="none"/>`;
      inner+=drawPot("cyl","#E4E0D8",100,ty,w,h)+drawHanging(p.type,100,ty+2,w,R);}
    else{const w=52,h=38,ty=240-14-h,small=p.type==="agave"||p.type==="snake";
      inner+=drawPlant(p.type,100,ty+2,small?120:178,R)+drawPot("nursery","#3A3B37",100,ty,w,h);}
  }else if(p.kind==="pot"){
    const dims={cyl:[104,112],bowl:[140,62],tall:[86,150],stone:[112,104],taper:[110,96],basket:[110,98],flare:[110,106]}[p.shape];
    const ty=226-dims[1];inner+=drawPot(p.shape,p.color,100,ty,dims[0],dims[1]);
    inner+=`<ellipse cx="100" cy="${ty+1}" rx="${dims[0]*.44}" ry="3" fill="#000" opacity=".35"/>`;
  }else if(p.type==="pebbles"){
    inner+=drawPot("bowl","#D7D2C8",100,170,150,52);
    for(let i=0;i<46;i++){const x=40+R()*120,y=150+R()*24-Math.abs(x-100)*.12;inner+=`<ellipse cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" rx="${(7+R()*6).toFixed(1)}" ry="${(5+R()*3).toFixed(1)}" fill="${["#F4F2EE","#E6E3DC","#FFFFFF","#D9D5CD"][i%4]}" stroke="#000" stroke-opacity=".08"/>`}
  }else{
    inner+=drawPot("bowl","#BFA982",100,170,150,52);
    for(let i=0;i<60;i++){const x=36+R()*128,y=152+R()*22-Math.abs(x-100)*.1;inner+=`<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="${(6+R()*7).toFixed(1)}" fill="${["#6E9A4E","#5E8C45","#82AA5C","#4E7A3A"][i%4]}"/>`}
  }
  return `<svg viewBox="0 0 200 240" role="img" aria-label="${p.name}" preserveAspectRatio="xMidYMid meet">${inner}</svg>`;
}

/* ================= Hero : la toise ================= */
function rulerSVG(links){links=links||{};
  const ground=388,k=1.38,xs=[{x:140,type:"agave",cm:60,band:"petites",lbl:"Petites"},{x:240,type:"monstera",cm:120,band:"moyennes",lbl:"Moyennes"},{x:350,type:"palm",cm:190,band:"grandes",lbl:"Grandes"},{x:460,type:"bamboo",cm:245,band:"geantes",lbl:"Géantes"}];
  let o=`<line x1="44" y1="${ground}" x2="44" y2="${ground-250*k}" stroke="var(--muted)" stroke-width="1"/>`;
  for(let c=0;c<=250;c+=10){const y=ground-c*k,big=c%50===0;o+=`<line x1="${big?32:38}" y1="${y}" x2="44" y2="${y}" stroke="var(--muted)" stroke-width="1"/>`;if(big&&c)o+=`<text x="28" y="${y+4}" text-anchor="end" font-size="11" fill="var(--muted)" font-family="Figtree,sans-serif">${c}</text>`}
  o+=`<text x="28" y="${ground+4}" text-anchor="end" font-size="11" fill="var(--muted)" font-family="Figtree,sans-serif">0</text>`;
  o+=`<line x1="44" y1="${ground}" x2="510" y2="${ground}" stroke="var(--line)" stroke-width="2"/>`;
  xs.forEach((p,i)=>{const R=rng(p.type+"hero"),ph=p.cm*k,potH=30,ty=ground-potH,top=ground-ph;
    const plant=drawPlant(p.type,p.x,ty+2,(ph-potH)*(p.type==="agave"?1.5:1),R)+drawPot("nursery","#3A3B37",p.x,ty,40,potH);
    o+=`<a class="pick" href="${links[p.band]||"#"}" aria-label="Voir les ${BANDS[p.band].toLowerCase()}">
      <line x1="46" y1="${top}" x2="${p.x}" y2="${top}" stroke="var(--brass)" stroke-dasharray="3 4" stroke-width="1"/>
      <text x="52" y="${top-5}" font-size="12" fill="var(--brass)" font-family="Figtree,sans-serif" font-weight="600">${p.cm} cm</text>
      <g transform="translate(${p.x} 0) scale(.62 1) translate(${-p.x} 0)">${plant}</g>
      <g class="tag"><rect x="${p.x-42}" y="${ground+10}" width="84" height="26" rx="13" fill="var(--bg)" stroke="var(--line)"/><text x="${p.x}" y="${ground+27}" text-anchor="middle" font-size="12.5" font-family="Figtree,sans-serif" font-weight="600" fill="var(--ink)">${p.lbl}</text></g>
    </a>`});
  return `<svg class="ruler" viewBox="0 0 520 430" role="group" aria-label="Choisir une plante selon sa hauteur">${o}</svg>`;
}


function livingScene(links){
  const plants=[{x:115,s:190,type:'monstera',pot:'#c5b39b',w:72,h:68},{x:290,s:335,type:'strelitzia',pot:'#ded7c9',w:88,h:94},{x:455,s:260,type:'palm',pot:'#76816a',w:76,h:78}];
  const greenery=plants.map((p,i)=>{
    const base=449-p.h;
    return `<ellipse cx="${p.x}" cy="456" rx="${p.w*.72}" ry="11" fill="#273e2d" opacity=".12"/><g class="living-leaves living-leaves-${i}" style="transform-origin:${p.x}px ${base}px">${drawPlant(p.type,p.x,base+5,p.s,rng('living'+i))}</g>${drawPot('taper',p.pot,p.x,base,p.w,p.h)}`;
  }).join('');
  return `<div class="living-scene"><svg viewBox="0 0 560 520" role="img" aria-label="Plantes décoratives animées dans un intérieur lumineux"><defs><linearGradient id="living-wall" x2="1" y2="1"><stop stop-color="#ede9df"/><stop offset="1" stop-color="#dce1d3"/></linearGradient><linearGradient id="living-light"><stop stop-color="#fff" stop-opacity=".65"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient></defs><rect width="560" height="520" fill="url(#living-wall)"/><path d="M0 0H240L490 440H180Z" fill="url(#living-light)"/><g opacity=".35" fill="#fff"><rect x="22" y="25" width="150" height="260" rx="75"/><path d="M96 25h3v260h-3z" fill="#d7d8cc"/></g><path d="M0 439H560V520H0Z" fill="#d3c7b4"/><path d="M0 439H560" stroke="#b4aa98" stroke-opacity=".4"/>${greenery}</svg><div class="living-heading"><span>LA NATURE CHEZ VOUS</span><strong>Un intérieur qui respire.</strong></div><a class="living-link" href="${links.grandes||'#'}">Découvrir les plantes <span aria-hidden="true">↗</span></a><button class="living-toggle" type="button" aria-label="Mettre l’animation en pause" aria-pressed="false">Ⅱ</button></div>`;
}

function renderAll(root){
  (root||document).querySelectorAll('[data-art]').forEach(function(el){
    if(el.dataset.done)return;
    try{var p=JSON.parse(el.dataset.art);if(p.kind==='acc'&&!p.type)p.type='pebbles';el.innerHTML=art(p);el.dataset.done='1';}catch(e){}
  });
  (root||document).querySelectorAll('[data-ruler]').forEach(function(el){
    if(el.dataset.done)return;
    try{
      el.innerHTML=livingScene(JSON.parse(el.dataset.ruler||'{}'));
      const box=el.closest('.ruler-box');
      if(box){box.classList.add('living-box');const cap=box.querySelector('.cap');if(cap)cap.hidden=true;}
      const toggle=el.querySelector('.living-toggle');
      toggle.addEventListener('click',function(){const paused=el.classList.toggle('living-paused');toggle.setAttribute('aria-pressed',String(paused));toggle.setAttribute('aria-label',paused?'Reprendre l’animation':'Mettre l’animation en pause');toggle.textContent=paused?'▶':'Ⅱ';});
      el.dataset.done='1';
    }catch(e){}
  });
}
window.AuryaArt={art:art,render:renderAll};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){renderAll()});else renderAll();
})();
