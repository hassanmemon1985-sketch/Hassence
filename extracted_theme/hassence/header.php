<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php if (function_exists('hs_is_co') && hs_is_co()) { hs_checkout_header(); } else { ?>
<div class="stage<?php echo is_front_page() ? '' : ' inner'; ?>">
<div class="ovl">
<div class="fbar"><div class="fmq"><div class="mq"><?php foreach (preg_split('/\r\n|\r|\n/', get_theme_mod('hs_flash_text', "⚡ FLASH SALE – up to 70% OFF\n🚚 Shipping or courier charges may apply according to the product\n💵 Cash on Delivery\n🎁 New arrivals every day")) as $l) { if (trim($l) !== '') echo '<span>' . esc_html($l) . '</span>'; } ?></div></div>
<?php hs_cur_select(); ?><div class="clock"><span>⏰</span><div class="timer"><span id="h">00</span><span id="m">00</span><span id="s">00</span></div></div></div>
<div class="hrow ovlrow">
<a class="logo" href="<?php echo esc_url(home_url('/')); ?>"><?php if (has_custom_logo()) { echo wp_get_attachment_image(get_theme_mod('custom_logo'), 'full'); } else { ?><span><b class="lg1">HAS</b><b class="lg2">SENCE</b></span><?php } ?></a>
<form class="search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
<input type="search" name="s" placeholder="Search for dresses, shoes, bags…" value="<?php echo esc_attr(get_search_query()); ?>">
<input type="hidden" name="post_type" value="product"><button type="submit">Search</button></form>
<div class="icons"><?php echo hs_account_html(); ?><a href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="Cart">🛒<sup class="cartcount"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></sup></a><a class="sellbtn" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Sell on Hassence</a></div>
</div></div>
<?php if (!is_front_page()) { echo '</div>'; hs_nav(); } ?>
<?php } ?>
