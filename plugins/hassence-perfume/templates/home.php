<?php
/**
 * Hassence Perfume Mode — perfume home page.
 * Uses the active Hassence theme header, product cards and footer.
 */
if (!defined('ABSPATH')) exit;

get_header();

$hsp_shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

/* Hero image: first image banner, otherwise the theme fallback. */
$hsp_hero = '';
if (function_exists('hs_slides')) {
    foreach ((array) hs_slides() as $slide) {
        if (isset($slide[0], $slide[1]) && $slide[0] === 'img' && !empty($slide[1])) {
            $hsp_hero = $slide[1];
            break;
        }
    }
}
if (!$hsp_hero) {
    $hsp_hero = get_template_directory_uri() . '/assets/og-default.jpg';
}

if (!function_exists('hsp_tpl_term')) {
    function hsp_tpl_term($slug) {
        $term = get_term_by('slug', $slug, 'product_cat');
        return ($term && !is_wp_error($term)) ? $term : null;
    }
}
if (!function_exists('hsp_tpl_link')) {
    function hsp_tpl_link($slug) {
        $term = hsp_tpl_term($slug);
        return $term ? get_term_link($term) : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/'));
    }
}
if (!function_exists('hsp_tpl_img')) {
    function hsp_tpl_img($slug) {
        $term = hsp_tpl_term($slug);
        if ($term) {
            $thumb = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
            if ($thumb) {
                $url = wp_get_attachment_image_url($thumb, 'large');
                if ($url) return $url;
            }
        }

        if (function_exists('hs_q')) {
            $items = hs_q(['limit' => 1, 'category' => [$slug]]);
            if (!empty($items)) {
                $id = $items[0]->get_image_id();
                if ($id) {
                    $url = wp_get_attachment_image_url($id, 'large');
                    if ($url) return $url;
                }
            }
        }

        return '';
    }
}
if (!function_exists('hsp_tpl_tag_link')) {
    function hsp_tpl_tag_link($slug) {
        $term = get_term_by('slug', $slug, 'product_tag');
        return ($term && !is_wp_error($term)) ? get_term_link($term) : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/'));
    }
}
if (!function_exists('hsp_tpl_section')) {
    function hsp_tpl_section($title, $eyebrow, $link, $products, $link_label = 'View all') {
        if (empty($products)) return;
        ?>
        <section class="hsp-section">
            <div class="hsp-head">
                <div>
                    <p class="hsp-eyebrow"><?php echo esc_html($eyebrow); ?></p>
                    <h2 class="hsp-title"><?php echo esc_html($title); ?></h2>
                </div>
                <?php if ($link) : ?>
                    <a class="hsp-all" href="<?php echo esc_url($link); ?>"><?php echo esc_html($link_label); ?> &rsaquo;</a>
                <?php endif; ?>
            </div>
            <div class="hsp-grid">
                <?php foreach ($products as $product) { if (function_exists('hs_card')) hs_card($product); } ?>
            </div>
        </section>
        <?php
    }
}

$hsp_genders = [
    ['mens-perfume',   'Men',      'Bold, woody and fresh scents'],
    ['womens-perfume', 'Women',      'Floral, fruity and sweet scents'],
    ['unisex-perfume', 'Unisex', 'Shared scents for any day'],
];

$hsp_cat_slugs = array_keys(hsp_cats());

$hsp_best = function_exists('hs_q') ? hs_q(['limit' => 8, 'category' => $hsp_cat_slugs, 'tag' => ['best-sellers'], 'orderby' => 'popularity', 'order' => 'DESC']) : [];
if (!$hsp_best && function_exists('hs_q')) {
    $hsp_best = hs_q(['limit' => 8, 'category' => $hsp_cat_slugs, 'orderby' => 'popularity', 'order' => 'DESC']);
}

