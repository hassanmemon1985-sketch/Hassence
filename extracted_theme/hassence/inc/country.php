<?php
/** Country flag next to the user name: choose country at register / login / account, shown in header, reviews and My Account. */
if (!defined('ABSPATH')) exit;

function hs_flag($code) {
 $c = strtoupper((string) $code); if (!preg_match('/^[A-Z]{2}$/', $c)) return '';
 $o = ''; foreach (str_split($c) as $ch) $o .= '&#' . (0x1F1E6 + ord($ch) - 65) . ';';
 return $o;
}
function hs_countries() { return (function_exists('WC') && WC()->countries) ? WC()->countries->get_countries() : ['PK' => 'Pakistan']; }
function hs_user_country($uid) { $c = get_user_meta($uid, 'hs_country', true); return $c ? strtoupper($c) : ''; }
function hs_user_flag_html($uid) {
 $c = hs_user_country($uid); if (!$c) return '';
 $n = hs_countries();
 return '<span class="flag" title="' . esc_attr(isset($n[$c]) ? $n[$c] : $c) . '">' . hs_flag($c) . '</span>';
}
function hs_country_field($sel, $label) {
 $sel = $sel ? $sel : 'PK';
 echo '<p class="woocommerce-form-row form-row"><label for="hs_country">' . esc_html($label) . '</label><select name="hs_country" id="hs_country" class="hs-cs">';
 foreach (hs_countries() as $k => $v) echo '<option value="' . esc_attr($k) . '"' . selected($sel, $k, false) . '>' . esc_html($v) . '</option>';
 echo '</select></p>';
}
function hs_save_country($uid) {
 if (empty($_POST['hs_country'])) return;
 $c = strtoupper(sanitize_text_field(wp_unslash($_POST['hs_country'])));
 if (isset(hs_countries()[$c])) update_user_meta($uid, 'hs_country', $c);
}
add_action('woocommerce_register_form', function () { hs_country_field('PK', 'Country'); });
add_action('woocommerce_created_customer', 'hs_save_country');
add_action('woocommerce_login_form', function () { hs_country_field('PK', 'Login from'); });
add_action('wp_login', function ($login, $user) { hs_save_country($user->ID); }, 10, 2);
add_action('woocommerce_edit_account_form', function () { hs_country_field(hs_user_country(get_current_user_id()), 'Country'); });
add_action('woocommerce_save_account_details', 'hs_save_country');
add_action('woocommerce_checkout_update_user_meta', function ($cid, $data) {
 if ($cid && !hs_user_country($cid) && !empty($data['billing_country'])) update_user_meta($cid, 'hs_country', strtoupper($data['billing_country']));
}, 10, 2);

function hs_account_html() {
 $url = esc_url(wc_get_page_permalink('myaccount'));
 if (!is_user_logged_in()) return '<a href="' . $url . '" aria-label="Account">👤</a>';
 $u = wp_get_current_user();
 $n = $u->first_name ? $u->first_name : $u->display_name;
 return '<a class="acct" href="' . $url . '" aria-label="Account">👤<span class="acct-n">' . esc_html(mb_strimwidth($n, 0, 14, '…')) . '</span>' . hs_user_flag_html($u->ID) . '</a>';
}
add_filter('comment_author', function ($a, $id) {
 $c = get_comment($id);
 if ($c && $c->user_id) { $f = hs_user_flag_html($c->user_id); if ($f) return $a . ' ' . $f; }
 return $a;
}, 10, 2);
add_action('woocommerce_account_dashboard', function () {
 $c = hs_user_country(get_current_user_id()); if (!$c) return;
 $n = hs_countries();
 echo '<p>Logged in from ' . hs_user_flag_html(get_current_user_id()) . ' ' . esc_html(isset($n[$c]) ? $n[$c] : $c) . '</p>';
});
add_filter('manage_users_columns', function ($c) { $c['hs_country'] = 'Country'; return $c; });
add_filter('manage_users_custom_column', function ($v, $col, $uid) { return $col === 'hs_country' ? wp_kses_post(hs_user_flag_html($uid) . ' ' . esc_html(hs_user_country($uid))) : $v; }, 10, 3);
