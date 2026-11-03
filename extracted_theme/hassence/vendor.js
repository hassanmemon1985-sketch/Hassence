(function(){
var $=function(i){return document.getElementById(i)};
if(!$('vf_photo')||!window.HSV)return;
var file='';
function show(n){['vs1','vs2','vs3'].forEach(function(id,i){$(id).hidden=(i+1)!==n})}
function post(fd){return fetch(HSV.ajax,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json()})}
$('vf_photo').addEventListener('change',function(){
 var f=this.files[0];if(!f)return;
 show(2);$('vf_ok').disabled=true;$('vf_msg').textContent='';
 $('vf_prev').innerHTML='<p>Improving your photo, please wait...</p>';
 var fd=new FormData();fd.append('action','hs_ai_photo');fd.append('nonce',HSV.nonce);fd.append('photo',f);
 post(fd).then(function(j){
  if(!j.success){$('vf_prev').innerHTML='';$('vf_msg').textContent=j.data||'Could not process the photo.';return}
  file=j.data.file;
  $('vf_prev').innerHTML='<img src="'+j.data.url+'" alt="Preview">';
  $('vf_msg').textContent=j.data.mode==='ai'?'Background cleaned and photo fitted to the store size.':'Photo fitted to the store size and brightened.';
  $('vf_ok').disabled=false;
 }).catch(function(){$('vf_prev').innerHTML='';$('vf_msg').textContent='Upload failed. Check your internet and try again.'});
});
$('vf_ok').onclick=function(){show(3)};
$('vf_retry').onclick=function(){$('vf_photo').value='';show(1)};
$('vf_submit').onclick=function(){
 var b=this;b.disabled=true;$('vf_res').textContent='Saving...';
 var fd=new FormData();fd.append('action','hs_ai_publish');fd.append('nonce',HSV.nonce);fd.append('file',file);
 fd.append('title',$('vf_title').value);fd.append('desc',$('vf_desc').value);fd.append('price',$('vf_price').value);fd.append('cat',$('vf_cat').value);
 post(fd).then(function(j){
  if(j.success){$('vf_res').innerHTML=j.data.msg+' (Product code: '+j.data.sku+') <br><a href="">Add another product</a>'}
  else{$('vf_res').textContent=j.data||'Could not list the product.';b.disabled=false}
 }).catch(function(){$('vf_res').textContent='Failed. Please try again.';b.disabled=false});
};
})();
