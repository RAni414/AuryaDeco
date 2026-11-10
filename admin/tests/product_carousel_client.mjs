import vm from 'node:vm';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const script=readFileSync(new URL('../../assets/js/product-carousel.js',import.meta.url),'utf8');
function setup(count,visible){
 const events={},buttons=[],status={textContent:''};
 const cards=Array.from({length:count},(_,i)=>({offsetLeft:i*120,offsetWidth:100}));
 const arrow=()=>({hidden:true,disabled:false,addEventListener(k,f){this[k]=f;}});
 const prev=arrow(),next=arrow(),dots={children:[],hidden:false,replaceChildren(){this.children=[];},appendChild(n){this.children.push(n);}};
 const state={visible,reduce:false,resize:null};
 const track={id:'test',children:cards,clientWidth:visible*120-20,scrollWidth:count*120-20,scrollLeft:0,classList:{add(){},remove(){}},
  addEventListener(k,f){events[k]=f;},setPointerCapture(){},scrollTo(o){this.lastScroll=o;this.scrollLeft=Math.min(this.scrollWidth-this.clientWidth,Math.max(0,o.left));events.scroll?.();}};
 const root={querySelector(s){return {'.product-track':track,'[data-product-prev]':prev,'[data-product-next]':next,'.product-pagination':dots,'.product-carousel-status':status}[s];}};
 const context={window:{matchMedia(){return {get matches(){return state.reduce;}};}},document:{querySelectorAll(){return[root];},createElement(){const attrs={};return {attrs,setAttribute(k,v){attrs[k]=v;},addEventListener(k,f){this[k]=f;}};}},getComputedStyle(){return {getPropertyValue(){return String(state.visible);}};},ResizeObserver:class{constructor(f){state.resize=f;}observe(){}},setTimeout(){return 1;},clearTimeout(){},Array,Math,Number};
 vm.runInNewContext(script,context);return {events,track,dots,prev,next,state,status};
}
const a=setup(8,3);
assert.equal(a.dots.children.length,3);assert.equal(a.prev.disabled,true);assert.equal(a.next.hidden,false);
a.next.click();assert.equal(a.track.scrollLeft,360);assert.equal(a.track.lastScroll.behavior,'smooth');
a.dots.children[2].click();assert.equal(a.track.scrollLeft,600);assert.equal(a.next.disabled,true);
a.prev.click();assert.equal(a.track.scrollLeft,360);
console.log('PASS arrows, pagination, bounds and smooth transitions');
let prevented=false;a.events.keydown({target:a.track,key:'Home',preventDefault(){prevented=true;}});assert.equal(a.track.scrollLeft,0);assert.ok(prevented);
a.events.keydown({target:a.track,key:'End',preventDefault(){}});assert.equal(a.track.scrollLeft,600);
a.events.keydown({target:{},key:'Home',preventDefault(){throw Error('nested controls intercepted');}});
console.log('PASS keyboard navigation leaves nested controls intact');
a.state.reduce=true;a.prev.click();assert.equal(a.track.lastScroll.behavior,'instant');
a.state.visible=2;a.track.clientWidth=220;a.state.resize();assert.equal(a.dots.children.length,4);
a.state.visible=1;a.track.clientWidth=100;a.state.resize();assert.equal(a.dots.children.length,8);
console.log('PASS tablet/mobile pagination rebuild and reduced motion');
const b=setup(2,3);assert.ok(b.prev.hidden&&b.next.hidden&&b.dots.hidden);
console.log('PASS selections shorter than viewport hide unnecessary controls');
a.events.pointerdown({button:0,clientX:200,clientY:100,pointerId:1,pointerType:'mouse',target:{closest(){return null;}}});
a.events.pointermove({clientX:80,clientY:102,pointerId:1,preventDefault(){}});a.events.pointerup();
let stopped=false;a.events.click({preventDefault(){stopped=true;},stopImmediatePropagation(){}});assert.ok(stopped);
stopped=false;a.events.pointerdown({button:0,clientX:200,clientY:100,pointerId:2,pointerType:'touch',target:{closest(){return null;}}});a.events.pointerup();
a.events.click({preventDefault(){stopped=true;},stopImmediatePropagation(){}});assert.ok(!stopped);
console.log('PASS drag suppresses navigation while ordinary taps remain clickable');
