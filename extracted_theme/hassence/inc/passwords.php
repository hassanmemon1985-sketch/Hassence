<?php
/** Vendor password tools: change-password page, forgot-password link, admin "temporary password" tool. */
if (!defined('ABSPATH')) exit;

function hs_pw_url() { $p = get_page_by_path('change-password'); return $p ? get_permalink($p) : home_url('/change-password/'); }

add_action('init', function () {
 if (get_option('hs_pwpage_v1')) return;
 if (!get_page_by_path('change-password')) wp_insert_post(['post_title' => 'Change Password', 'post_name' => 'change-password', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '[hassence_change_password]']);
 update_option('hs_pwpage_v1', 1);
}, 45);

add_shortcode('hassence_change_password', function () {
 if (!is_user_logged_in()) return '<div class="hs-vf"><p>Please log in first.</p><a class="btn-pill" href="' . esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url()) . '">Log in</a></div>';
 $u = wp_get_current_user(); $msg = ''; $ok = false;
 if (isset($_POST['hs_pw_n']) && wp_verify_nonce($_POST['hs_pw_n'], 'hs_pw')) {
  $cur = isset($_POST['cur']) ? (string) wp_unslash($_POST['cur']) : '';
  $n1 = isset($_POST['n1']) ? (string) wp_unslash($_POST['n1']) : '';
  $n2 = isset($_POST['n2']) ? (string) wp_unslash($_POST['n2']) : '';
  if (!wp_check_password($cur, $u->user_pass, $u->ID)) $msg = 'Your current password is not correct.';
  elseif (strlen($n1) < 8) $msg = 'The new password must be at least 8 characters.';
  elseif ($n1 !== $n2) $msg = 'The two new passwords do not match.';
  elseif ($n1 === $cur) $msg = 'Please choose a password different from the current one.';
  else { wp_set_password($n1, $u->ID); delete_user_meta($u->ID, 'hs_must_change'); wp_set_auth_cookie($u->ID, true); wp_set_current_user($u->ID); $ok = true; $msg = 'Your password was changed.'; }
 }
 ob_start(); ?>
<div class="hs-vf"><h2>Change password</h2>
<?php if (!$ok && get_user_meta($u->ID, 'hs_must_change', true)) echo '<p class="hs-note">For your safety, please choose your own new password. Your current password is the temporary one you were given.</p>'; ?>
<?php if ($msg) echo '<p class="' . ($ok ? 'hs-ok' : 'hs-bad') . '">' . esc_html($msg) . '</p>'; ?>
<?php if (!$ok) : ?>
<form method="post"><?php wp_nonce_field('hs_pw', 'hs_pw_n'); ?>
<label>Current password<input type="password" name="cur" autocomplete="current-password" required></label>
<label>New password (at least 8 characters)<input type="password" name="n1" autocomplete="new-password" minlength="8" required></label>
<label>Repeat new password<input type="password" name="n2" autocomplete="new-password" minlength="8" required></label>
<button type="submit" class="btn-pill">Change password</button></form>
<?php else : ?><p><a class="btn-pill" href="<?php echo esc_url(home_url('/')); ?>">Back to the store</a></p><?php endif; ?></div>
<?php return ob_get_clean();
});

/* Temporary password: vendor must choose their own at next visit */
add_action('template_redirect', function () {
 if (!is_user_logged_in() || !get_user_meta(get_current_user_id(), 'hs_must_change', true)) return;
 if (is_page('change-password') || (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('customer-logout'))) return;
 wp_safe_redirect(hs_pw_url()); exit;
});
/* Forgot-password links go to the shop's own page, not wp-login.php */
add_filter('lostpassword_url', function ($u) { return function_exists('wc_lostpassword_url') ? wc_lostpassword_url() : $u; }, 20);
/* Add "Change password" to the Dokan vendor dashboard menu */
add_filter('dokan_get_dashboard_nav', function ($urls) {
 $urls['hs-change-password'] = ['title' => 'Change password', 'icon' => '<i class="fas fa-lock"></i>', 'url' => hs_pw_url(), 'pos' => 200];
 return $urls;
});

