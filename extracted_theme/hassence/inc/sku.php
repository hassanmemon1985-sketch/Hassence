<?php
/** Automatic, never-duplicate SKUs: category initials + running number (e.g. WF-001001). */
if (!defined('ABSPATH')) exit;

function hs_make_sku($p) {
 $pre = 'HS';
 $ids = $p->get_category_ids();
 if ($ids) {
  $t = get_term($ids[0], 'product_cat');
  if ($t && !is_wp_error($t)) {
   $w = preg_split('/[^A-Za-z]+/', html_entity_decode($t->name), -1, PREG_SPLIT_NO_EMPTY);
   $x = '';
   foreach (array_slice($w, 0, 3) as $word) $x .= strtoupper($word[0]);
   if (strlen($x) >= 2) $pre = $x;
  }
 }
 do {
  $n = (int) get_option('hs_sku_counter', 1000) + 1;
  update_option('hs_sku_counter', $n, false);
  $sku = $pre . '-' . str_pad($n, 6, '0', STR_PAD_LEFT);
 } while (wc_get_product_id_by_sku($sku));
 return $sku;
}
function hs_auto_sku($id) {
 static $busy = false;
 if ($busy) return;
 $p = wc_get_product($id);
 if (!$p || $p->is_type('variation') || $p->get_sku('edit')) return;
 $busy = true;
 for ($i = 0; $i < 5; $i++) {
  try { $p->set_sku(hs_make_sku($p)); $p->save(); break; } catch (Exception $e) { /* duplicate: try the next number */ }
 }
 $busy = false;
}
add_action('woocommerce_new_product', 'hs_auto_sku', 20);
add_action('woocommerce_update_product', 'hs_auto_sku', 20);
