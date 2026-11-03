<?php
/** Store pages (created once) + footer link helper + home meta description. */
if (!defined('ABSPATH')) exit;

function hs_page_url($slug) {
 $p = get_page_by_path($slug);
 return $p ? get_permalink($p) : '#';
}
function hs_pages_list() {
 return [
 'help-center' => ['Help Center', '<p>We are here to help. For any question about an order, a product or your account, email us at <a href="mailto:info@hassence.com">info@hassence.com</a>. Please include your order number and mobile number so we can help you quickly.</p>
<h3>Common questions</h3>
<p><b>How do I place an order?</b><br>Open the product you like, tap the + on the picture or press Buy now, then complete the checkout form. See our <a href="/how-to-buy/">How to Buy</a> page for every step.</p>
<p><b>How can I pay?</b><br>You can pay by Cash on Delivery or by Easypaisa / Raast QR. For QR payments, scan the QR code at checkout, pay, then upload your payment screenshot or type the Transaction ID.</p>
<p><b>What are the courier charges?</b><br>Courier charges depend on the product and are shown in your order total at checkout. One courier charge applies per delivery address.</p>
<p><b>Can I return a product or get a refund?</b><br>No. All sales are final. Please read our <a href="/return-refund-policy/">Return and Refund Policy</a> before ordering.</p>
<p><b>How do I write a review?</b><br>Log in to your account. You can review a product after you have bought it.</p>
<h3>Contact us</h3>
<p>Email: <a href="mailto:info@hassence.com">info@hassence.com</a><br>WhatsApp: +92 333 3848131<br>Address: Gurumandir, Jamsheed Quarters, Karachi, Pakistan</p>'],
 'how-to-buy' => ['How to Buy', '<p>Shopping on Hassence takes just a few minutes.</p>
<ol>
<li><b>Find a product.</b> Use the category tabs, the <b>Categories</b> button, the search bar or the Sort button to find what you want.</li>
<li><b>Open the product.</b> Check the picture, price, description and shipping information.</li>
<li><b>Add it to your cart.</b> Tap the <b>+</b> on the product picture to add it to your cart, or press <b>Buy now</b> to go straight to checkout. Choose the quantity first if you want more than one.</li>
<li><b>Fill in your details.</b> Enter your mobile number, your name and your delivery address.</li>
<li><b>Check the total.</b> Your courier charge is shown with your total before you pay.</li>
<li><b>Choose how to pay.</b> Pick <b>Cash on Delivery</b>, or <b>Easypaisa / Raast QR</b>. For QR, scan the code, pay the amount, then upload your payment screenshot or type the Transaction ID.</li>
<li><b>Complete your order.</b> Press <b>Complete order</b>. We will confirm your order and send it by courier.</li>
</ol>
<p>Need help? Email <a href="mailto:info@hassence.com">info@hassence.com</a> or chat with us on WhatsApp using the green button.</p>'],
 'return-refund-policy' => ['Return and Refund Policy', '<p><b>No return and no refund.</b></p>
<p>All orders placed on Hassence are final. We do not accept returns and we do not give refunds after an order is placed.</p>
<p>Please check the product name, picture, description and price carefully before you order. If you have any question about a product, contact us before ordering at <a href="mailto:info@hassence.com">info@hassence.com</a> or on WhatsApp at +92 333 3848131.</p>
<p>By placing an order on hassence.com you agree to this policy.</p>'],
 'about-us' => ['About Us', '<p>Hassence is an online store based in Karachi, Pakistan. We are an e-commerce company that brings fragrances, fashion, beauty, electronics and lifestyle products together in one easy place to shop.</p>
<p>Our goal is simple: good products, clear prices and an easy way to order from your phone. You can pay by Cash on Delivery or by Easypaisa / Raast QR, and we deliver across Pakistan by courier.</p>
<h3>What we offer</h3>
<ul><li>A growing range of products in many categories</li><li>New products added regularly</li><li>Simple ordering and clear courier charges at checkout</li><li>A marketplace where approved sellers can list their own products</li></ul>
<p>Want to sell on Hassence? Use the <b>Sell on Hassence</b> button at the top of the site.</p>
<p>Questions? Email us at <a href="mailto:info@hassence.com">info@hassence.com</a>.</p>'],
 'careers' => ['Careers', '<p>Hassence is a growing e-commerce company in Karachi. We are always happy to hear from people who like online business and want to grow with us.</p>
<h3>Areas we hire in</h3>
<ul><li><b>Customer support:</b> answer customers by WhatsApp and email and follow up on orders</li><li><b>Product listing and catalog:</b> add products, photos, prices and descriptions to the website</li><li><b>Digital marketing and social media:</b> posts, ads and promotions</li><li><b>Order and delivery coordination:</b> pack orders and work with couriers</li><li><b>Seller onboarding:</b> help new sellers list their products</li></ul>
<h3>How to apply</h3>
<p>Email your CV to <a href="mailto:info@hassence.com">info@hassence.com</a>. Write the role you want in the subject line and tell us a little about yourself. If your profile fits, we will contact you.</p>
<p>Location: Gurumandir, Jamsheed Quarters, Karachi, Pakistan.</p>'],
 'contact-us' => ['Contact Us', '<p>We would love to hear from you.</p>
<p><b>Email:</b> <a href="mailto:info@hassence.com">info@hassence.com</a></p>
<p><b>WhatsApp:</b> <a href="https://wa.me/923333848131">+92 333 3848131</a></p>
<p><b>Address:</b><br>Gurumandir, Jamsheed Quarters,<br>Karachi, Pakistan</p>
<p>When you write to us about an order, please include your order number and mobile number.</p>'],
 ];
}
add_action('init', function () {
 if (get_option('hs_pages_v1')) return;
 foreach (hs_pages_list() as $slug => $pg) {
  if (!get_page_by_path($slug)) wp_insert_post(['post_title' => $pg[0], 'post_name' => $slug, 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => $pg[1]]);
 }
 update_option('hs_pages_v1', 1);
}, 40);
