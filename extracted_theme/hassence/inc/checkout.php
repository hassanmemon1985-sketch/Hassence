<?php
/** Clean one-page checkout (Shopify style): contact, delivery, courier charges, payment, complete order. */
if (!defined('ABSPATH')) exit;

function hs_is_co() {
 return function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url('order-received') && !is_wc_endpoint_url('order-pay');
}
add_filter('body_class', function ($c) { if (hs_is_co()) $c[] = 'hs-co'; return $c; });

add_filter('woocommerce_checkout_fields', function ($f) {
 $b = &$f['billing'];
 unset($b['billing_company'], $b['billing_state']);
 $set = function ($k, $label, $prio, $req, $extra = []) use (&$b) {
  if (!isset($b[$k])) return;
  $b[$k] = array_merge($b[$k], ['label' => $label, 'placeholder' => $label, 'priority' => $prio, 'required' => $req, 'class' => ['form-row-wide']], $extra);
 };
 $set('billing_phone', 'Mobile phone number', 5, true);
 $set('billing_email', 'Email (optional)', 6, false);
 $set('billing_first_name', 'First name (optional)', 50, false);
 $set('billing_last_name', 'Last name (optional)', 60, false);
 $set('billing_address_1', 'Address', 70, true);
 $set('billing_address_2', 'Apartment, suite, etc. (optional)', 80, false);
 $set('billing_city', 'City', 90, true);
 $set('billing_postcode', 'Postal code (optional)', 100, false);
 if (isset($b['billing_country'])) $b['billing_country']['priority'] = 40;
 unset($f['order']['order_comments']);
 return $f;
}, 99);
add_filter('woocommerce_get_country_locale', function ($l) {
 $l['PK']['state'] = ['required' => false, 'hidden' => true];
 $l['PK']['postcode'] = ['required' => false];
 return $l;
});
add_filter('default_checkout_billing_country', function () { return 'PK'; });
add_filter('woocommerce_cart_needs_shipping_address', '__return_false', 20);
add_filter('woocommerce_order_button_text', function () { return 'Complete order'; });
add_filter('woocommerce_gateway_title', function ($t, $id) { return $id === 'cod' ? 'Cash on Delivery (COD)' : $t; }, 10, 2);
add_filter('gettext', function ($tr, $text, $domain) { return ($domain === 'woocommerce' && $text === 'Create an account?') ? 'Save this information for next time' : $tr; }, 10, 3);
add_action('init', function () {
 if (get_option('hs_co_v1')) return;
 update_option('woocommerce_enable_signup_and_login_from_checkout', 'yes');
 update_option('hs_co_v1', 1);
}, 31);

function hs_checkout_header() {
 $cart = WC()->cart;
 echo '<div class="co-head"><div class="wrap"><a class="logo" href="' . esc_url(home_url('/')) . '"><span><b class="lg1">HAS</b><b class="lg2">SENCE</b></span></a><a class="co-bag" href="' . esc_url(wc_get_cart_url()) . '" aria-label="Cart">🛍️</a></div></div>';
 if (!$cart || $cart->is_empty()) return;
 echo '<details class="co-sum"><summary><span>Order summary ▾</span><b>' . wp_kses_post($cart->get_total()) . '</b></summary><div class="co-items">';
 foreach ($cart->get_cart() as $it) {
  $p = $it['data'];
  echo '<div class="co-it">' . $p->get_image('thumbnail') . '<span class="co-n">' . esc_html($p->get_name()) . ' × ' . (int) $it['quantity'] . '</span><span>' . wp_kses_post($cart->get_product_subtotal($p, $it['quantity'])) . '</span></div>';
 }
 echo '<div class="co-row"><span>Subtotal</span><span>' . wp_kses_post($cart->get_cart_subtotal()) . '</span></div>';
 $c = hs_courier_total($cart);
 if ($c > 0) echo '<div class="co-row"><span>Courier charges</span><span>' . wc_price($c) . '</span></div>';
 echo '<div class="co-row"><b>Total</b><b>' . wp_kses_post($cart->get_total()) . '</b></div></div></details>';
}
add_action('woocommerce_review_order_before_payment', function () {
 $c = hs_courier_total(WC()->cart);
 echo '<h3 class="co-h">Shipping method</h3><div class="co-ship"><span>Courier charges</span><b>' . ($c > 0 ? wc_price($c) : 'Calculated at checkout') . '</b></div>';
 echo '<h3 class="co-h">Payment</h3><p class="co-sec">All transactions are secure and encrypted.</p>';
});
add_action('woocommerce_review_order_before_submit', function () {
 $cart = WC()->cart; $img = '';
 foreach ($cart->get_cart() as $it) { $img = $it['data']->get_image('thumbnail'); break; }
 $n = $cart->get_cart_contents_count();
 echo '<div class="co-total"><span class="co-th">' . $img . '</span><div><b>Total</b><br><small>' . $n . ($n === 1 ? ' item' : ' items') . '</small></div><div class="co-amt">' . wp_kses_post($cart->get_total()) . '</div></div><p class="cur-note">Prices on this page are charged in PKR.</p>';
});
add_action('woocommerce_after_checkout_form', function () {
 echo '<p class="co-terms"><a href="' . esc_url(hs_page_url('return-refund-policy')) . '">Terms and policies</a></p>';
});

add_filter('the_content', function ($c) {
 if (!in_the_loop() || !is_main_query()) return $c;
 if (function_exists('is_checkout') && is_checkout() && has_block('woocommerce/checkout', $c)) return do_shortcode('[woocommerce_checkout]');
 if (function_exists('is_cart') && is_cart() && has_block('woocommerce/cart', $c)) return do_shortcode('[woocommerce_cart]');
 return $c;
}, 5);
