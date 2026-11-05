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

(function(){
document.addEventListener('click',function(e){
 var m=e.target.closest('.buy-mini');
 if(m){e.preventDefault();var b=document.querySelector('form.cart .buy-now');if(b)b.click();return}
 var g=e.target.closest('.get-app[data-soon]');
 if(g){e.preventDefault();alert('The Hassence app is coming soon.')}
});
})();

(function(){
document.addEventListener('click',function(e){
 var b=e.target.closest('.car');if(!b)return;
 var t=b.parentNode.querySelector('.crow-track');
 if(t)t.scrollBy({left:(b.classList.contains('l')?-1:1)*Math.max(260,t.clientWidth*.8),behavior:'smooth'});
});
})();

(function(){
document.addEventListener('submit',function(e){
 var f=e.target;if(!f||!f.matches||!f.matches('form.cart'))return;
 var box=document.querySelector('.pdp-sizes');if(!box)return;
 var sel=[].slice.call(box.querySelectorAll('input:checked'));
 if(!sel.length){e.preventDefault();e.stopImmediatePropagation();box.classList.add('need');box.scrollIntoView({behavior:'smooth',block:'center'});return}
 f.querySelectorAll('input[name="hs_size[]"]').forEach(function(x){x.remove()});
 sel.forEach(function(s){var h=document.createElement('input');h.type='hidden';h.name='hs_size[]';h.value=s.value;f.appendChild(h)});
},true);
document.addEventListener('change',function(e){if(e.target.closest&&e.target.closest('.pdp-sizes'))e.target.closest('.pdp-sizes').classList.remove('need')});
})();

(function(){
if(matchMedia('(prefers-reduced-motion: reduce)').matches)return;
var tr=[].slice.call(document.querySelectorAll('.crow-track'));if(!tr.length)return;
var st=tr.map(function(t,i){return{t:t,dir:1,x:0,v:34+(i%4)*6,pause:0}});
st.forEach(function(s){
 ['touchstart','pointerdown','wheel'].forEach(function(ev){s.t.addEventListener(ev,function(){s.pause=performance.now()+4500},{passive:true})});
 s.t.addEventListener('mouseenter',function(){s.pause=Infinity});
 s.t.addEventListener('mouseleave',function(){s.pause=performance.now()+800});
});
var last=performance.now();
function tick(now){
 var dt=Math.min(60,now-last);last=now;
 st.forEach(function(s){
  if(now<s.pause)return;var t=s.t,max=t.scrollWidth-t.clientWidth;if(max<=4)return;
  var r=t.getBoundingClientRect();if(r.bottom<0||r.top>innerHeight)return;
  if(Math.abs(t.scrollLeft-Math.round(s.x))>2)s.x=t.scrollLeft;
  s.x+=s.dir*s.v*dt/1000;
  if(s.x>=max){s.x=max;s.dir=-1}else if(s.x<=0){s.x=0;s.dir=1}
  t.scrollLeft=s.x;
 });
 requestAnimationFrame(tick);
}
requestAnimationFrame(tick);
})();
