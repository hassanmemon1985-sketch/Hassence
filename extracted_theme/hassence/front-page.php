<?php get_header(); ?>
<section class="hero"><div class="track" id="track">
<?php foreach (hs_slides() as $s) : if ($s[0] === 'img') : ?>
<a class="slide im<?php echo !empty($s[4]) ? ' hasm' : ''; ?>" href="<?php echo esc_url($s[2]); ?>" style="background-image:url('<?php echo esc_url($s[1]); ?>')<?php if (!empty($s[4])) echo ';--mob:url(\'' . esc_url($s[4]) . '\')'; ?>" aria-label="<?php echo esc_attr($s[3]); ?>"></a>
<?php continue; endif; ?>
<div class="slide" style="background:<?php echo esc_attr($s[0]); ?>"><div><h2><?php echo wp_kses_post($s[1]); ?></h2><p><?php echo wp_kses_post($s[2]); ?></p><a class="btn" href="<?php echo esc_url($s[4]); ?>"><?php echo esc_html($s[3]); ?></a></div><div class="em"><?php echo $s[5]; ?></div></div>
<?php endforeach; ?>
</div><button class="arr l" id="prev" aria-label="Previous">‹</button><button class="arr r" id="next" aria-label="Next">›</button><div class="dots" id="dots"></div></section>
</div>
<?php
$shop = wc_get_page_permalink('shop');
$cats = hs_get_cats();
?>
<main class="wrap">
<?php if (get_theme_mod('hs_daraz_strip', true) && function_exists('hs_daraz_logo')) : ?><a class="dz-strip" href="<?php echo esc_url(hs_page_url('daraz')); ?>"><?php echo hs_daraz_logo('md'); ?><span>Partner deals from Daraz ›</span></a><?php endif; ?>
<div class="hotrow"><?php hs_catdd(); ?></div>

<?php $shown = 0; foreach ($cats as $c) :
 $items = hs_q(['limit' => 12, 'category' => [$c->slug], 'orderby' => 'date', 'order' => 'DESC']);
 if (!$items) continue; $shown++;
 $tid = get_term_meta($c->term_id, 'thumbnail_id', true); $link = esc_url(get_term_link($c)); ?>
<section class="crow">
<a class="crow-head" href="<?php echo $link; ?>"><span class="ci"><?php echo $tid ? wp_get_attachment_image($tid, 'thumbnail') : hs_cat_icon($c); ?></span><span class="crow-n"><?php echo esc_html($c->name); ?></span><small>View all ›</small></a>
<div class="crow-wrap"><button type="button" class="car l" aria-label="Scroll left">‹</button>
<div class="crow-track"><?php foreach ($items as $p) hs_card($p); ?><a class="crow-all" href="<?php echo $link; ?>"><span>View all</span><small><?php echo esc_html($c->name); ?></small><b>›</b></a></div>
<button type="button" class="car r" aria-label="Scroll right">›</button></div>
</section>
<?php endforeach; ?>
<?php if (!$shown) echo '<div class="empty">No products yet. Add them in WordPress → Products → Add New, or use Bulk Upload.</div>'; ?>

<div class="more"><a href="<?php echo esc_url($shop); ?>"><button type="button">SHOP ALL PRODUCTS</button></a></div>

<section class="sell"><div><h3>Sell on Hassence</h3><p>Open your own store, list your products and reach customers across Pakistan.</p></div><a class="btn" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Start selling →</a></section>
</main>
<?php get_footer(); ?>
