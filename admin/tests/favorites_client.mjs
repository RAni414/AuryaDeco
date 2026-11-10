// Execute the actual browser script with controlled DOM/storage/network adapters.
import vm from 'node:vm';
import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
const script=readFileSync(new URL('../../assets/js/favorites.js',import.meta.url),'utf8');
const KEY='auryadeco:favorites:v1';
const flush=async()=>{for(let i=0;i<12;i++)await new Promise(resolve=>setImmediate(resolve));};
function environment(raw=null,blocked=false,valid=[1,2]){
  const values=new Map(raw===null?[]:[[KEY,raw]]),listeners={},events={},buttons=[];
  const attributes=new Map();
  const link={setAttribute(k,v){attributes.set(k,v);}};
  const count={textContent:'0',hidden:true,closest(){return link;}};
  const notice={textContent:'',hidden:true};
  const state={fail:false,calls:0};
  function button(id){const attrs=new Map();const b={dataset:{favoriteId:String(id)},hidden:true,disabled:false,setAttribute(k,v){attrs.set(k,v);},getAttribute(k){return attrs.get(k);},closest(){return b;}};buttons.push(b);return b;}
  button(1);button(2);
  const document={body:{},activeElement:null,
    querySelector(selector){return selector==='[data-favorites-script]'?{dataset:{endpoint:'/test',csrf:'test-token'}}:selector==='[data-favorites-notice]'?notice:null;},
    querySelectorAll(selector){return selector==='[data-favorite-id]'?buttons:selector==='[data-favorites-count]'?[count]:[];},
    addEventListener(name,fn){listeners[name]=fn;}};
  const localStorage={getItem(key){if(blocked)throw new Error('Blocked');return values.get(key)??null;},setItem(key,value){if(blocked)throw new Error('Blocked');values.set(key,value);}};
  const window={localStorage,addEventListener(name,fn){events[name]=fn;}};
  const context=vm.createContext({document,window,console,Set,Map,Promise,Array,Number,JSON,Error,
    setTimeout(){return 1;},clearTimeout(){},MutationObserver:class{observe(){}},
    async fetch(url,options){state.calls++;const input=JSON.parse(options.body);return{ok:!state.fail,async json(){return state.fail?{error:'Erreur réseau de test'}:{ids:input.ids.filter(id=>valid.includes(id)),html:'',removed:0};}};}});
  vm.runInContext(script,context);
  return{values,buttons,count,notice,state,events,button,async click(b){let prevented=false,stopped=false;listeners.click({target:b,preventDefault(){prevented=true;},stopPropagation(){stopped=true;}});await flush();assert(prevented&&stopped);}};
}
let env=environment();await flush();
assert(env.buttons.every(b=>!b.hidden));
await env.click(env.buttons[0]);
assert.equal(env.buttons[0].getAttribute('aria-pressed'),'true');assert.equal(env.count.textContent,1);
assert.equal(env.values.get(KEY),'[1]');console.log('PASS add, accessibility, confirmation and IDs-only storage');
await env.click(env.buttons[0]);assert.equal(env.values.get(KEY),'[]');assert.equal(env.count.hidden,true);
assert.equal(env.buttons[0].getAttribute('aria-label'),'Ajouter aux favoris');console.log('PASS remove and zero counter');
await env.click(env.buttons[0]);await env.click(env.buttons[1]);
assert.equal(env.values.get(KEY),'[1,2]');
let reloaded=environment(env.values.get(KEY));await flush();assert(reloaded.buttons.every(b=>b.getAttribute('aria-pressed')==='true'));
console.log('PASS reload/navigation restores both hearts');
reloaded.values.set(KEY,'[]');reloaded.events.storage({key:KEY});await flush();assert.equal(reloaded.count.hidden,true);
reloaded.values.set(KEY,'[2]');reloaded.events.pageshow({persisted:true});await flush();assert.equal(reloaded.buttons[1].getAttribute('aria-pressed'),'true');
console.log('PASS cross-tab and back/forward synchronization');
env=environment('{broken');await flush();assert.equal(env.values.get(KEY),'[]');console.log('PASS corrupt JSON repaired');
env=environment('[1,1,"2",-1,4294967295]');await flush();assert.equal(env.values.get(KEY),'[1]');console.log('PASS invalid, duplicated and deleted IDs removed');
env=environment(null,true);await flush();await env.click(env.buttons[0]);assert.equal(env.buttons[0].getAttribute('aria-pressed'),'true');
assert(env.notice.textContent.includes('temporaire'));assert.equal(env.values.size,0);
reloaded=environment(null,true);await flush();assert.equal(reloaded.count.hidden,true);console.log('PASS unavailable storage uses temporary state and warns');
env=environment('[1]');await flush();env.state.fail=true;await env.click(env.buttons[0]);
assert.equal(env.values.get(KEY),'[1]');assert.equal(env.buttons[0].disabled,false);assert(env.notice.textContent.includes('Erreur'));
console.log('PASS server error preserves existing favorites and re-enables heart');
env=environment();await flush();await env.click(env.button(999));assert.equal(env.values.get(KEY),undefined);assert.equal(env.count.hidden,true);
console.log('PASS nonexistent product is never persisted');
const many=Array.from({length:200},(_,i)=>i+1);env=environment(JSON.stringify(many),false,many.concat(201));await flush();
const calls=env.state.calls;await env.click(env.button(201));assert.equal(env.state.calls,calls);assert(env.notice.textContent.includes('200'));
console.log('PASS bounded list refuses an extra product');
