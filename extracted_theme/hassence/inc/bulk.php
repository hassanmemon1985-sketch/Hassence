<?php
/** Bulk product upload for WordPress admin and Dokan vendors: pictures only, or CSV, many products at a time. */
if (!defined('ABSPATH')) exit;

function hs_bulk_url() { $p = get_page_by_path('bulk-upload'); return $p ? get_permalink($p) : home_url('/bulk-upload/'); }
add_action('init', function () {
 if (get_option('hs_bulkpage_v1')) return;
 if (!get_page_by_path('bulk-upload')) wp_insert_post(['post_title' => 'Bulk Upload', 'post_name' => 'bulk-upload', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '[hassence_bulk_upload]']);
 update_option('hs_bulkpage_v1', 1);
}, 46);
add_filter('dokan_get_dashboard_nav', function ($u) { $u['hs-bulk'] = ['title' => 'Bulk upload', 'icon' => '<i class="fas fa-upload"></i>', 'url' => hs_bulk_url(), 'pos' => 190]; return $u; });
add_action('admin_menu', function () { add_submenu_page('hs-orders', 'Bulk Upload', 'Bulk Upload', 'edit_products', 'hs-bulk', function () { echo '<div class="wrap"><h1>Bulk Upload</h1>' . hs_bulk_ui() . '</div>'; }); }, 40);
add_shortcode('hassence_bulk_upload', function () {
 if (!is_user_logged_in()) return '<div class="hs-vf"><p>Please log in first.</p><a class="btn-pill" href="' . esc_url(wc_get_page_permalink('myaccount')) . '">Log in</a></div>';
 if (!hs_is_vendor()) return '<div class="hs-vf"><p>Your account is not a seller account yet.</p></div>';
 return hs_bulk_ui();
});

add_action('wp_ajax_hs_bulk_one', function () {
 check_ajax_referer('hs_bulk', 'nonce');
 if (!hs_is_vendor()) wp_send_json_error('Not allowed.');
 $name = sanitize_text_field(wp_unslash(isset($_POST['name']) ? $_POST['name'] : ''));
 $price = (float) wc_format_decimal(wp_unslash(isset($_POST['price']) ? $_POST['price'] : ''));
 $sale = (float) wc_format_decimal(wp_unslash(isset($_POST['sale']) ? $_POST['sale'] : ''));
 $desc = wp_kses_post(wp_unslash(isset($_POST['desc']) ? $_POST['desc'] : ''));
 $live = current_user_can('manage_woocommerce');
 if (strlen($name) < 2) wp_send_json_error('Name needed.');
 if ($price <= 0) wp_send_json_error('Price needed.');
 $cat = (int) (isset($_POST['cat']) ? $_POST['cat'] : 0);
 if (!$cat || !term_exists($cat, 'product_cat')) {
  $cn = sanitize_text_field(wp_unslash(isset($_POST['cat_name']) ? $_POST['cat_name'] : '')); $cat = 0;
  if ($cn !== '') { $t = get_term_by('name', $cn, 'product_cat'); if ($t) $cat = (int) $t->term_id; elseif ($live && function_exists('hs_term_id')) $cat = hs_term_id($cn); }
 }
 $p = new WC_Product_Simple();
 $p->set_name($name); $p->set_description($desc); $p->set_short_description(wp_trim_words(wp_strip_all_tags($desc), 30));
 $p->set_regular_price($price); if ($sale > 0 && $sale < $price) $p->set_sale_price($sale);
 if ($cat) $p->set_category_ids([$cat]);
 $p->set_status($live ? 'publish' : 'pending'); $p->set_catalog_visibility('visible'); $p->set_manage_stock(false); $p->set_stock_status('instock');
 $id = $p->save(); if (!$id) wp_send_json_error('Could not save.');
 if (function_exists('hs_parse_sizes')) { $sz = hs_parse_sizes(wp_unslash(isset($_POST['sizes']) ? $_POST['sizes'] : '')); if ($sz) update_post_meta($id, '_hs_sizes', $sz); }
 require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
 $aid = 0;
 if (!empty($_FILES['file']) && $_FILES['file']['error'] === 0 && @getimagesize($_FILES['file']['tmp_name'])) $aid = media_handle_upload('file', $id);
 elseif (!empty($_POST['image_url'])) $aid = media_sideload_image(esc_url_raw(wp_unslash($_POST['image_url'])), $id, $name, 'id');
 if ($aid && !is_wp_error($aid)) set_post_thumbnail($id, $aid);
 wp_send_json_success(['id' => $id, 'sku' => wc_get_product($id)->get_sku(), 'live' => $live]);
});

