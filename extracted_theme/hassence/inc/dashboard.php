<?php
/** "Hassence Orders": one-window order handling + live new-order alerts in the WordPress panel. */
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
 add_menu_page('Hassence Orders', 'Hassence Orders', 'manage_woocommerce', 'hs-orders', 'hs_orders_page', 'dashicons-clipboard', 3);
});
add_action('admin_bar_menu', function ($bar) {
 if (!current_user_can('manage_woocommerce')) return;
 $n = wc_orders_count('on-hold') + wc_orders_count('processing');
 $bar->add_node(['id' => 'hs_orders', 'title' => '🔔 Orders <span id="hs-badge" style="background:#e0245e;color:#fff;border-radius:10px;padding:0 7px;margin-left:4px">' . (int) $n . '</span>', 'href' => admin_url('admin.php?page=hs-orders')]);
}, 90);
function hs_wa_digits($p) { $d = preg_replace('/\D/', '', (string) $p); if (strlen($d) === 11 && $d[0] === '0') $d = '92' . substr($d, 1); return $d; }
function hs_proof_url($f) {
 if (!preg_match('/^[A-Za-z0-9]{20}\.(jpg|png|webp)$/', (string) $f)) return '';
 $u = wp_upload_dir();
 return $u['baseurl'] . '/hs-payments/' . $f;
}
function hs_st_label($s) {
 $m = ['pending' => 'Pending', 'on-hold' => 'Check payment', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded', 'failed' => 'Failed'];
 return isset($m[$s]) ? $m[$s] : ucfirst($s);
}

add_action('wp_ajax_hs_ord_status', function () {
 check_ajax_referer('hs_ord', 'nonce');
 if (!current_user_can('manage_woocommerce')) wp_send_json_error('Not allowed.');
 $o = wc_get_order((int) $_POST['id']);
 $to = isset($_POST['to']) ? sanitize_key($_POST['to']) : '';
 if (!$o || !in_array($to, ['processing', 'completed', 'cancelled'], true)) wp_send_json_error('Bad request.');
 $o->update_status($to, 'Changed from the Hassence Orders dashboard.', true);
 wp_send_json_success();
});
add_action('wp_ajax_hs_ord_poll', function () {
 check_ajax_referer('hs_ord', 'nonce');
 if (!current_user_can('manage_woocommerce')) wp_send_json_error();
 $l = wc_get_orders(['limit' => 1, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order']);
 $o = $l ? $l[0] : null;
 wp_send_json_success(['last' => $o ? $o->get_id() : 0, 'name' => $o ? trim($o->get_billing_first_name() . ' ' . $o->get_billing_last_name()) : '', 'total' => $o ? wp_strip_all_tags(wc_price($o->get_total())) : '', 'open' => wc_orders_count('on-hold') + wc_orders_count('processing')]);
});
add_action('wp_ajax_hs_test_mail', function () {
 check_ajax_referer('hs_ord', 'nonce');
 if (!current_user_can('manage_woocommerce')) wp_send_json_error('Not allowed.');
 $ok = wp_mail(HS_MAIL, 'Hassence test email', 'This is a test email from your Hassence website. If you can read this, order emails can be sent.');
 $ok ? wp_send_json_success('Test email sent to ' . HS_MAIL . '. Check the inbox and the spam folder.') : wp_send_json_error('Sending failed. Set up SMTP (see the note on this page).');
});

add_action('admin_footer', function () {
 if (!current_user_can('manage_woocommerce')) return;
 $n = wp_create_nonce('hs_ord'); ?>
<script>
(function(){var N='<?php echo esc_js($n); ?>',key='hs_last_order';
function beep(){try{var c=new (window.AudioContext||window.webkitAudioContext)(),o=c.createOscillator(),g=c.createGain();o.connect(g);g.connect(c.destination);o.frequency.value=880;g.gain.value=.15;o.start();setTimeout(function(){o.stop()},400)}catch(e){}}
function toast(t){var d=document.createElement('div');d.textContent=t;d.style.cssText='position:fixed;right:20px;bottom:30px;background:#0b7a3b;color:#fff;padding:14px 18px;border-radius:8px;z-index:99999;font-weight:600;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,.3)';d.onclick=function(){location.href='admin.php?page=hs-orders'};document.body.appendChild(d);setTimeout(function(){d.remove()},10000)}
function poll(){fetch(ajaxurl+'?action=hs_ord_poll&nonce='+N,{credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){
 if(!j.success)return;var d=j.data,last=+localStorage.getItem(key)||0;
 if(d.last>last){localStorage.setItem(key,d.last);if(last){beep();toast('New order #'+d.last+' - '+d.name+' - '+d.total);if(location.search.indexOf('page=hs-orders')>-1)setTimeout(function(){location.reload()},2500)}}
 var b=document.getElementById('hs-badge');if(b)b.textContent=d.open}).catch(function(){})}
setInterval(poll,25000);poll()})();
</script>
<?php });

function hs_orders_page() {
 if (!current_user_can('manage_woocommerce')) return;
 $orders = wc_get_orders(['limit' => 100, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order']);
 $c = ['on-hold' => wc_orders_count('on-hold'), 'processing' => wc_orders_count('processing'), 'completed' => wc_orders_count('completed'), 'cancelled' => wc_orders_count('cancelled')];
 $ts = strtotime('today'); $today = 0; $tn = 0;
 foreach ($orders as $o) { $d = $o->get_date_created(); if ($d && $d->getTimestamp() >= $ts && in_array($o->get_status(), ['on-hold', 'processing', 'completed'], true)) { $today += (float) $o->get_total(); $tn++; } }
 $nonce = wp_create_nonce('hs_ord');
 $smtp = defined('HS_SMTP_PASS');
 ?>
<div class="hso">
<style>
.hso{max-width:1250px;margin:18px 18px 40px 0;font-size:14px}
.hso h1{font-size:24px;margin:0 0 4px}.hso .sub{color:#646970;margin-bottom:14px}
.hso .mail{display:flex;gap:10px;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:10px 14px;margin-bottom:14px}
.hso .dot{width:10px;height:10px;border-radius:50%;background:<?php echo $smtp ? '#0b7a3b' : '#d97706'; ?>}
.hso .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px}
.hso .card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:14px}.hso .card b{display:block;font-size:26px;line-height:1.1}.hso .card span{color:#646970}
.hso .bar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:10px}
.hso .tab{border:1px solid #c3c4c7;background:#fff;border-radius:20px;padding:6px 14px;cursor:pointer;font-weight:600}.hso .tab.on{background:#111;color:#fff;border-color:#111}
.hso input.s{margin-left:auto;min-width:220px;padding:6px 10px}
.hso .tw{background:#fff;border:1px solid #dcdcde;border-radius:8px;overflow:auto}
.hso table{border-collapse:collapse;width:100%;min-width:980px}.hso th,.hso td{padding:10px 12px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}.hso th{background:#f6f7f7;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#50575e}
.hso .chip{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;background:#eee}
.st-on-hold{background:#fde8c4!important;color:#7a4a00}.st-processing{background:#d6e9ff!important;color:#0b4a8f}.st-completed{background:#d4f2dc!important;color:#0b6b31}.st-cancelled,.st-failed{background:#f6d5d5!important;color:#8f1d1d}
.hso .b{border:0;border-radius:6px;padding:6px 10px;cursor:pointer;font-weight:600;margin:0 4px 4px 0;background:#e9ecef}.hso .go{background:#0b7a3b;color:#fff}.hso .no{background:#fbe3e3;color:#8f1d1d}
.hso .th{width:42px;height:42px;object-fit:cover;border-radius:4px;border:1px solid #ddd}
.hso .dr{position:fixed;top:32px;right:0;bottom:0;width:min(460px,100%);background:#fff;box-shadow:-8px 0 30px rgba(0,0,0,.2);z-index:9999;padding:20px;overflow:auto;transform:translateX(105%);transition:.25s}.hso .dr.on{transform:none}
.hso .dr h2{margin-top:0}.hso .dr table{min-width:0}.hso .dr .x{position:absolute;right:14px;top:10px;border:0;background:none;font-size:26px;cursor:pointer}
.hso .dr img.big{max-width:100%;border:1px solid #ddd;border-radius:6px}
</style>
<h1>Hassence Orders</h1><div class="sub">Every order, payment proof and action in one window. New orders appear here automatically with a sound.</div>
<div class="mail"><span class="dot"></span><span><?php echo $smtp ? 'Email: SMTP is set up. Order emails go out from ' . esc_html(HS_MAIL) . '.' : 'Email: SMTP is not set up yet. Order emails use server mail and may land in spam.'; ?></span><button class="b" id="tm">Send test email</button><span id="tmr"></span></div>
<div class="cards">
<div class="card"><b><?php echo (int) $c['on-hold']; ?></b><span>Check payment</span></div>
<div class="card"><b><?php echo (int) $c['processing']; ?></b><span>To pack and ship</span></div>
<div class="card"><b><?php echo (int) $c['completed']; ?></b><span>Completed</span></div>
<div class="card"><b><?php echo (int) $tn; ?></b><span>Orders today · <?php echo wp_kses_post(wc_price($today)); ?></span></div>
</div>
<div class="bar">
<button class="tab on" data-f="open">Needs action</button><button class="tab" data-f="all">All</button><button class="tab" data-f="on-hold">Check payment</button><button class="tab" data-f="processing">Processing</button><button class="tab" data-f="completed">Completed</button><button class="tab" data-f="cancelled">Cancelled</button>
<input class="s" id="q" type="search" placeholder="Search name, phone, order number">
</div>
<div class="tw"><table><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($orders as $o) :
 $id = $o->get_id(); $st = $o->get_status();
 $name = trim($o->get_billing_first_name() . ' ' . $o->get_billing_last_name());
 $phone = $o->get_billing_phone(); $wa = hs_wa_digits($phone);
 $items = []; foreach ($o->get_items() as $it) $items[] = $it->get_name() . ($it->get_meta('Size') ? ' (Size ' . $it->get_meta('Size') . ')' : '') . ' × ' . $it->get_quantity();
 $tid = $o->get_meta('_hs_ep_tid'); $purl = hs_proof_url($o->get_meta('_hs_ep_proof'));
 $d = $o->get_date_created();
 $hay = strtolower($id . ' ' . $name . ' ' . $phone . ' ' . $o->get_billing_email());
 ?>
<tr data-st="<?php echo esc_attr($st); ?>" data-h="<?php echo esc_attr($hay); ?>">
<td><b>#<?php echo (int) $id; ?></b><br><small><?php echo $d ? esc_html($d->date_i18n('d M, h:i a')) : ''; ?></small></td>
<td><b><?php echo esc_html($name ? $name : 'Guest'); ?></b><br><?php echo esc_html($phone); ?><br><small><?php echo esc_html($o->get_billing_city()); ?></small></td>
<td><?php echo esc_html(implode(', ', $items)); ?></td>
<td><b><?php echo wp_kses_post(wc_price($o->get_total())); ?></b></td>
<td><?php echo esc_html($o->get_payment_method_title()); ?><?php if ($tid) echo '<br><small>TID: ' . esc_html($tid) . '</small>'; ?><?php if ($purl) echo '<br><a href="' . esc_url($purl) . '" target="_blank"><img class="th" src="' . esc_url($purl) . '" alt="Payment proof"></a>'; ?></td>
<td><span class="chip st-<?php echo esc_attr($st); ?>"><?php echo esc_html(hs_st_label($st)); ?></span></td>
<td>
<button class="b dt" data-id="<?php echo (int) $id; ?>">Details</button>
<?php if ($st === 'on-hold') : ?><button class="b go act" data-id="<?php echo (int) $id; ?>" data-to="processing">Confirm payment</button><?php endif; ?>
<?php if ($st === 'processing') : ?><button class="b go act" data-id="<?php echo (int) $id; ?>" data-to="completed">Mark completed</button><?php endif; ?>
<?php if (in_array($st, ['on-hold', 'processing', 'pending'], true)) : ?><button class="b no act" data-id="<?php echo (int) $id; ?>" data-to="cancelled">Cancel</button><?php endif; ?>
<?php if ($wa) : ?><a class="b" style="text-decoration:none;display:inline-block" target="_blank" href="https://wa.me/<?php echo esc_attr($wa); ?>">WhatsApp</a><?php endif; ?>
<template id="t<?php echo (int) $id; ?>">
<h2>Order #<?php echo (int) $id; ?> <span class="chip st-<?php echo esc_attr($st); ?>"><?php echo esc_html(hs_st_label($st)); ?></span></h2>
<p><b><?php echo esc_html($name); ?></b><br><?php echo esc_html($phone); ?><br><?php echo esc_html($o->get_billing_email()); ?></p>
<p><b>Delivery address</b><br><?php echo wp_kses_post($o->get_formatted_billing_address() ? $o->get_formatted_billing_address() : '—'); ?></p>
<table><?php foreach ($o->get_items() as $it) echo '<tr><td>' . esc_html($it->get_name()) . ($it->get_meta('Size') ? ' <b>(Size ' . esc_html($it->get_meta('Size')) . ')</b>' : '') . ' × ' . (int) $it->get_quantity() . '</td><td>' . wp_kses_post(wc_price($it->get_total())) . '</td></tr>'; ?>
<?php foreach ($o->get_fees() as $f) echo '<tr><td>' . esc_html($f->get_name()) . '</td><td>' . wp_kses_post(wc_price($f->get_total())) . '</td></tr>'; ?>
<tr><td><b>Total</b></td><td><b><?php echo wp_kses_post(wc_price($o->get_total())); ?></b></td></tr></table>
<p><b>Payment:</b> <?php echo esc_html($o->get_payment_method_title()); ?><?php if ($tid) echo '<br><b>Transaction ID:</b> ' . esc_html($tid); ?></p>
<?php if ($purl) echo '<p><a href="' . esc_url($purl) . '" target="_blank"><img class="big" src="' . esc_url($purl) . '" alt="Payment proof"></a></p>'; ?>
<?php if ($o->get_customer_note()) echo '<p><b>Customer note:</b> ' . esc_html($o->get_customer_note()) . '</p>'; ?>
<p><a class="button" href="<?php echo esc_url($o->get_edit_order_url()); ?>">Open full WooCommerce order</a></p>
</template>
</td></tr>
<?php endforeach; ?>
<?php if (!$orders) echo '<tr><td colspan="7">No orders yet.</td></tr>'; ?>
</tbody></table></div>
<div class="dr" id="dr"><button class="x" id="drx" aria-label="Close">×</button><div id="drb"></div></div>
<script>
(function(){var N='<?php echo esc_js($nonce); ?>',rows=[].slice.call(document.querySelectorAll('.hso tbody tr[data-st]')),f='open';
function show(){var q=document.getElementById('q').value.toLowerCase();rows.forEach(function(r){var s=r.dataset.st,ok=f==='all'||(f==='open'?(s==='on-hold'||s==='processing'):s===f);if(ok&&q&&r.dataset.h.indexOf(q)<0)ok=false;r.style.display=ok?'':'none'})}
document.querySelectorAll('.hso .tab').forEach(function(t){t.onclick=function(){document.querySelectorAll('.hso .tab').forEach(function(x){x.classList.remove('on')});t.classList.add('on');f=t.dataset.f;show()}});
document.getElementById('q').oninput=show;show();
document.addEventListener('click',function(e){
 var a=e.target.closest('.hso .act');
 if(a){if(a.dataset.to==='cancelled'&&!confirm('Cancel this order?'))return;a.disabled=true;
  var fd=new FormData();fd.append('action','hs_ord_status');fd.append('nonce',N);fd.append('id',a.dataset.id);fd.append('to',a.dataset.to);
  fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){if(j.success)location.reload();else{alert(j.data||'Failed');a.disabled=false}});return}
 var d=e.target.closest('.hso .dt');
 if(d){var t=document.getElementById('t'+d.dataset.id);document.getElementById('drb').innerHTML=t.innerHTML;document.getElementById('dr').classList.add('on');return}
 if(e.target.id==='drx')document.getElementById('dr').classList.remove('on');
});
document.getElementById('tm').onclick=function(){var r=document.getElementById('tmr');r.textContent='Sending...';var fd=new FormData();fd.append('action','hs_test_mail');fd.append('nonce',N);
 fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'}).then(function(x){return x.json()}).then(function(j){r.textContent=j.data||''})};
})();
</script>
</div>
<?php }
