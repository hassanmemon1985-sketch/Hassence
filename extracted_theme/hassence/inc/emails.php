<?php
/** All store emails are sent from info@hassence.com. Optional SMTP via wp-config.php constants. */
if (!defined('ABSPATH')) exit;
if (!defined('HS_MAIL')) define('HS_MAIL', 'info@hassence.com');

add_filter('wp_mail_from', function () { return HS_MAIL; }, 99);
add_filter('wp_mail_from_name', function () { return 'Hassence'; }, 99);
add_filter('woocommerce_email_from_address', function () { return HS_MAIL; }, 99);
add_filter('woocommerce_email_from_name', function () { return 'Hassence'; }, 99);
add_filter('woocommerce_email_recipient_new_order', function ($r) {
 $l = array_filter(array_map('trim', explode(',', (string) $r)));
 $l[] = HS_MAIL;
 return implode(',', array_unique($l));
}, 99);
add_filter('woocommerce_email_headers', function ($h) { return $h . 'Reply-To: Hassence <' . HS_MAIL . ">\r\n"; }, 10);
add_action('phpmailer_init', function ($m) {
 if (!defined('HS_SMTP_PASS')) return;
 $port = defined('HS_SMTP_PORT') ? (int) HS_SMTP_PORT : 465;
 $m->isSMTP();
 $m->Host = defined('HS_SMTP_HOST') ? HS_SMTP_HOST : 'mail.hassence.com';
 $m->SMTPAuth = true;
 $m->Port = $port;
 $m->SMTPSecure = $port === 587 ? 'tls' : 'ssl';
 $m->Username = defined('HS_SMTP_USER') ? HS_SMTP_USER : HS_MAIL;
 $m->Password = HS_SMTP_PASS;
 $m->setFrom(HS_MAIL, 'Hassence', false);
});
