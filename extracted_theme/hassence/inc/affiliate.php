<?php
/** Affiliate products: program badge + colour, disclosure, "Buy on ..." button. */
if (!defined('ABSPATH')) exit;

function hs_prog_color($name) {
 $d = ['daraz' => '#f85606', 'aliexpress' => '#e62e04', 'amazon' => '#ff9900', 'alibaba' => '#ff6a00', 'shein' => '#111111', 'temu' => '#fb7701', 'markaz' => '#0a8f4e', 'naheed' => '#7b2d8e'];
 foreach (preg_split('/\r\n|\r|\n/', (string) get_theme_mod('hs_prog_colors', '')) as $l) {
  if (strpos($l, '=') === false) continue;
  list($a, $b) = array_map('trim', explode('=', $l, 2));
  if ($a !== '' && preg_match('/^#[0-9a-f]{3,6}$/i', $b)) $d[strtolower($a)] = $b;
 }
 $k = strtolower(trim($name));
 return isset($d[$k]) ? $d[$k] : '#6b7280';
}
function hs_is_aff($id) { return get_post_meta($id, '_hs_mode', true) === 'affiliate' && get_post_meta($id, '_hs_program', true) !== ''; }
function hs_prog_badge($id) {
 if (!hs_is_aff($id)) return '';
 $p = get_post_meta($id, '_hs_program', true);
 return '<span class="prog" style="background:' . esc_attr(hs_prog_color($p)) . '">' . esc_html($p) . '</span>';
}
add_action('woocommerce_single_product_summary', function () {
 global $product; if (!$product || !hs_is_aff($product->get_id())) return;
 $p = get_post_meta($product->get_id(), '_hs_program', true);
 echo '<p class="aff-note">' . hs_prog_badge($product->get_id()) . ' This product is sold by ' . esc_html($p) . '. We may earn a commission if you buy through this link. Price and availability are set by ' . esc_html($p) . ' and may change.</p>';
}, 31);
add_action('customize_register', function ($c) {
 $c->add_section('hs_prog', ['title' => 'Affiliate program colours', 'priority' => 37, 'description' => 'One per line, like Daraz=#f85606. Programs not listed use grey.']);
 $c->add_setting('hs_prog_colors', ['default' => '', 'sanitize_callback' => 'sanitize_textarea_field']);
 $c->add_control('hs_prog_colors', ['label' => 'Program colours', 'section' => 'hs_prog', 'type' => 'textarea']);
});

add_filter('pre_get_document_title', function ($t) {
 if (is_singular('product')) { $v = get_post_meta(get_the_ID(), '_hs_seo_title', true); if ($v) return $v; }
 return $t;
}, 20);
