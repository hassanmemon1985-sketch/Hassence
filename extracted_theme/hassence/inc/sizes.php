<?php
/** Clothing / shoe sizes: set in the product editor, chosen under the title (one or many), saved in the order. */
if (!defined('ABSPATH')) exit;

function hs_size_defs() { return ['S' => 'Small', 'M' => 'Medium', 'L' => 'Large', 'XL' => 'Extra Large']; }
function hs_product_sizes($id) {
 $s = get_post_meta($id, '_hs_sizes', true); if (!is_array($s)) return [];
 $s = array_values(array_filter(array_map('strval', $s), function ($x) { return $x !== ''; }));
 $order = array_keys(hs_size_defs());
 usort($s, function ($a, $b) use ($order) { $x = array_search($a, $order, true); $y = array_search($b, $order, true); $x = $x === false ? 99 : $x; $y = $y === false ? 99 : $y; return $x === $y ? strnatcasecmp($a, $b) : $x - $y; });
 return $s;
}
function hs_parse_sizes($str) {
 $out = [];
 foreach (preg_split('/[,|;\/]+/', (string) $str) as $z) {
  $z = trim(sanitize_text_field($z)); if ($z === '' || strlen($z) > 12) continue;
  $u = strtoupper($z); $map = ['SMALL' => 'S', 'MEDIUM' => 'M', 'LARGE' => 'L', 'EXTRA LARGE' => 'XL', 'X-LARGE' => 'XL'];
  if (isset($map[$u])) $u = $map[$u];
  $out[] = in_array($u, array_keys(hs_size_defs()), true) ? $u : $z;
 }
 return array_values(array_unique($out));
}
add_action('add_meta_boxes', function () { add_meta_box('hs_sizes', 'Sizes (clothes and shoes)', 'hs_sizes_box', 'product', 'side', 'default'); });
function hs_sizes_box($post) {
 $cur = hs_product_sizes($post->ID); wp_nonce_field('hs_sz', 'hs_sz_n');
 foreach (hs_size_defs() as $k => $l) echo '<label style="display:block;margin:4px 0"><input type="checkbox" name="hs_sz[]" value="' . esc_attr($k) . '"' . checked(in_array($k, $cur, true), true, false) . '> ' . esc_html($l) . ' (' . esc_html($k) . ')</label>';
 $other = array_diff($cur, array_keys(hs_size_defs()));
 echo '<p style="margin-top:10px"><label>Other sizes, separated by commas (for example 38, 40, 42)<br><input type="text" name="hs_sz_other" value="' . esc_attr(implode(', ', $other)) . '" style="width:100%"></label></p><p class="description">Customers will see these under the product title and can pick one or more.</p>';
}
add_action('save_post_product', function ($id) {
 if (!isset($_POST['hs_sz_n']) || !wp_verify_nonce($_POST['hs_sz_n'], 'hs_sz') || !current_user_can('edit_post', $id)) return;
 $s = isset($_POST['hs_sz']) ? (array) wp_unslash($_POST['hs_sz']) : [];
 $s = array_values(array_filter($s, function ($v) { return isset(hs_size_defs()[$v]); }));
 $s = array_merge($s, hs_parse_sizes(isset($_POST['hs_sz_other']) ? wp_unslash($_POST['hs_sz_other']) : ''));
 update_post_meta($id, '_hs_sizes', array_values(array_unique($s)));
});

add_action('woocommerce_single_product_summary', function () {
 global $product; if (!$product) return;
 $s = hs_product_sizes($product->get_id()); if (!$s) return;
 echo '<div class="pdp-sizes"><b>Size</b><div class="sz">';
 foreach ($s as $z) echo '<label class="sz-c"><input type="checkbox" value="' . esc_attr($z) . '"><span>' . esc_html($z) . '</span></label>';
 echo '</div><small>Choose one size, or more than one.</small></div>';
}, 6);

function hs_req_sizes($pid) {
 $allowed = hs_product_sizes($pid); $in = isset($_REQUEST['hs_size']) ? (array) $_REQUEST['hs_size'] : []; $out = [];
 foreach ($in as $v) { $v = sanitize_text_field(wp_unslash($v)); if (in_array($v, $allowed, true) && !in_array($v, $out, true)) $out[] = $v; }
 return $out;
}
add_filter('woocommerce_add_to_cart_validation', function ($ok, $pid) {
 if (!$ok || !hs_product_sizes($pid)) return $ok;
 if (!hs_req_sizes($pid)) { wc_add_notice('Please choose a size.', 'error'); return false; }
 return $ok;
}, 10, 2);
add_filter('woocommerce_add_cart_item_data', function ($d, $pid) {
 if (isset($d['hs_size'])) return $d;
 $c = hs_req_sizes($pid); if ($c) $d['hs_size'] = $c[0];
 return $d;
}, 10, 2);
add_action('woocommerce_add_to_cart', function ($key, $pid, $qty, $vid, $var) {
 static $busy = false; if ($busy) return;
 $c = hs_req_sizes($pid); if (count($c) < 2) return;
 $busy = true;
 foreach (array_slice($c, 1) as $z) WC()->cart->add_to_cart($pid, $qty, $vid, $var, ['hs_size' => $z]);
 $busy = false;
}, 10, 5);
add_filter('woocommerce_get_item_data', function ($d, $ci) { if (!empty($ci['hs_size'])) $d[] = ['key' => 'Size', 'value' => $ci['hs_size']]; return $d; }, 10, 2);
add_action('woocommerce_checkout_create_order_line_item', function ($item, $key, $values) { if (!empty($values['hs_size'])) $item->add_meta_data('Size', $values['hs_size'], true); }, 10, 3);
