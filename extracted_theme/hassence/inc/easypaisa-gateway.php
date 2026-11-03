<?php
/**
 * Hassence: Easypaisa / Raast QR payment (manual verification).
 * Customer scans the QR, pays, then uploads a screenshot and/or types the Transaction ID.
 * The order is set to "On hold" until the shop owner confirms the payment.
 */
if (!defined('ABSPATH')) exit;

class WC_Gateway_HS_Easypaisa extends WC_Payment_Gateway {
 public function __construct() {
  $this->id = 'hs_easypaisa';
  $this->method_title = 'Easypaisa / Raast QR';
  $this->method_description = 'Customer scans the QR code and uploads a payment screenshot or Transaction ID. You confirm the payment manually.';
  $this->has_fields = true;
  $this->supports = ['products'];
  $this->init_form_fields();
  $this->init_settings();
  $this->title = $this->get_option('title');
  $this->description = $this->get_option('description');
  $this->till = $this->get_option('till');
  add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
  add_action('woocommerce_thankyou_' . $this->id, [$this, 'thankyou']);
 }
 public function init_form_fields() {
  $this->form_fields = [
   'enabled' => ['title' => 'Enable', 'type' => 'checkbox', 'label' => 'Enable Easypaisa / Raast QR payment', 'default' => 'yes'],
   'title' => ['title' => 'Title', 'type' => 'text', 'default' => 'Easypaisa / Raast QR (scan & pay)'],
   'description' => ['title' => 'Description', 'type' => 'textarea', 'default' => 'Pay by scanning the QR code with the Easypaisa app or any Raast-enabled bank app.'],
   'till' => ['title' => 'Merchant till number', 'type' => 'text', 'default' => '998904576'],
  ];
 }
 public function payment_fields() {
  echo '<div class="hs-ep" data-ajax="' . esc_url(admin_url('admin-ajax.php')) . '" data-nonce="' . esc_attr(wp_create_nonce('hs_ep')) . '">';
  echo wpautop(wp_kses_post($this->description));
  echo '<img class="hs-ep-qr" src="' . esc_url(get_template_directory_uri() . '/assets/easypaisa-qr.jpg') . '" alt="Easypaisa QR code">';
  echo '<p><b>Merchant till number:</b> ' . esc_html($this->till) . '<br><b>Amount to pay:</b> ' . (WC()->cart ? WC()->cart->get_total() : '') . '</p>';
  echo '<p>1. Scan the QR code and pay the amount above.<br>2. Then upload your payment screenshot and/or type the Transaction ID (TID).</p>';
  echo '<p><label>Payment screenshot</label><input type="file" id="hs_ep_file" accept="image/jpeg,image/png,image/webp"><input type="hidden" name="hs_ep_proof" id="hs_ep_proof_id"> <span class="hs-ep-st"></span></p>';
  echo '<p><label>Transaction ID (TID)</label><input type="text" name="hs_ep_tid" class="input-text" placeholder="e.g. 1234567890" maxlength="40"></p></div>';
 }
 private static function proof_ok($name) {
  if (!preg_match('/^[A-Za-z0-9]{20}\.(jpg|png|webp)$/', $name)) return false;
  $u = wp_upload_dir();
  return file_exists($u['basedir'] . '/hs-payments/' . $name);
 }
 public function validate_fields() {
  $tid = isset($_POST['hs_ep_tid']) ? sanitize_text_field(wp_unslash($_POST['hs_ep_tid'])) : '';
  $proof = isset($_POST['hs_ep_proof']) ? sanitize_text_field(wp_unslash($_POST['hs_ep_proof'])) : '';
  if (strlen($tid) >= 6 || self::proof_ok($proof)) return true;
  wc_add_notice('Please pay using the QR code, then upload your payment screenshot or enter the Transaction ID.', 'error');
  return false;
 }
 public function process_payment($order_id) {
  $order = wc_get_order($order_id);
  $tid = isset($_POST['hs_ep_tid']) ? sanitize_text_field(wp_unslash($_POST['hs_ep_tid'])) : '';
  $proof = isset($_POST['hs_ep_proof']) ? sanitize_text_field(wp_unslash($_POST['hs_ep_proof'])) : '';
  if ($tid) $order->update_meta_data('_hs_ep_tid', $tid);
  if (self::proof_ok($proof)) $order->update_meta_data('_hs_ep_proof', $proof);
  $order->save();
  $order->update_status('on-hold', 'Awaiting Easypaisa payment verification. TID: ' . ($tid ? $tid : 'not given') . '.');
  wc_reduce_stock_levels($order_id);
  WC()->cart->empty_cart();
  return ['result' => 'success', 'redirect' => $this->get_return_url($order)];
 }
 public function thankyou() {
  echo '<p>Thank you! We received your payment details and will confirm your payment shortly. Your order will ship after confirmation.</p>';
 }
}
add_filter('woocommerce_payment_gateways', function ($g) { $g[] = 'WC_Gateway_HS_Easypaisa'; return $g; });

function hs_ep_upload() {
 check_ajax_referer('hs_ep', 'nonce');
 if (empty($_FILES['file'])) wp_send_json_error('No file selected.');
 if ($_FILES['file']['size'] > 5 * 1024 * 1024) wp_send_json_error('File is too big (max 5 MB).');
 require_once ABSPATH . 'wp-admin/includes/file.php';
 $mimes = ['jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
 $ft = wp_check_filetype($_FILES['file']['name'], $mimes);
 if (empty($ft['ext'])) wp_send_json_error('Please upload a JPG, PNG or WEBP image.');
 $ext = $ft['ext'] === 'jpeg' ? 'jpg' : $ft['ext'];
 $_FILES['file']['name'] = wp_generate_password(20, false, false) . '.' . $ext;
 $dir = function ($d) { $d['subdir'] = '/hs-payments'; $d['path'] = $d['basedir'] . '/hs-payments'; $d['url'] = $d['baseurl'] . '/hs-payments'; return $d; };
 add_filter('upload_dir', $dir);
 $r = wp_handle_upload($_FILES['file'], ['test_form' => false, 'mimes' => $mimes]);
 remove_filter('upload_dir', $dir);
 if (!empty($r['error'])) wp_send_json_error($r['error']);
 $u = wp_upload_dir();
 $idx = $u['basedir'] . '/hs-payments/index.html';
 if (!file_exists($idx)) @file_put_contents($idx, '');
 wp_send_json_success(['id' => basename($r['file'])]);
}
add_action('wp_ajax_hs_ep_upload', 'hs_ep_upload');
add_action('wp_ajax_nopriv_hs_ep_upload', 'hs_ep_upload');

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
 if ($order->get_payment_method() !== 'hs_easypaisa') return;
 $tid = $order->get_meta('_hs_ep_tid');
 $proof = $order->get_meta('_hs_ep_proof');
 echo '<h3>Easypaisa payment proof</h3><p><b>Transaction ID:</b> ' . ($tid ? esc_html($tid) : 'not given') . '</p>';
 if ($proof) {
  $u = wp_upload_dir();
  $url = esc_url($u['baseurl'] . '/hs-payments/' . $proof);
  echo '<p><a href="' . $url . '" target="_blank"><img src="' . $url . '" style="max-width:220px;border:1px solid #ccc"></a><br><a href="' . $url . '" target="_blank">Open screenshot</a></p>';
 } else echo '<p>No screenshot uploaded.</p>';
});
