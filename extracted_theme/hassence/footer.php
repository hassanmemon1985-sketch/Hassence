<?php if (function_exists('hs_is_co') && hs_is_co()) { wp_footer(); echo '</body></html>'; return; } ?>
<footer><div class="wrap fperks">
<div><span>🚚</span><div><b>Fast Delivery</b>Across Pakistan</div></div>
<div><span>💵</span><div><b>Cash on Delivery</b>Pay at your door</div></div>
<div><span>💬</span><div><b>Customer Support</b>info@hassence.com</div></div>
<div><span>🔒</span><div><b>Secure Payment</b>100% protected</div></div>
</div><div class="wrap">
<div><h5>Hassence</h5><p>hassence.com – fragrances, fashion, beauty, electronics and more.</p><p>info@hassence.com</p><p>Gurumandir, Jamsheed Quarters, Karachi, Pakistan</p></div>
<div><h5>Customer Care</h5><p><a href="<?php echo esc_url(hs_page_url('help-center')); ?>">Help Center</a></p><p><a href="<?php echo esc_url(hs_page_url('how-to-buy')); ?>">How to Buy</a></p><p><a href="<?php echo esc_url(hs_page_url('return-refund-policy')); ?>">Returns &amp; Refunds</a></p></div>
<div><h5>About</h5><p><a href="<?php echo esc_url(hs_page_url('about-us')); ?>">About Us</a></p><p><a href="<?php echo esc_url(hs_page_url('careers')); ?>">Careers</a></p><p><a href="<?php echo esc_url(hs_page_url('contact-us')); ?>">Contact</a></p></div>
<div><h5>Payments</h5><p>Cash on Delivery</p><p><button type="button" class="qr-open">Easypaisa / Raast QR</button></p></div>
</div></footer>
<a class="wa-float" href="<?php echo esc_url(hs_wa_link()); ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><?php echo hs_wa_svg(34); ?></a>
<nav class="tabbar" aria-label="Quick menu">
<a href="<?php echo esc_url(home_url('/')); ?>"><i>🏠</i><span>Home</span></a>
<a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><i>🛍️</i><span>Shop</span></a>
<a href="<?php echo esc_url(wc_get_cart_url()); ?>"><i>🛒<sup class="cartcount"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></sup></i><span>Cart</span></a>
<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><i>👤</i><span>Account</span></a>
</nav>
<?php $hs_ep = get_option('woocommerce_hs_easypaisa_settings', []); $hs_till = !empty($hs_ep['till']) ? $hs_ep['till'] : '998904576'; ?>
<div class="qr-modal" id="qrm" hidden><div class="qr-box"><button type="button" class="qr-x" aria-label="Close">×</button><h3>Pay with Easypaisa / Raast</h3><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/easypaisa-qr.jpg'); ?>" alt="Easypaisa QR code"><p>Merchant till number: <b><?php echo esc_html($hs_till); ?></b></p><p class="qr-note">Scan the QR with the Easypaisa app. At checkout choose “Easypaisa / Raast QR”, then upload your payment screenshot or type the Transaction ID.</p></div></div>
<?php wp_footer(); ?>
</body>
</html>