$hsp_new = function_exists('hs_q') ? hs_q(['limit' => 8, 'category' => $hsp_cat_slugs, 'tag' => ['new-arrivals'], 'orderby' => 'date', 'order' => 'DESC']) : [];
if (!$hsp_new && function_exists('hs_q')) {
    $hsp_new = hs_q(['limit' => 8, 'category' => $hsp_cat_slugs, 'orderby' => 'date', 'order' => 'DESC']);
}
$hsp_oud = function_exists('hs_q') ? hs_q(['limit' => 4, 'category' => ['oud-attar']]) : [];
$hsp_gift = function_exists('hs_q') ? hs_q(['limit' => 4, 'category' => ['gift-sets']]) : [];

$hsp_flash_url = add_query_arg('hs_sale', 1, $hsp_shop);
$hsp_flash_end = (new DateTimeImmutable('tomorrow', wp_timezone()))->getTimestamp();
?>

<header class="hsp-topbar">
    <button class="hsp-burger" type="button" aria-label="Open menu" aria-expanded="false">&#9776;</button>
    <a class="hsp-logo" href="<?php echo esc_url(home_url('/')); ?>">
        <span class="hsp-lg1">HAS</span><span class="hsp-lg2">SENCE</span>
    </a>
    <div class="hsp-topactions">
        <a class="hsp-topicon" href="<?php echo esc_url(add_query_arg('s', '', home_url('/'))); ?>" title="Search">&#128302;</a>
        <a class="hsp-topicon hsp-cart" href="<?php echo esc_url($hsp_shop ? wc_get_cart_url() : ''); ?>" aria-label="Cart">&#128722;<span class="hsp-cart-count"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></span></a>
    </div>
</header>
<nav class="hsp-drawer" hidden aria-label="Categories">
    <ul>
        <li><a href="<?php echo esc_url(hsp_tpl_link('mens-perfume')); ?>">Men</a></li>
        <li><a href="<?php echo esc_url(hsp_tpl_link('womens-perfume')); ?>">Women</a></li>
        <li><a href="<?php echo esc_url(hsp_tpl_link('unisex-perfume')); ?>">Unisex</a></li>
        <li><a href="<?php echo esc_url(hsp_tpl_tag_link('best-sellers')); ?>">Best seller</a></li>
        <li><a href="<?php echo esc_url(hsp_tpl_tag_link('new-arrivals')); ?>">New arrival</a></li>
        <li><a href="<?php echo esc_url(hsp_tpl_link('gift-sets')); ?>">Bundles</a></li>
        <li><a href="<?php echo esc_url(add_query_arg('hs_sale', 1, $hsp_shop)); ?>">Sale</a></li>
        <li><a href="<?php echo esc_url(hsp_tpl_link('oud-attar')); ?>">Attar best seller</a></li>
        <li><a href="<?php echo esc_url(hsp_tpl_link('gift-sets')); ?>">Bundle offers</a></li>
    </ul>
</nav>

<section class="hsp-flash" aria-label="Flash sale">
    <div class="hsp-flash-inner">
        <div class="hsp-flash-copy">
            <span class="hsp-flash-tag">Flash sale</span>
            <div>
                <h2>Up to <em>70% off</em> signature perfumes</h2>
                <p>Limited stock. Offer ends at midnight. &middot; Cash on Delivery &middot; Easypaisa / Raast &middot; 100% original</p>
            </div>
        </div>
        <div class="hsp-flash-timer" data-hsp-countdown data-end="<?php echo esc_attr($hsp_flash_end); ?>" aria-label="Time left in the flash sale">
            <span class="hsp-flash-time"><b data-hsp-h>00</b><small>Hrs</small></span>
            <i>:</i>
            <span class="hsp-flash-time"><b data-hsp-m>00</b><small>Min</small></span>
            <i>:</i>
            <span class="hsp-flash-time"><b data-hsp-s>00</b><small>Sec</small></span>
        </div>
        <a class="hsp-flash-btn" href="<?php echo esc_url($hsp_flash_url); ?>">Shop the sale <span aria-hidden="true">&rarr;</span></a>
    </div>
</section>

<section class="hsp-hero" style="background-image:url('<?php echo esc_url($hsp_hero); ?>')">
    <div class="hsp-hero-inner">
        <h1>Find your <em>signature</em> scent.</h1>
        <div class="hsp-actions">
            <a class="hsp-btn" href="<?php echo esc_url($hsp_shop); ?>">Shop perfumes</a>
            <a class="hsp-btn ghost" href="#hsp-shop">Shop by gender</a>
        </div>
    </div>