function hs_bulk_ui() {
 $cats = []; $t = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'exclude' => [(int) get_option('default_product_cat')]]);
 if (is_array($t)) foreach ($t as $c) $cats[] = [(int) $c->term_id, $c->name];
 $cfg = ['ajax' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('hs_bulk'), 'cats' => $cats];
 ob_start(); ?>
<div class="hsb" id="hsb">
<style>
.hsb{max-width:1150px}.hsb *{box-sizing:border-box}
.hsb-tabs{display:flex;gap:8px;margin:10px 0}.hsb-tabs button{border:1px solid #c3c4c7;background:#fff;border-radius:20px;padding:8px 16px;cursor:pointer;font-weight:600;color:#111}.hsb-tabs button.on{background:#111;color:#fff;border-color:#111}
.hsb-p{background:#f6f7f7;border:1px dashed #b5bcc2;border-radius:8px;padding:14px;margin-bottom:12px;color:#111}
.hsb-tw{overflow:auto;border:1px solid #dcdcde;border-radius:8px;background:#fff;margin:10px 0}
.hsb-t{border-collapse:collapse;width:100%;min-width:900px;color:#111}.hsb-t th,.hsb-t td{padding:8px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}.hsb-t th{background:#f6f7f7;font-size:12px}
.hsb-t input,.hsb-t select,.hsb-t textarea{width:100%;padding:7px;border:1px solid #c3c4c7;border-radius:4px;background:#fff;color:#111;font-size:14px}.hsb-t textarea{height:54px}
.hsb-t img{width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #ddd}
.hsb-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.hsb-prog{flex:1;min-width:140px;height:8px;background:#e3e5e8;border-radius:8px;overflow:hidden}.hsb-prog i{display:block;height:100%;width:0;background:#0b7a3b}
.hsb-go{background:#e0245e;color:#fff;border:0;border-radius:30px;padding:11px 24px;font-weight:800;cursor:pointer}.hsb-go[disabled]{opacity:.45;cursor:not-allowed}
.hsb-x{background:#fff;border:1px solid #c3c4c7;border-radius:30px;padding:10px 18px;cursor:pointer;color:#111}
.hsb .st{font-weight:700;white-space:nowrap}
</style>
<div class="hsb-tabs"><button type="button" class="on" data-m="pic">Pictures and details (no CSV)</button><button type="button" data-m="csv">Upload with a CSV file</button></div>
<div class="hsb-p" data-p="pic"><b>Select all your product pictures at once.</b> A row appears for each picture. Fill in the name, price and category, then press Upload. You can add more than 25 at a time.<br><br><input type="file" id="hsb-img" accept="image/*" multiple></div>
<div class="hsb-p" data-p="csv" style="display:none"><b>1.</b> Choose your CSV file (<a href="<?php echo esc_url(get_template_directory_uri() . '/assets/bulk-products-template.csv'); ?>">download the template</a>). <b>2.</b> Select the pictures it mentions in the image_file column (optional; the file names must match). A row can also use an image_url link instead.<br><br><input type="file" id="hsb-csv" accept=".csv,text/csv"> &nbsp; <input type="file" id="hsb-imgs2" accept="image/*" multiple></div>
<div>Same category for all rows: <select id="hsb-allcat"></select> <button type="button" class="hsb-x" id="hsb-apply">Apply</button></div>
<div class="hsb-tw"><table class="hsb-t"><thead><tr><th>Picture</th><th>Product name</th><th>Price (Rs.)</th><th>Sale price</th><th>Category</th><th>Description</th><th>Sizes</th><th>Status</th><th></th></tr></thead><tbody id="hsb-rows"><tr><td colspan="9">No products yet. Choose pictures or a CSV above.</td></tr></tbody></table></div>
<div class="hsb-bar"><span id="hsb-sum"></span><div class="hsb-prog"><i id="hsb-pb"></i></div><button type="button" class="hsb-go" id="hsb-go" disabled>Upload all products</button><button type="button" class="hsb-x" id="hsb-clr">Clear list</button></div>
<script>
(function(){
var H=<?php echo wp_json_encode($cfg); ?>,rows=[],busy=false,pool={};
function $(i){return document.getElementById(i)}
function esc(s){return String(s==null?'':s).replace(/[&<>"]/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]})}
function opts(sel){return '<option value="">Category</option>'+H.cats.map(function(c){return '<option value="'+c[0]+'"'+(String(sel)===String(c[0])?' selected':'')+'>'+esc(c[1])+'</option>'}).join('')}
$('hsb-allcat').innerHTML=opts('');
function render(){
 var tb=$('hsb-rows');
 if(!rows.length){tb.innerHTML='<tr><td colspan="9">No products yet. Choose pictures or a CSV above.</td></tr>'}
 else tb.innerHTML=rows.map(function(r,i){return '<tr data-i="'+i+'"><td>'+(r.prev?'<img src="'+r.prev+'" alt="">':'')+'</td><td><input data-k="name" value="'+esc(r.name)+'"></td><td><input data-k="price" type="number" min="1" value="'+esc(r.price)+'"></td><td><input data-k="sale" type="number" min="1" value="'+esc(r.sale)+'"></td><td><select data-k="cat">'+opts(r.cat)+'</select></td><td><textarea data-k="desc">'+esc(r.desc)+'</textarea></td><td><input data-k="sizes" placeholder="S,M,L,XL" value="'+esc(r.sizes)+'"></td><td class="st">'+esc(r.st||'')+'</td><td><button type="button" class="hsb-x" data-del="'+i+'">×</button></td></tr>'}).join('');
 $('hsb-go').disabled=!rows.length||busy;$('hsb-sum').textContent=rows.length?rows.length+' products in the list':'';
}
function name(f){return f.name.replace(/\.[^.]+$/,'').replace(/[_-]+/g,' ').trim()}
document.querySelectorAll('.hsb-tabs button').forEach(function(b){b.onclick=function(){document.querySelectorAll('.hsb-tabs button').forEach(function(x){x.classList.toggle('on',x===b)});document.querySelectorAll('.hsb-p').forEach(function(p){p.style.display=p.dataset.p===b.dataset.m?'':'none'})}});
$('hsb-img').onchange=function(){[].forEach.call(this.files,function(f){rows.push({file:f,prev:URL.createObjectURL(f),name:name(f),price:'',sale:'',cat:'',desc:'',sizes:''})});this.value='';render()};
$('hsb-imgs2').onchange=function(){[].forEach.call(this.files,function(f){pool[f.name.toLowerCase()]=f});$('hsb-sum').textContent=Object.keys(pool).length+' pictures ready. Now choose the CSV.'};
function csv(t){var r=[],row=[],c='',q=false;for(var i=0;i<t.length;i++){var ch=t[i];if(q){if(ch=='"'){if(t[i+1]=='"'){c+='"';i++}else q=false}else c+=ch}else if(ch=='"')q=true;else if(ch==','){row.push(c);c=''}else if(ch=='\n'||ch=='\r'){if(ch=='\r'&&t[i+1]=='\n')i++;row.push(c);c='';if(row.length>1||row[0]!=='')r.push(row);row=[]}else c+=ch}row.push(c);if(row.length>1||row[0]!=='')r.push(row);return r}
$('hsb-csv').onchange=function(){var f=this.files[0];if(!f)return;var rd=new FileReader();rd.onload=function(){
 var d=csv(String(rd.result).replace(/^\uFEFF/,''));if(d.length<2)return;var h=d[0].map(function(x){return x.trim().toLowerCase()});
 function g(r,k){var i=h.indexOf(k);return i<0||r[i]==null?'':r[i].trim()}
 d.slice(1).forEach(function(r){var nm=g(r,'name');if(!nm)return;var fl=pool[g(r,'image_file').toLowerCase()]||null,cn=g(r,'category'),cid='';
  H.cats.forEach(function(c){if(cn&&c[1].toLowerCase()===cn.toLowerCase())cid=c[0]});
  rows.push({file:fl,prev:fl?URL.createObjectURL(fl):g(r,'image_url'),url:g(r,'image_url'),name:nm,price:g(r,'price'),sale:g(r,'sale_price'),cat:cid,catName:cn,desc:g(r,'description'),sizes:g(r,'sizes')})});
 render()};rd.readAsText(f);this.value=''};
document.addEventListener('input',function(e){var t=e.target,tr=t.closest&&t.closest('#hsb-rows tr[data-i]');if(tr&&t.dataset.k)rows[+tr.dataset.i][t.dataset.k]=t.value});
document.addEventListener('change',function(e){var t=e.target,tr=t.closest&&t.closest('#hsb-rows tr[data-i]');if(tr&&t.dataset.k==='cat')rows[+tr.dataset.i].cat=t.value});
document.addEventListener('click',function(e){var d=e.target.closest&&e.target.closest('[data-del]');if(d&&!busy){rows.splice(+d.dataset.del,1);render()}});
$('hsb-apply').onclick=function(){var v=$('hsb-allcat').value;rows.forEach(function(r){r.cat=v});render()};
$('hsb-clr').onclick=function(){if(!busy){rows=[];$('hsb-pb').style.width='0';render()}};
function shrink(f){return new Promise(function(res){var im=new Image();im.onload=function(){var s=Math.min(1,1600/Math.max(im.width,im.height)),c=document.createElement('canvas');c.width=Math.round(im.width*s);c.height=Math.round(im.height*s);var x=c.getContext('2d');x.fillStyle='#fff';x.fillRect(0,0,c.width,c.height);x.drawImage(im,0,0,c.width,c.height);c.toBlob(function(b){res(b||f)},'image/jpeg',.86)};im.onerror=function(){res(f)};im.src=URL.createObjectURL(f)})}
$('hsb-go').onclick=async function(){
 if(busy)return;busy=true;$('hsb-go').disabled=true;var ok=0,bad=0,n=rows.length;
 for(var i=0;i<rows.length;i++){var r=rows[i];if(/^✓/.test(r.st||'')){ok++;continue}
  var er=!r.name||r.name.length<2?'Name needed':!(+r.price>0)?'Price needed':'';
  if(er){r.st='✗ '+er;bad++}else{
   r.st='Uploading...';var tr=document.querySelector('#hsb-rows tr[data-i="'+i+'"] .st');if(tr)tr.textContent=r.st;
   try{var fd=new FormData();fd.append('action','hs_bulk_one');fd.append('nonce',H.nonce);fd.append('name',r.name);fd.append('price',r.price);fd.append('sale',r.sale||'');fd.append('cat',r.cat||'');fd.append('cat_name',r.catName||'');fd.append('desc',r.desc||'');fd.append('sizes',r.sizes||'');fd.append('image_url',r.url||'');
    if(r.file){var b=await shrink(r.file);fd.append('file',b,'p'+i+'.jpg')}
    var j=await (await fetch(H.ajax,{method:'POST',body:fd,credentials:'same-origin'})).json();
    if(j.success){r.st='✓ '+(j.data.sku||'saved')+(j.data.live?'':' (pending)');ok++}else{r.st='✗ '+(j.data||'Failed');bad++}
   }catch(x){r.st='✗ Network error';bad++}}
  $('hsb-pb').style.width=Math.round((i+1)/n*100)+'%';
  var c=document.querySelector('#hsb-rows tr[data-i="'+i+'"] .st');if(c)c.textContent=r.st}
 busy=false;$('hsb-go').disabled=false;$('hsb-sum').textContent=ok+' uploaded, '+bad+' need attention. Fix the rows marked ✗ and press Upload again: finished rows are skipped.';
};
render();
})();
</script>
</div>
<?php return ob_get_clean();
}
