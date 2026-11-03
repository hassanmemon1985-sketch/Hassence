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
<div class="tabwrap"><button type="button" class="tabarr l" aria-label="Scroll left">‹</button>
<div class="tabs" id="tabs">
<button class="tab on" data-t="all">All</button>
<?php foreach ($cats as $c) : ?><button class="tab" data-t="<?php echo esc_attr($c->slug); ?>"><?php echo hs_cat_icon($c) . ' ' . esc_html($c->name); ?></button><?php endforeach; ?>
</div><button type="button" class="tabarr r" aria-label="Scroll right">›</button></div>

<div class="hotrow">
<?php hs_catdd(); ?>
<label class="sortbox"><select id="sortSel" aria-label="Sort products">
<option value="az">Sort: A to Z</option>
<option value="za">Sort: Z to A</option>
<option value="low">Sort: Price low to high</option>
<option value="high">Sort: Price high to low</option>
<option value="pop">Sort: Most popular</option>
<option value="new" selected>Sort: New added</option>
</select></label>
</div>
<div class="grid" id="grid">
<?php $prods = hs_q(['limit' => 60, 'orderby' => 'date', 'order' => 'DESC']);
if ($prods) { foreach ($prods as $p) hs_card($p); } else { echo '<div class="empty">No products yet. Add them in WordPress → Products → Add New.</div>'; } ?>
</div>
<div class="more"><a href="<?php echo esc_url($shop); ?>"><button type="button">VIEW ALL PRODUCTS</button></a></div>

<section class="sell"><div><h3>Sell on Hassence</h3><p>Open your own store, list your products and reach customers across Pakistan.</p></div><a class="btn" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Start selling →</a></section>
</main>
<?php get_footer(); ?>