</section>

<main class="wrap hsp-main">

    <div class="hsp-trust">
        <div><b>100% original</b><span>Quality checked</span></div>
        <div><b>Cash on Delivery</b><span>Pay at your door</span></div>
        <div><b>Across Pakistan</b><span>Fast courier delivery</span></div>
        <div><b>Need help?</b><span>WhatsApp us anytime</span></div>
    </div>

    <section class="hsp-section" id="hsp-shop">
        <div class="hsp-head">
            <div>
                <p class="hsp-eyebrow">Shop by</p>
                <h2 class="hsp-title">Who is it for?</h2>
            </div>
            <a class="hsp-all" href="<?php echo esc_url($hsp_shop); ?>">All perfumes &rsaquo;</a>
        </div>
        <div class="hsp-tiles">
            <?php foreach ($hsp_genders as $item) :
                $image = hsp_tpl_img($item[0]);
                $style = $image ? 'background-image:url(' . esc_url($image) . ')' : 'background:linear-gradient(135deg,#141414,#2a2110)';
                ?>
                <a class="hsp-tile" href="<?php echo esc_url(hsp_tpl_link($item[0])); ?>" style="<?php echo esc_attr($style); ?>">
                    <span>
                        <b><?php echo esc_html($item[1]); ?></b>
                        <span><?php echo esc_html($item[2]); ?></span>
                        <i>Explore &rsaquo;</i>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php
    hsp_tpl_section('Best sellers', 'Most loved', hsp_tpl_tag_link('best-sellers'), $hsp_best);
    hsp_tpl_section('New arrivals', 'Just landed', $hsp_shop, $hsp_new);
    hsp_tpl_section('Oud & Attar', 'Rich and long-lasting', hsp_tpl_link('oud-attar'), $hsp_oud);
    hsp_tpl_section('Gift sets', 'Made to be given', hsp_tpl_link('gift-sets'), $hsp_gift);
    ?>

    <section class="hsp-section">
        <div class="hsp-head">
            <div>
                <p class="hsp-eyebrow">The Hassence promise</p>
                <h2 class="hsp-title">Why shop with us</h2>
            </div>
        </div>
        <div class="hsp-promise">
            <div><b>Long-lasting formulas</b><p>High-concentration fragrances made to stay with you through the day.</p></div>
            <div><b>Honest prices</b><p>Premium scent profiles without the designer price tag.</p></div>
            <div><b>Easy ordering</b><p>Cash on Delivery or Easypaisa / Raast QR. Delivered by courier across Pakistan.</p></div>
        </div>
    </section>

    <section class="hsp-section">
        <div class="hsp-head">
            <div>
                <p class="hsp-eyebrow">Simple &amp; safe</p>
                <h2 class="hsp-title">How to order</h2>
            </div>
        </div>
        <div class="hsp-steps">
            <div class="hsp-step"><span>1</span><b>Choose your perfume</b><small>Browse men’s, women’s and unisex scents.</small></div>
            <div class="hsp-step"><span>2</span><b>Add it to your cart</b><small>Check the size and quantity, then checkout.</small></div>
            <div class="hsp-step"><span>3</span><b>Pay at your door</b><small>Cash on Delivery, or Easypaisa / Raast QR.</small></div>
        </div>
    </section>

    <div class="hsp-help">
        <div>
            <b>Not sure which scent to choose?</b>
            <small>Send us a message and we will help you find the right perfume.</small>
        </div>
        <?php if (function_exists('hs_wa_link')) : ?>
            <a class="hsp-btn" href="<?php echo esc_url(hs_wa_link('Hello Hassence, I need help choosing a perfume.')); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
        <?php endif; ?>
    </div>

    <div class="more">
        <a href="<?php echo esc_url($hsp_shop); ?>"><button type="button">SHOP ALL PERFUMES</button></a>
    </div>

</main>

<?php get_footer(); ?>
