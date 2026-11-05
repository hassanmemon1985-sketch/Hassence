<?php
/** My Account: customer details (name, contact, address, bank, Easypaisa/JazzCash, country, city, wallet). */
if (!defined('ABSPATH')) exit;

function hs_acc_keys() { return ['hs_bank_name', 'hs_bank_title', 'hs_bank_no', 'hs_wallet_type', 'hs_wallet_no']; }
add_action('woocommerce_edit_account_form', function () {
 $id = get_current_user_id(); $g = function ($k) use ($id) { return esc_attr(get_user_meta($id, $k, true)); };
 $wt = get_user_meta($id, 'hs_wallet_type', true);
 echo '<fieldset><legend><b>Payment details</b></legend>';
 echo '<p class="form-row"><label>Bank name<input type="text" class="input-text" name="hs_bank_name" value="' . $g('hs_bank_name') . '"></label></p>';
 echo '<p class="form-row"><label>Account title<input type="text" class="input-text" name="hs_bank_title" value="' . $g('hs_bank_title') . '"></label></p>';
 echo '<p class="form-row"><label>Bank account number or IBAN<input type="text" class="input-text" name="hs_bank_no" value="' . $g('hs_bank_no') . '"></label></p>';
 echo '<p class="form-row"><label>Mobile wallet<select name="hs_wallet_type"><option value="">Choose</option><option' . selected($wt, 'Easypaisa', false) . '>Easypaisa</option><option' . selected($wt, 'JazzCash', false) . '>JazzCash</option></select></label></p>';
 echo '<p class="form-row"><label>Easypaisa or JazzCash number<input type="text" class="input-text" name="hs_wallet_no" value="' . $g('hs_wallet_no') . '"></label></p></fieldset>';
});
add_action('woocommerce_save_account_details', function ($uid) {
 foreach (hs_acc_keys() as $k) {
  if (!isset($_POST[$k])) continue;
  $v = sanitize_text_field(wp_unslash($_POST[$k]));
  if ($k === 'hs_wallet_type' && !in_array($v, ['', 'Easypaisa', 'JazzCash'], true)) continue;
  update_user_meta($uid, $k, $v);
 }
});
add_action('woocommerce_account_dashboard', function () {
 $id = get_current_user_id(); $u = get_userdata($id); $m = function ($k) use ($id) { return trim((string) get_user_meta($id, $k, true)); };
 $name = trim($m('billing_first_name') . ' ' . $m('billing_last_name')); if ($name === '') $name = $u->display_name;
 $addr = trim($m('billing_address_1') . ' ' . $m('billing_address_2'));
 $cc = hs_user_country($id); if (!$cc) $cc = strtoupper($m('billing_country')); $cn = hs_countries();
 $bank = trim(implode(' · ', array_filter([$m('hs_bank_name'), $m('hs_bank_title'), $m('hs_bank_no')])));
 $wal = trim(implode(' · ', array_filter([$m('hs_wallet_type'), $m('hs_wallet_no')])));
 $rows = [
  'Name' => esc_html($name), 'Email' => esc_html($u->user_email), 'Contact number' => esc_html($m('billing_phone')),
  'Address' => esc_html($addr), 'City' => esc_html($m('billing_city')),
  'Country' => $cc ? hs_flag($cc) . ' ' . esc_html(isset($cn[$cc]) ? $cn[$cc] : $cc) : '',
  'Bank account' => esc_html($bank), 'Easypaisa / JazzCash' => esc_html($wal),
  'Wallet balance' => wc_price((float) get_user_meta($id, 'hs_wallet', true)),
 ];
 echo '<div class="hs-det"><h3>My details</h3><table>';
 foreach ($rows as $k => $v) echo '<tr><th>' . esc_html($k) . '</th><td>' . ($v !== '' ? $v : '<span class="hs-na">Not added yet</span>') . '</td></tr>';
 echo '</table><p><a class="button" href="' . esc_url(wc_get_endpoint_url('edit-account', '', wc_get_page_permalink('myaccount'))) . '">Edit my details</a> <a class="button" href="' . esc_url(wc_get_endpoint_url('edit-address', 'billing', wc_get_page_permalink('myaccount'))) . '">Edit my address</a></p></div>';
}, 5);
/* Admin: see the customer's payment details and set the wallet balance */
function hs_acc_admin($u) {
 if (!current_user_can('manage_woocommerce')) return;
 echo '<h2>Hassence customer details</h2><table class="form-table">';
 foreach (['hs_bank_name' => 'Bank name', 'hs_bank_title' => 'Account title', 'hs_bank_no' => 'Bank account / IBAN', 'hs_wallet_type' => 'Mobile wallet', 'hs_wallet_no' => 'Wallet number'] as $k => $l) echo '<tr><th>' . esc_html($l) . '</th><td>' . esc_html(get_user_meta($u->ID, $k, true)) . '</td></tr>';
 echo '<tr><th><label for="hs_wallet">Wallet balance (Rs.)</label></th><td><input type="number" step="0.01" name="hs_wallet" id="hs_wallet" value="' . esc_attr((float) get_user_meta($u->ID, 'hs_wallet', true)) . '"></td></tr></table>';
}
add_action('show_user_profile', 'hs_acc_admin'); add_action('edit_user_profile', 'hs_acc_admin');
function hs_acc_admin_save($uid) { if (current_user_can('manage_woocommerce') && isset($_POST['hs_wallet'])) update_user_meta($uid, 'hs_wallet', (float) $_POST['hs_wallet']); }
add_action('personal_options_update', 'hs_acc_admin_save'); add_action('edit_user_profile_update', 'hs_acc_admin_save');
