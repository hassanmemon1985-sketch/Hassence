<?php
/** Product importer: CSV -> queue -> automatic daily publishing (default 50/day) with margin rules. */
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
 add_submenu_page('hs-orders', 'Product Importer', 'Product Importer', 'manage_woocommerce', 'hs-import', 'hs_import_page');
}, 20);
function hs_imp_set() { return array_merge(['limit' => 25, 'margin' => 30, 'round' => 10, 'paused' => 0], (array) get_option('hs_imp', [])); }
function hs_imp_log($m) { $l = (array) get_option('hs_imp_log', []); array_unshift($l, date_i18n('d M H:i') . ' - ' . $m); update_option('hs_imp_log', array_slice($l, 0, 40), false); }
function hs_term_id($name) {
 $name = trim($name); if ($name === '') $name = 'Uncategorized';
 $t = term_exists($name, 'product_cat'); if (!$t) $t = wp_insert_term($name, 'product_cat');
 return is_wp_error($t) ? 0 : (int) (is_array($t) ? $t['term_id'] : $t);
}
function hs_auto_seo($name, $program, $mode, $cat) {
 $n = trim($name); $short = mb_strimwidth($n, 0, 44, '', 'UTF-8');
 $title = $short . ' Price in Pakistan | Buy Online - Hassence';
 if ($mode === 'affiliate') $d = 'Looking for ' . $n . ' in Pakistan? See the details and the current price, then buy it on ' . ($program !== '' ? $program : 'the partner site') . ' through Hassence. Price and availability are set by ' . ($program !== '' ? $program : 'the seller') . ' and may change.';
 else $d = 'Buy ' . $n . ' online in Pakistan at Hassence' . ($cat !== '' ? ' - ' . $cat : '') . '. Cash on Delivery available.';
 return [$title, mb_strimwidth($d, 0, 155, '', 'UTF-8')];
}
function hs_make_product($r) {
 $name = trim($r['name']); $cost = (float) preg_replace('/[^0-9.]/', '', $r['merchant_price']);
 if ($name === '' || $cost <= 0) return 'skipped (missing name or price)';
 $cur = strtoupper(trim(isset($r['currency']) ? $r['currency'] : '')); if ($cur !== '' && $cur !== 'PKR') { $rt = hs_fx_rates(); if (isset($rt[$cur])) $cost = $cost * $rt[$cur]; else return 'skipped (unknown currency ' . $cur . ')'; }
 $key = md5(strtolower($r['link'] !== '' ? $r['link'] : $name));
 if (get_posts(['post_type' => 'product', 'meta_key' => '_hs_key', 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1, 'post_status' => 'any'])) return 'skipped (already added)';
 $s = hs_imp_set(); $mode = strtolower($r['mode']) === 'dropship' ? 'dropship' : 'affiliate';
 if ($mode === 'dropship') {
  $m = $r['margin_percent'] !== '' ? (float) $r['margin_percent'] : (float) $s['margin']; $step = max(1, (int) $s['round']);
  $p = new WC_Product_Simple(); $p->set_regular_price(ceil($cost * (1 + $m / 100) / $step) * $step);
 } else {
  if ($r['link'] === '') return 'skipped (affiliate row needs a link)';
  $p = new WC_Product_External(); $p->set_product_url(esc_url_raw($r['link']));
  $p->set_button_text('Buy on ' . ($r['program'] !== '' ? $r['program'] : 'partner site')); $p->set_regular_price(round($cost));
 }
 $p->set_name($name); $p->set_description(wp_kses_post($r['description'])); $p->set_status('publish');
 $cid = hs_term_id($r['category']); if ($cid) $p->set_category_ids([$cid]);
 $id = $p->save(); if (!$id) return 'failed to save';
 $seo = hs_auto_seo($name, $r['program'], $mode, trim($r['category']));
 update_post_meta($id, '_hs_seo_title', trim($r['seo_title']) !== '' ? sanitize_text_field($r['seo_title']) : $seo[0]);
 update_post_meta($id, '_hs_seo_desc', trim($r['seo_desc']) !== '' ? sanitize_text_field($r['seo_desc']) : $seo[1]);
 update_post_meta($id, '_hs_key', $key); update_post_meta($id, '_hs_program', $r['program']); update_post_meta($id, '_hs_mode', $mode);
 if ($mode === 'dropship') { update_post_meta($id, '_hs_cost', $cost); update_post_meta($id, '_hs_src', $r['link']); }
 if ($r['image_url'] !== '') {
  require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
  $a = media_sideload_image(esc_url_raw($r['image_url']), $id, $name, 'id'); if (!is_wp_error($a)) set_post_thumbnail($id, $a);
 }
 return 'ok';
}
function hs_run_batch($n) {
 $q = (array) get_option('hs_queue', []); $ok = 0; $skip = 0;
 while ($n > 0 && $q) { $r = array_shift($q); $res = hs_make_product($r); if ($res === 'ok') { $ok++; $n--; } else { $skip++; hs_imp_log($r['name'] . ': ' . $res); } }
 update_option('hs_queue', $q, false); hs_imp_log("Published $ok products, skipped $skip. " . count($q) . ' left in queue.');
 return $ok;
}
add_action('init', function () { if (!wp_next_scheduled('hs_daily_import')) wp_schedule_event(time() + 600, 'daily', 'hs_daily_import'); });
add_action('hs_daily_import', function () { $s = hs_imp_set(); if (empty($s['paused'])) hs_run_batch((int) $s['limit']); });