/* Admin tool */
add_action('admin_menu', function () { add_submenu_page('hs-orders', 'Vendor passwords', 'Vendor passwords', 'edit_users', 'hs-vpw', 'hs_vpw_page'); }, 30);
function hs_vpw_page() {
 if (!current_user_can('edit_users')) return;
 $note = '';
 if (!empty($_POST['hs_vpw']) && check_admin_referer('hs_vpw')) {
  $uid = (int) $_POST['uid']; $u = get_userdata($uid); $mode = sanitize_key($_POST['hs_vpw']);
  if (!$u || !current_user_can('edit_user', $uid) || user_can($u, 'manage_options')) $note = '<div class="notice notice-error"><p>You cannot change this account.</p></div>';
  else {
   $pw = $mode === 'set' ? (isset($_POST['pw']) ? (string) wp_unslash($_POST['pw']) : '') : wp_generate_password(10, false);
   if (strlen($pw) < 6) $note = '<div class="notice notice-error"><p>The password must be at least 6 characters.</p></div>';
   else {
    wp_set_password($pw, $uid);
    if (!empty($_POST['must'])) update_user_meta($uid, 'hs_must_change', 1); else delete_user_meta($uid, 'hs_must_change');
    $sent = '';
    if (!empty($_POST['mail'])) {
     $body = "Hello " . $u->display_name . ",\n\nYour Hassence vendor login was updated.\n\nLogin page: " . (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url()) . "\nUsername: " . $u->user_login . "\nTemporary password: " . $pw . "\n\n" . (!empty($_POST['must']) ? "You will be asked to choose your own new password when you sign in.\n\n" : '') . "If you did not expect this, reply to info@hassence.com.\n\nHassence";
     $sent = wp_mail($u->user_email, 'Your Hassence vendor login', $body) ? ' It was emailed to ' . $u->user_email . '.' : ' The email could not be sent. Check the email test on the Hassence Orders page.';
    }
    $note = '<div class="notice notice-success"><p><b>' . esc_html($u->display_name) . '</b> now has this password: <code style="font-size:16px">' . esc_html($pw) . '</code> Copy it now. It cannot be shown again.' . esc_html($sent) . '</p></div>';
   }
  }
 }
 $vendors = get_users(['role__in' => ['seller', 'vendor'], 'number' => 200, 'orderby' => 'display_name']); ?>
<div class="wrap"><h1>Vendor passwords</h1><?php echo wp_kses_post($note); ?>
<p>WordPress never stores a readable password, only a scrambled copy, so <b>nobody, including you, can look up an existing password</b>. What you can do here is set a new temporary password for a vendor, or generate one, and give it to them. They can then change it themselves.</p>
<table class="widefat striped"><thead><tr><th>Vendor</th><th>Email</th><th>Store</th><th>Set a password</th></tr></thead><tbody>
<?php foreach ($vendors as $v) : $ps = get_user_meta($v->ID, 'dokan_profile_settings', true); $store = is_array($ps) && !empty($ps['store_name']) ? $ps['store_name'] : '—'; ?>
<tr><td><b><?php echo esc_html($v->display_name); ?></b><br><small><?php echo esc_html($v->user_login); ?></small></td><td><?php echo esc_html($v->user_email); ?></td><td><?php echo esc_html($store); ?></td>
<td><form method="post"><?php wp_nonce_field('hs_vpw'); ?><input type="hidden" name="uid" value="<?php echo (int) $v->ID; ?>">
<input type="text" name="pw" placeholder="Type a password" style="width:170px">
<button class="button" name="hs_vpw" value="set">Set this password</button>
<button class="button button-primary" name="hs_vpw" value="gen">Generate a temporary one</button><br>
<label><input type="checkbox" name="must" checked> Vendor must choose a new password at next login</label>
<label style="margin-left:12px"><input type="checkbox" name="mail"> Email it to the vendor</label></form></td></tr>
<?php endforeach; if (!$vendors) echo '<tr><td colspan="4">No vendors yet. They appear here after Dokan is installed and sellers register.</td></tr>'; ?>
</tbody></table></div>
<?php }
