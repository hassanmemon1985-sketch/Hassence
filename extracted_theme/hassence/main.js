(function(){
var $=function(i){return document.getElementById(i)};
var tr=$('track');
if(tr){var n=tr.children.length,k=0,tm,dots=$('dots');
for(var i=0;i<n;i++){var d=document.createElement('i');d.dataset.i=i;dots.appendChild(d)}
var go=function(i){k=(i+n)%n;tr.style.transform='translateX(-'+k*100+'%)';[].forEach.call(dots.children,function(d,j){d.classList.toggle('on',j===k)})};
var auto=function(){clearInterval(tm);tm=setInterval(function(){go(k+1)},4500)};
$('prev').onclick=function(){go(k-1);auto()};$('next').onclick=function(){go(k+1);auto()};
dots.onclick=function(e){if(e.target.dataset.i){go(+e.target.dataset.i);auto()}};go(0);auto()}
document.addEventListener('click',function(e){var t=e.target.closest('.tab');if(!t)return;
document.querySelectorAll('.tab').forEach(function(b){b.classList.toggle('on',b===t)});
var s=t.dataset.t;document.querySelectorAll('#grid .p').forEach(function(p){var ok=s==='all'||(' '+p.dataset.cat+' ').indexOf(' '+s+' ')>-1;p.style.display=ok?'':'none'})});
function tick(){var e=new Date();e.setHours(24,0,0,0);var s=Math.max(0,Math.floor((e-new Date())/1000)),p=function(n){return String(n).padStart(2,'0')};
[['h',Math.floor(s/3600)],['m',Math.floor(s%3600/60)],['s',s%60]].forEach(function(a){var el=$(a[0]);if(el)el.textContent=p(a[1])})}
tick();setInterval(tick,1000);
})();

(function(){
document.addEventListener('change',function(e){
 var i=e.target;if(!i.matches||!i.matches('#hs_ep_file'))return;
 var box=i.closest('.hs-ep'),st=box.querySelector('.hs-ep-st'),hid=box.querySelector('#hs_ep_proof_id'),f=i.files[0];
 if(!f)return;st.textContent='Uploading...';
 var fd=new FormData();fd.append('action','hs_ep_upload');fd.append('nonce',box.dataset.nonce);fd.append('file',f);
 fetch(box.dataset.ajax,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){
  if(j.success){hid.value=j.data.id;window.hsEpId=j.data.id;st.textContent='Uploaded \u2713'}else{hid.value='';window.hsEpId='';st.textContent=j.data||'Upload failed'}
 }).catch(function(){st.textContent='Upload failed'});
});
if(window.jQuery)jQuery(document.body).on('updated_checkout',function(){
 var h=document.getElementById('hs_ep_proof_id');
 if(h&&window.hsEpId){h.value=window.hsEpId;var s=document.querySelector('.hs-ep-st');if(s)s.textContent='Uploaded \u2713'}
});
})();

(function(){
document.addEventListener('click',function(e){
 var dd=document.querySelector('.catdd');if(!dd)return;
 var b=e.target.closest('.catbtn');
 if(b){var o=dd.classList.toggle('open');b.setAttribute('aria-expanded',o)}
 else if(!e.target.closest('.catmenu')){dd.classList.remove('open')}
});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){var dd=document.querySelector('.catdd');if(dd)dd.classList.remove('open')}});
})();

(function(){
document.addEventListener('change',function(e){
 if(e.target.id!=='sortSel')return;
 var g=document.getElementById('grid');if(!g)return;
 var fn={az:function(a,b){return a.dataset.name.localeCompare(b.dataset.name)},za:function(a,b){return b.dataset.name.localeCompare(a.dataset.name)},low:function(a,b){return a.dataset.price-b.dataset.price},high:function(a,b){return b.dataset.price-a.dataset.price},pop:function(a,b){return b.dataset.pop-a.dataset.pop},new:function(a,b){return b.dataset.date-a.dataset.date}}[e.target.value];
 if(fn)[].slice.call(g.querySelectorAll('.p')).sort(fn).forEach(function(p){g.appendChild(p)});
});
document.addEventListener('click',function(e){
 var b=e.target.closest('.tabarr');if(!b)return;
 var t=document.getElementById('tabs');if(t)t.scrollBy({left:b.classList.contains('l')?-300:300,behavior:'smooth'});
});
})();

(function(){
document.addEventListener('click',function(e){
 var b=e.target.closest('.share-btn');
 if(b){var d=b.closest('.share');
  if(navigator.share){navigator.share({title:d.dataset.title,text:d.dataset.title,url:d.dataset.url}).catch(function(){})}
  else{d.classList.toggle('open')}
  return}
 var c=e.target.closest('.share-copy');
 if(c){var u=c.closest('.share').dataset.url;
  if(navigator.clipboard){navigator.clipboard.writeText(u).then(function(){c.textContent='Copied \u2713'})}else{window.prompt('Copy this link',u)}
  return}
 if(e.target.closest('.woocommerce-review-link')){var r=document.getElementById('reviews-acc');if(r)r.open=true}
 if(!e.target.closest('.share')){document.querySelectorAll('.share.open').forEach(function(x){x.classList.remove('open')})}
});
})();

(function(){
function init(){var f=document.querySelector('form.cart .single_add_to_cart_button');if(f&&!document.querySelector('.pdp-plus'))document.body.classList.add('no-plus')}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
document.addEventListener('click',function(e){
 var p=e.target.closest('.pdp-plus');if(!p)return;
 e.preventDefault();e.stopPropagation();
 var b=document.querySelector('form.cart .single_add_to_cart_button');if(b)b.click();
},true);
})();

(function(){
document.addEventListener('click',function(e){
 var m=document.getElementById('qrm');if(!m)return;
 if(e.target.closest('.qr-open')){m.hidden=false;return}
 if(e.target.closest('.qr-x')||e.target===m)m.hidden=true;
});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){var m=document.getElementById('qrm');if(m)m.hidden=true}});
})();