function hs_import_page() {
 if (!current_user_can('manage_woocommerce')) return;
 $msg = '';
 if (!empty($_POST['hs_do']) && check_admin_referer('hs_imp')) {
  $a = sanitize_key($_POST['hs_do']);
  if ($a === 'save') { update_option('hs_imp', ['limit' => max(1, (int) $_POST['limit']), 'margin' => max(0, (float) $_POST['margin']), 'round' => max(1, (int) $_POST['round']), 'paused' => empty($_POST['paused']) ? 0 : 1]); $msg = 'Settings saved.'; }
  if ($a === 'now') { $n = hs_run_batch((int) hs_imp_set()['limit']); $msg = "Published $n products."; }
  if ($a === 'clear') { update_option('hs_queue', [], false); $msg = 'Queue cleared.'; }
  if ($a === 'upload' && !empty($_FILES['csv']['tmp_name'])) {
   $f = fopen($_FILES['csv']['tmp_name'], 'r'); $h = fgetcsv($f); $h = array_map(function ($x) { return strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $x))); }, (array) $h);
   $q = (array) get_option('hs_queue', []); $c = 0; $cols = ['name', 'category', 'program', 'mode', 'merchant_price', 'link', 'image_url', 'description', 'margin_percent', 'currency', 'seo_title', 'seo_desc'];
   while (($row = fgetcsv($f)) !== false) { $r = []; foreach ($cols as $k) { $i = array_search($k, $h, true); $r[$k] = $i !== false && isset($row[$i]) ? trim($row[$i]) : ''; } if ($r['name'] !== '') { $q[] = $r; $c++; } }
   fclose($f); update_option('hs_queue', $q, false); $msg = "Added $c rows to the queue.";
  }
 }
 $s = hs_imp_set(); $q = (array) get_option('hs_queue', []); $log = (array) get_option('hs_imp_log', []); ?>
<div class="wrap"><h1>Product Importer</h1>
<?php if ($msg) echo '<div class="notice notice-success"><p>' . esc_html($msg) . '</p></div>'; ?>
<p>Upload a CSV of products. The site publishes <b><?php echo (int) $s['limit']; ?></b> of them every day automatically. In the queue now: <b><?php echo count($q); ?></b>. Template: <a href="<?php echo esc_url(get_template_directory_uri() . '/assets/product-import-template.csv'); ?>">download CSV template</a>.</p>
<form method="post" enctype="multipart/form-data"><?php wp_nonce_field('hs_imp'); ?><input type="hidden" name="hs_do" value="upload"><input type="file" name="csv" accept=".csv" required> <button class="button button-primary">Add to queue</button></form>
<h2>Rules</h2>
<form method="post"><?php wp_nonce_field('hs_imp'); ?><input type="hidden" name="hs_do" value="save">
<table class="form-table">
<tr><th>Products published per day</th><td><input type="number" name="limit" value="<?php echo (int) $s['limit']; ?>" min="1"></td></tr>
<tr><th>Dropship margin on cost (%)</th><td><input type="number" step="0.1" name="margin" value="<?php echo esc_attr($s['margin']); ?>" min="0"> <span class="description">Used when a row has no margin_percent. Sell price = cost + margin, rounded up.</span></td></tr>
<tr><th>Round prices up to nearest Rs.</th><td><input type="number" name="round" value="<?php echo (int) $s['round']; ?>" min="1"></td></tr>
<tr><th>Pause daily publishing</th><td><label><input type="checkbox" name="paused" <?php checked($s['paused']); ?>> Paused</label></td></tr>
</table><button class="button button-primary">Save rules</button></form>
<p><b>Affiliate rows</b> keep the merchant's price (no mark-up) and send the customer to the merchant's site. <b>Dropship rows</b> are sold on your site at your price.</p>
<form method="post" style="display:inline"><?php wp_nonce_field('hs_imp'); ?><input type="hidden" name="hs_do" value="now"><button class="button">Publish the next batch now</button></form>
<form method="post" style="display:inline" onsubmit="return confirm('Clear the whole queue?')"><?php wp_nonce_field('hs_imp'); ?><input type="hidden" name="hs_do" value="clear"><button class="button">Clear queue</button></form>
<h2>Recent activity</h2><pre style="background:#fff;padding:10px;max-height:260px;overflow:auto"><?php echo esc_html(implode("\n", $log) ?: 'Nothing yet.'); ?></pre></div>
<?php }
