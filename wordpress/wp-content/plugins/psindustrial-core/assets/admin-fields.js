/* Native Media Library and Gutenberg-compatible metabox controls. */
(() => {
'use strict';
const L=psiMediaLabels;
const edit=attributes=>{try{if(wp.data.select('core/editor')?.getCurrentPostId())wp.data.dispatch('core/editor').editPost(attributes);}catch{}};
const sync=(input,value)=>{const m=input.name.match(/^psi_fields\[([^\]]+)\]$/);if(m)edit({meta:{[m[1]]:value}});};
wp.hooks.addFilter('editor.PostTaxonomyType','psindustrial/brand',Original=>props=>props.slug==='psi_marca'?null:wp.element.createElement(Original,props));
document.querySelectorAll('.psi-media-control').forEach(control=>{
 const input=control.querySelector('.psi-media-value'),list=control.querySelector('.psi-media-list'),kind=control.dataset.kind;
 let value;
 const read=()=>{try{value=JSON.parse(input.value);}catch{value=kind==='single'?0:[];}if(kind!=='single'&&!Array.isArray(value))value=[];};read();
 const persist=()=>{input.value=JSON.stringify(value);input.dispatchEvent(new Event('change',{bubbles:true}));sync(input,value);};
 const choose=(replace=null)=>{
  const frame=wp.media({title:L.choose,button:{text:L.use},library:{type:kind==='pdf'?'application/pdf':'image'},multiple:replace===null&&kind!=='single'});
  frame.on('select',()=>{
   const items=frame.state().get('selection').toJSON();
   if(kind==='single')value=items[0]?.id||0;
   else if(replace!==null&&items[0])value[replace]=kind==='pdf'?{...value[replace],attachment_id:items[0].id}:items[0].id;
   else items.forEach(media=>{if(!value.some(v=>(kind==='pdf'?v.attachment_id:v)===media.id))value.push(kind==='pdf'?{attachment_id:media.id,label:media.title||media.filename,language:''}:media.id);});
   persist();render();
  });frame.open();
 };
 const videoId=text=>{
  try{const u=new URL(text);if(!['http:','https:'].includes(u.protocol))return '';
   let id='';if(u.hostname==='youtu.be')id=u.pathname.replace(/^\/|\/$/g,'');
   else if(['youtube.com','www.youtube.com','m.youtube.com'].includes(u.hostname))id=u.pathname==='/watch'?u.searchParams.get('v'):(u.pathname.match(/^\/(?:shorts|embed)\/([A-Za-z0-9_-]{11})\/?$/)||[])[1];
   return /^[A-Za-z0-9_-]{11}$/.test(id||'')?id:'';
  }catch{return '';}
 };
 const render=()=>{
  list.replaceChildren();
  (kind==='single'?(value?[value]:[]):value).forEach((item,index)=>{
   const row=document.createElement('li');
   const button=(text,action)=>{const b=document.createElement('button');b.type='button';b.className='button';b.textContent=text;b.addEventListener('click',action);row.append(b);return b;};
   const field=(text,type,current,oninput)=>{const label=document.createElement('label'),el=document.createElement('input');label.append(document.createTextNode(text+' '));el.type=type;el.value=current;el.maxLength=type==='url'?500:200;el.addEventListener('input',()=>oninput(el.value,el));label.append(el);row.append(label);};
   const link=(url,text,parent)=>{const a=document.createElement('a');a.href=url;a.textContent=text;a.target='_blank';a.rel='noopener noreferrer';parent.append(a);};
   if(kind==='video'){
    field(L.url,'url',item.video_id?'https://www.youtube.com/watch?v='+item.video_id:'',(text,el)=>{item.video_id=videoId(text);el.setCustomValidity(item.video_id?'':L.url);persist();});
    field(L.title,'text',item.title,text=>{item.title=text;persist();});
    if(item.video_id)link('https://www.youtube.com/watch?v='+encodeURIComponent(item.video_id),L.open,row);
   }else{
    const id=kind==='pdf'?item.attachment_id:item,preview=document.createElement('span');preview.textContent=L.item+' #'+id;row.append(preview);
    const media=wp.media.attachment(id);
    const show=()=>{if(!row.isConnected)return;const m=media.toJSON();preview.replaceChildren();if(m.type==='image'){const img=document.createElement('img');img.src=m.sizes?.thumbnail?.url||m.url;img.alt=m.alt||'';preview.append(img);}link(m.url,(m.filename||m.title||L.item)+' — '+L.open,preview);};
    if(media.get('url'))queueMicrotask(show);else media.fetch().then(show,()=>{});
    if(kind==='pdf'){
     field(L.label,'text',item.label,text=>{item.label=text;persist();});
     const label=document.createElement('label'),select=document.createElement('select');label.textContent=L.language+' ';
     [['',L.unspecified],['es',L.spanish],['en',L.english]].forEach(([key,text])=>{const o=document.createElement('option');o.value=key;o.textContent=text;select.append(o);});
     select.value=item.language||'';select.addEventListener('change',()=>{item.language=select.value;persist();});label.append(select);row.append(label);
    }
    button(L.replace,()=>choose(index));
   }
   if(kind!=='single'){
    button(L.up,()=>{[value[index-1],value[index]]=[value[index],value[index-1]];persist();render();}).disabled=index===0;
    button(L.down,()=>{[value[index+1],value[index]]=[value[index],value[index+1]];persist();render();}).disabled=index===value.length-1;
   }
   button(L.remove,()=>{if(kind==='single')value=0;else value.splice(index,1);persist();render();});list.append(row);
  });
 };
 control.querySelector('.psi-media-select').addEventListener('click',()=>{if(kind==='video'){value.push({provider:'youtube',video_id:'',title:L.video});persist();render();}else choose();});
 control.closest('form')?.addEventListener('reset',()=>setTimeout(()=>{read();render();},0));
 if(control.closest('#addtag'))jQuery(document).on('ajaxSuccess.psiMedia',(_event,xhr,settings)=>{
  if(typeof settings.data==='string'&&/(?:^|&)action=add-tag(?:&|$)/.test(settings.data)&&xhr.responseXML?.querySelector('term')&&!xhr.responseXML.querySelector('wp_error')){
   input.value='0';read();render();
   document.querySelectorAll('#addtag [name^="psi_term_fields["]').forEach(field=>{field.value=field.name.includes('_psi_public_state')?'review':field.name.includes('_psi_order')?'0':'';});
  }
 });
 render();
});
document.querySelectorAll('[name^="psi_fields["]:not(.psi-media-value)').forEach(input=>input.addEventListener('change',()=>sync(input,input.name.includes('_id]')?Number(input.value):input.value)));
document.querySelector('#psi-brand')?.addEventListener('change',e=>edit({psi_marca:Number(e.target.value)?[Number(e.target.value)]:[]}));
document.querySelector('#psi-order')?.addEventListener('change',e=>edit({menu_order:Number(e.target.value)}));
document.querySelector('#psi-related')?.addEventListener('change',e=>edit({meta:{_psi_related_ids:Array.from(e.target.selectedOptions,o=>Number(o.value))}}));
if(document.body.classList.contains('block-editor-page')&&document.getElementById('psi-brand')){
 let done=false,unsubscribe=()=>{};
 const hideBrandPanel=()=>{const store=wp.data.select('core/editor');if(!done&&store?.getCurrentPostId()){done=true;wp.data.dispatch('core/editor').removeEditorPanel('taxonomy-panel-psi_marca');unsubscribe();}};
 unsubscribe=wp.data.subscribe(hideBrandPanel);hideBrandPanel();
}
})();
