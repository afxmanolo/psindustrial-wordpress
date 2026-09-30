'use strict';
// Dependency-free behavioral tests for the actual Home slider script.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const code = fs.readFileSync(require('node:path').join(__dirname, '../assets/js/home.js'), 'utf8');
function environment(reduced = false) {
 const element = () => ({hidden:false, attributes:{}, events:{}, dataset:{pause:'Pause',play:'Play'}, classList:{add(){}},
  setAttribute(k,v){this.attributes[k]=v;}, addEventListener(k,f){this.events[k]=f;}, focus(){this.focused=true;}});
 const slides = Array.from({length:4},element), dots = Array.from({length:4},element), pause = element(), controls = element(), root = element();
 root.querySelectorAll = s => s === '.home-slide' ? slides : dots;
 root.querySelector = s => s === '.home-slider-controls' ? controls : pause;
 root.contains = e => dots.includes(e) || e === pause;
 const media = {matches:reduced,addEventListener(k,f){this.change=f;}};
 const document = {hidden:false,events:{},querySelector:()=>root,addEventListener(k,f){this.events[k]=f;}};
 let timer;
 vm.runInNewContext(code,{document,window:{},matchMedia:()=>media,setTimeout:f=>{timer=f;return 1;},clearTimeout:()=>{timer=null;}});
 return {slides,dots,pause,controls,root,media,document,tick(){const f=timer;timer=null;f?.();},scheduled:()=>!!timer,active:()=>slides.findIndex(s=>!s.hidden)};
}
const checks=[];
const test=(name,f)=>{try{f();checks.push({test:name,passed:true});}catch(e){checks.push({test:name,passed:false,error:e.message});}};
test('First slide visible, others hidden, autoplay scheduled',()=>{const e=environment();assert.equal(e.active(),0);assert.equal(e.slides.filter(s=>!s.hidden).length,1);assert(e.scheduled());});
test('Autoplay advances once and reschedules',()=>{const e=environment();e.tick();assert.equal(e.active(),1);assert(e.scheduled());});
test('Manual indicators select the requested slide',()=>{const e=environment();e.dots[2].events.click();assert.equal(e.active(),2);assert.equal(e.dots[2].attributes['aria-pressed'],'true');});
test('Keyboard wraps and restores focus to active control',()=>{const e=environment();e.root.events.keydown({key:'ArrowLeft',preventDefault(){}});assert.equal(e.active(),3);assert(e.dots[3].focused);});
test('Pause cancels automatic rotation; resume schedules',()=>{const e=environment();e.pause.events.click();assert(!e.scheduled());e.pause.events.click();assert(e.scheduled());});
test('Reduced motion disables automatic rotation but allows manual slides',()=>{const e=environment(true);assert(!e.scheduled());assert(e.pause.hidden);e.dots[1].events.click();assert.equal(e.active(),1);});
test('Changing reduced motion cancels pending timer',()=>{const e=environment();e.media.matches=true;e.media.change();assert(!e.scheduled());});
test('Focus and hover suspend autoplay',()=>{const e=environment();e.root.events.focusin();assert(!e.scheduled());e.root.events.focusout({relatedTarget:null});assert(e.scheduled());e.root.events.pointerenter({pointerType:'mouse'});assert(!e.scheduled());});
test('Hidden document cancels autoplay',()=>{const e=environment();e.document.hidden=true;e.document.events.visibilitychange();assert(!e.scheduled());});
test('Horizontal touch swipe changes slide',()=>{const e=environment();e.root.events.pointerdown({pointerType:'touch',clientX:150,clientY:100});e.root.events.pointerup({clientX:60,clientY:105});assert.equal(e.active(),1);});
test('Vertical touch gesture preserves slide',()=>{const e=environment();e.root.events.pointerdown({pointerType:'touch',clientX:150,clientY:100});e.root.events.pointerup({clientX:140,clientY:220});assert.equal(e.active(),0);});
const failed=checks.filter(c=>!c.passed).length;
console.log(JSON.stringify({passed:checks.length-failed,failed,checks},null,2));
process.exitCode=failed?1:0;
