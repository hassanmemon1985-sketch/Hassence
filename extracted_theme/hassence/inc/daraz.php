<?php
/** Daraz Deals: flash-style Daraz logo, a "Daraz Deals" category and page for Daraz affiliate items. */
if (!defined('ABSPATH')) exit;

function hs_daraz_logo($size = 'md') {
 $img = get_theme_mod('hs_daraz_logo', '');
 $in = $img ? '<img src="' . esc_url($img) . '" alt="Daraz">' : '<span class="dz-bolt">⚡</span><b>DARAZ</b>';
 return '<span class="dz-logo dz-' . esc_attr($size) . '">' . $in . '<i class="dz-shine"></i></span>';
}
add_action('init', function () {
 if (get_option('hs_daraz_v1')) return;
 if (taxonomy_exists('product_cat') && !term_exists('Daraz Deals', 'product_cat')) wp_insert_term('Daraz Deals', 'product_cat', ['slug' => 'daraz-deals']);
 if (!get_page_by_path('daraz')) wp_insert_post(['post_title' => 'Daraz Deals', 'post_name' => 'daraz', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '[hassence_daraz]']);
 update_option('hs_daraz_v1', 1);
}, 47);
add_shortcode('hassence_daraz', function () {
 $a = ['post_type' => 'product', 'numberposts' => -1, 'fields' => 'ids', 'post_status' => 'publish'];
 $i1 = get_posts($a + ['tax_query' => [['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => ['daraz-deals']]]]);
 $i2 = get_posts($a + ['meta_query' => [['key' => '_hs_program', 'value' => 'daraz', 'compare' => 'LIKE']]]);
 $all = array_values(array_unique(array_merge($i1, $i2)));
 ob_start(); ?>
<div class="dz-head"><?php echo hs_daraz_logo('lg'); ?><p>Partner deals from Daraz. Tap a product and you will continue on Daraz to buy. Price and availability are set by Daraz and may change. We may earn a commission from your purchase.</p></div>
<?php
 if ($all) {
  echo '<div class="grid dz-grid">';
  foreach (wc_get_products(['include' => $all, 'limit' => 120, 'orderby' => 'date', 'order' => 'DESC', 'status' => 'publish']) as $p) hs_card($p);
  echo '</div>';
 } else {
  echo '<div class="empty">No Daraz items yet.' . (current_user_can('manage_woocommerce') ? ' Add one: Products → Add New → choose "External/Affiliate product", paste your Daraz link, write "Daraz" in the Affiliate program box and tick the category "Daraz Deals".' : '') . '</div>';
 }
 return ob_get_clean();
});
add_action('customize_register', function ($c) {
 $c->add_section('hs_dz', ['title' => 'Daraz Deals', 'priority' => 39, 'description' => 'Optional: upload the official Daraz logo you received from the Daraz affiliate program. Until then a text logo is shown.']);
 $c->add_setting('hs_daraz_logo', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
 $c->add_control(new WP_Customize_Image_Control($c, 'hs_daraz_logo', ['label' => 'Daraz logo image', 'section' => 'hs_dz']));
 $c->add_setting('hs_daraz_strip', ['default' => true, 'sanitize_callback' => 'rest_sanitize_boolean']);
 $c->add_control('hs_daraz_strip', ['label' => 'Show the Daraz Deals strip on the home page', 'section' => 'hs_dz', 'type' => 'checkbox']);
});
/* Affiliate program name box in the product editor (works for External/Affiliate products) */
add_action('woocommerce_product_options_general_product_data', function () {
 woocommerce_wp_text_input(['id' => '_hs_program', 'label' => 'Affiliate program', 'placeholder' => 'Daraz', 'desc_tip' => true, 'description' => 'For External/Affiliate products: the program name, for example Daraz. Shows a coloured badge on the product.']);
});
add_action('woocommerce_product_options_general_product_data', function () {
 woocommerce_wp_text_input(['id' => '_hs_seo_title', 'label' => 'Google title (SEO)', 'desc_tip' => true, 'description' => 'About 50 to 60 characters, with the main keyword first.']);
 woocommerce_wp_textarea_input(['id' => '_hs_seo_desc', 'label' => 'Google description (SEO)', 'desc_tip' => true, 'description' => 'About 140 to 155 characters. Plain, honest, with the keyword once.']);
});
add_action('woocommerce_process_product_meta', function ($id) {
 if (isset($_POST['_hs_seo_title'])) update_post_meta($id, '_hs_seo_title', sanitize_text_field(wp_unslash($_POST['_hs_seo_title'])));
 if (isset($_POST['_hs_seo_desc'])) update_post_meta($id, '_hs_seo_desc', sanitize_textarea_field(wp_unslash($_POST['_hs_seo_desc'])));
 if (!isset($_POST['_hs_program'])) return;
 $v = sanitize_text_field(wp_unslash($_POST['_hs_program'])); $t = isset($_POST['product-type']) ? sanitize_key($_POST['product-type']) : '';
 update_post_meta($id, '_hs_program', $v);
 if ($t === 'external') update_post_meta($id, '_hs_mode', $v !== '' ? 'affiliate' : '');
});
