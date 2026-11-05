<?php
/*
Plugin Name: Hassence Perfume Mode
Plugin URI: https://hassence.com
Description: Turns Hassence into a perfume-only store: black & gold storefront, men/women/unisex categories, a perfume home page, and safe cleanup of non-perfume products. Keeps the current Hassence theme, logo and fonts.
Version: 9.9.9
Author: Hassence
License: GPL-2.0-or-later
Text Domain: hassence-perfume
*/

if (!defined('ABSPATH')) exit;

define('HSP_VERSION', '9.9.9');
define('HSP_DIR', plugin_dir_path(__FILE__));
define('HSP_URL', plugin_dir_url(__FILE__));

/* -------------------------------------------------------------------------
 * 1. Catalogue structure
 * ---------------------------------------------------------------------- */

function hsp_cats() {
    return [
        'mens-perfume'   => "Men's Perfume",
        'womens-perfume' => "Women's Perfume",
        'unisex-perfume' => 'Unisex Perfume',
        'oud-attar'      => 'Oud & Attar',
        'gift-sets'      => 'Gift Sets',
    ];
}

function hsp_tags() {
    return [
        'best-sellers' => 'Best Sellers',
        'new-arrivals' => 'New Arrivals',
    ];
}

/**
 * SKU => categories + tags.
 * SKUs are stable, so this survives a re-import better than product IDs.
 */
function hsp_map() {
    return [
        'HS-001001' => ['cats' => ['unisex-perfume', 'gift-sets'], 'tags' => ['best-sellers']],
        'HS-001004' => ['cats' => ['mens-perfume'],                'tags' => []],
        'HS-001005' => ['cats' => ['mens-perfume'],                'tags' => []],
        'HS-001006' => ['cats' => ['mens-perfume'],                'tags' => ['best-sellers']],
        'HS-001007' => ['cats' => ['mens-perfume'],                'tags' => ['new-arrivals']],
        'HS-001008' => ['cats' => ['womens-perfume'],              'tags' => ['new-arrivals']],
        'HS-001009' => ['cats' => ['unisex-perfume'],              'tags' => []],
        'HS-001010' => ['cats' => ['mens-perfume'],                'tags' => ['best-sellers']],
        'HS-001011' => ['cats' => ['womens-perfume'],              'tags' => []],
        'HS-001012' => ['cats' => ['unisex-perfume'],              'tags' => ['best-sellers']],
        'HS-001013' => ['cats' => ['unisex-perfume', 'oud-attar'], 'tags' => []],
        'HS-001014' => ['cats' => ['unisex-perfume'],              'tags' => ['best-sellers']],
        'HS-001015' => ['cats' => ['unisex-perfume'],              'tags' => ['best-sellers', 'new-arrivals']],
        'HS-001016' => ['cats' => ['womens-perfume'],              'tags' => ['new-arrivals']],
        'HS-001017' => ['cats' => ['womens-perfume'],              'tags' => ['new-arrivals']],
        'HS-001018' => ['cats' => ['unisex-perfume'],              'tags' => []],
        'HS-001019' => ['cats' => ['unisex-perfume', 'mens-perfume'], 'tags' => ['new-arrivals']],
        'HS-001020' => ['cats' => ['unisex-perfume', 'mens-perfume'], 'tags' => []],
        'HS-001021' => ['cats' => ['unisex-perfume', 'mens-perfume'], 'tags' => ['new-arrivals']],
        'HS-001022' => ['cats' => ['mens-perfume'],                'tags' => ['new-arrivals']],
        'HS-001023' => ['cats' => ['unisex-perfume'],              'tags' => ['new-arrivals']],
    ];
}

/** Product IDs that are perfumes (mapped SKUs + anything in a perfume category + flagged). */
function hsp_perfume_ids() {
    static $ids = null;
    if ($ids !== null) return $ids;

    $ids = [];

    foreach (array_keys(hsp_map()) as $sku) {
        $pid = function_exists('wc_get_product_id_by_sku') ? wc_get_product_id_by_sku($sku) : 0;
        if ($pid) $ids[] = (int) $pid;
    }

    $term_ids = [];
    foreach (array_keys(hsp_cats()) as $slug) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term && !is_wp_error($term)) $term_ids[] = (int) $term->term_id;
    }

    if ($term_ids) {
        $found = get_posts([
            'post_type'      => 'product',
            'numberposts'    => -1,
            'fields'         => 'ids',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'tax_query'      => [[
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $term_ids,
            ]],
        ]);
        $ids = array_merge($ids, $found);
    }

    $flagged = get_posts([
        'post_type'   => 'product',
        'numberposts' => -1,
        'fields'      => 'ids',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'meta_key'    => '_hsp_perfume',
        'meta_value'  => '1',
    ]);
    $ids = array_merge($ids, $flagged);

    $ids = array_values(array_unique(array_map('intval', $ids)));
    return $ids;
}

/** Published/draft/trashed products that are NOT perfume. */
function hsp_non_perfume_ids() {
    $perfume = hsp_perfume_ids();
    $all = get_posts([
        'post_type'   => 'product',
        'numberposts' => -1,
        'fields'      => 'ids',
        'post_status' => ['publish', 'draft', 'pending', 'private', 'trash'],
    ]);

    $out = [];
    foreach ($all as $id) {
        $id = (int) $id;
        if (in_array($id, $perfume, true)) continue;

        $name = get_the_title($id);
        // Never delete something that looks like a fragrance, even if it is not mapped yet.
        if (preg_match('/perfume|parfum|fragrance|attar|oud|cologne|body\s*mist|eau\s*de|tester/i', $name)) continue;

        $out[] = $id;
    }
    return $out;
}

/* -------------------------------------------------------------------------
 * 2. Setup / cleanup actions
 * ---------------------------------------------------------------------- */

function hsp_setup() {
    if (!taxonomy_exists('product_cat')) {
        return ['ok' => false, 'msg' => 'WooCommerce is not active, so categories could not be created.'];
    }

    $term_ids = [];
    foreach (hsp_cats() as $slug => $name) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if (!$term) $term = get_term_by('name', $name, 'product_cat');
        if (!$term) $term = wp_insert_term($name, 'product_cat', ['slug' => $slug]);
        if (is_wp_error($term) || !$term) continue;
        $tid = is_object($term) ? (int) $term->term_id : (int) (is_array($term) ? $term['term_id'] : $term);
        $term_ids[$slug] = $tid;
        delete_term_meta($tid, 'hs_hidden');
    }

    foreach (hsp_tags() as $slug => $name) {
        $tag = get_term_by('slug', $slug, 'product_tag');
        if (!$tag) $tag = get_term_by('name', $name, 'product_tag');
        if (!$tag) wp_insert_term($name, 'product_tag', ['slug' => $slug]);
    }

    $assigned = 0;
    foreach (hsp_map() as $sku => $data) {
        $pid = function_exists('wc_get_product_id_by_sku') ? wc_get_product_id_by_sku($sku) : 0;
        if (!$pid) continue;

        $cats = [];
        foreach ((array) $data['cats'] as $slug) {
            if (!empty($term_ids[$slug])) $cats[] = $term_ids[$slug];
        }
        if ($cats) wp_set_object_terms($pid, $cats, 'product_cat', false);

        if (!empty($data['tags'])) {
            wp_set_object_terms($pid, (array) $data['tags'], 'product_tag', false);
        }

        update_post_meta($pid, '_hsp_perfume', 1);

        $product = wc_get_product($pid);
        if ($product) {
            $product->set_catalog_visibility('visible');
            $product->save();
        }
        $assigned++;
    }

    // Hide every old category (Fragrance, user, fashion, etc.) from the theme menus/rows.
    $keep = array_values($term_ids);
    $default = (int) get_option('default_product_cat');
    $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0]);
    $hidden = 0;
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $tid = (int) $term->term_id;
            if (in_array($tid, $keep, true) || $tid === $default) continue;
            update_term_meta($tid, 'hs_hidden', '1');
            $hidden++;
        }
    }

    update_option('hsp_setup_version', HSP_VERSION);

    return [
        'ok'  => true,
        'msg' => sprintf('Perfume categories ready. %d perfumes organised, %d old categories hidden.', $assigned, $hidden),
    ];
}

function hsp_hide_others($restore = false) {
    $ids = hsp_non_perfume_ids();
    $count = 0;

    foreach ($ids as $id) {
        if (get_post_status($id) === 'trash') continue;
        $product = wc_get_product($id);
        if (!$product) continue;

        if ($restore) {
            $product->set_catalog_visibility('visible');
            $product->save();
            delete_post_meta($id, '_hsp_hidden');
        } else {
            $product->set_catalog_visibility('hidden');
            $product->save();
            update_post_meta($id, '_hsp_hidden', 1);
        }
        $count++;
    }

    return $count;
}

function hsp_trash_others() {
    $ids = hsp_non_perfume_ids();
    $count = 0;
    foreach ($ids as $id) {
        if (get_post_status($id) === 'trash') continue;
        update_post_meta($id, '_hsp_hidden', 1);
        if (wp_trash_post($id)) $count++;
    }
    return $count;
}

function hsp_delete_others() {
    $ids = hsp_non_perfume_ids();
    $count = 0;
    foreach ($ids as $id) {
        if (wp_delete_post($id, true)) $count++;
    }
    return $count;
}

function hsp_restore_others() {
    $ids = get_posts([
        'post_type'   => 'product',
        'numberposts' => -1,
        'fields'      => 'ids',
        'post_status' => ['publish', 'draft', 'pending', 'private', 'trash'],
        'meta_key'    => '_hsp_hidden',
    ]);

    $count = 0;
    foreach ($ids as $id) {
        if (get_post_status($id) === 'trash') wp_untrash_post($id);
        $product = wc_get_product($id);
        if ($product) {
            $product->set_catalog_visibility('visible');
            $product->save();
        }
        delete_post_meta($id, '_hsp_hidden');
        $count++;
    }
    return $count;
}

function hsp_export_csv() {
    $ids = hsp_non_perfume_ids();
    $filename = 'hassence-non-perfume-backup-' . date('Y-m-d-His') . '.csv';

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'SKU', 'Name', 'Status', 'Price', 'Regular price', 'Sale price', 'Categories', 'Image URL', 'Description']);

    foreach ($ids as $id) {
        $product = wc_get_product($id);
        $cats = wp_get_post_terms($id, 'product_cat', ['fields' => 'names']);
        $image = $product && $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : '';

        fputcsv($out, [
            $id,
            $product ? $product->get_sku() : '',
            get_the_title($id),
            get_post_status($id),
            $product ? $product->get_price() : '',
            $product ? $product->get_regular_price() : '',
            $product ? $product->get_sale_price() : '',
            is_array($cats) ? implode(' | ', $cats) : '',
            $image,
            $product ? wp_strip_all_tags($product->get_description()) : '',
        ]);
    }

    fclose($out);
    exit;
}

/* -------------------------------------------------------------------------
 * 3. Activation + frontend assets
 * ---------------------------------------------------------------------- */

register_activation_hook(__FILE__, function () {
    update_option('hsp_needs_setup', 1);
});

add_action('init', function () {
    if (get_option('hsp_needs_setup')) {
        hsp_setup();
        delete_option('hsp_needs_setup');
    }
}, 60);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('hassence-perfume', HSP_URL . 'assets/perfume.css', [], HSP_VERSION);
    wp_enqueue_script('hassence-perfume', HSP_URL . 'assets/perfume.js', [], HSP_VERSION, true);
}, 99);

add_filter('body_class', function ($classes) {
    $classes[] = 'hassence-perfume';
    return $classes;
});

/* Perfume home page */
add_filter('template_include', function ($template) {
    if (is_front_page() && function_exists('hs_card') && function_exists('hs_q')) {
        $custom = HSP_DIR . 'templates/home.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}, 99);

/* Perfume copy in the theme's own blocks */
add_filter('theme_mod_hs_flash_text', function () {
    return "100% original perfumes\nCash on Delivery across Pakistan\nFree delivery on orders over Rs. 5,000\nEasypaisa / Raast accepted";
});
add_filter('theme_mod_hs_site_desc', function () {
    return 'Hassence is a perfume house in Karachi, Pakistan. Shop long-lasting men’s, women’s and unisex fragrances with Cash on Delivery and Easypaisa.';
});
add_filter('theme_mod_hs_wa_msg', function () {
    return 'Hello Hassence, I need help choosing a perfume.';
});
add_filter('theme_mod_hs_ship_note', function () {
    return 'Delivery across Pakistan. Courier charges are calculated at checkout. Cash on Delivery and Easypaisa / Raast QR accepted.';
});

/* Trust badges on the product page */
add_action('woocommerce_after_add_to_cart_button', function () {
    echo '<div class="hsp-trust-pdp">'
        . '<span>100% original</span>'
        . '<span>Cash on Delivery</span>'
        . '<span>Delivered across Pakistan</span>'
        . '</div>';
}, 25);

/* -------------------------------------------------------------------------
 * 4. Admin screen
 * ---------------------------------------------------------------------- */

add_action('admin_menu', function () {
    add_menu_page(
        'Perfume Mode',
        'Perfume Mode',
        'manage_options',
        'hassence-perfume',
        'hsp_admin_page',
        'dashicons-heart',
        56
    );
});

add_action('admin_init', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'hassence-perfume') return;
    if (!current_user_can('manage_options')) return;
    if (empty($_POST['hsp_action'])) return;

    check_admin_referer('hsp_admin');

    $action = sanitize_key($_POST['hsp_action']);
    $msg = '';

    switch ($action) {
        case 'setup':
            $result = hsp_setup();
            $msg = $result['msg'];
            break;

        case 'hide':
            $msg = hsp_hide_others() . ' non-perfume products hidden from the shop.';
            break;

        case 'trash':
            $msg = hsp_trash_others() . ' non-perfume products moved to Trash.';
            break;

        case 'delete':
            if (!isset($_POST['hsp_confirm']) || trim(wp_unslash($_POST['hsp_confirm'])) !== 'DELETE') {
                $msg = 'Nothing deleted. Type DELETE in the confirmation box first.';
            } else {
                $msg = hsp_delete_others() . ' non-perfume products deleted permanently.';
            }
            break;

        case 'restore':
            $msg = hsp_restore_others() . ' products restored.';
            break;

        case 'dokan':
            if (!function_exists('is_plugin_active')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            if (is_plugin_active('dokan-lite/dokan-lite.php')) {
                deactivate_plugins('dokan-lite/dokan-lite.php');
                $msg = 'Dokan Lite deactivated. The marketplace menus are gone.';
            } else {
                $msg = 'Dokan Lite is not active.';
            }
            break;

        case 'export':
            hsp_export_csv();
            break;
    }

    wp_safe_redirect(add_query_arg([
        'page'    => 'hassence-perfume',
        'hsp_msg' => $msg,
    ], admin_url('admin.php')));
    exit;
});

function hsp_admin_page() {
    if (!current_user_can('manage_options')) return;

    $perfume = hsp_perfume_ids();
    $others  = hsp_non_perfume_ids();
    $publish = (int) wp_count_posts('product')->publish;
    $dokan   = false;
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $dokan = is_plugin_active('dokan-lite/dokan-lite.php');

    $msg = isset($_GET['hsp_msg']) ? sanitize_text_field(wp_unslash($_GET['hsp_msg'])) : '';
    ?>
    <div class="wrap hsp-admin">
        <style>
            .hsp-admin{max-width:1000px}
            .hsp-admin h1{font-weight:800;letter-spacing:-.02em}
            .hsp-admin .hsp-sub{color:#666;margin:4px 0 22px}
            .hsp-admin .hsp-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:18px 0 24px}
            .hsp-admin .hsp-card{background:#fff;border:1px solid #e3ddd0;border-radius:12px;padding:16px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
            .hsp-admin .hsp-card b{display:block;font-size:26px;line-height:1.1;color:#111}
            .hsp-admin .hsp-card span{font-size:12px;color:#777;text-transform:uppercase;letter-spacing:.08em}
            .hsp-admin .hsp-card.gold{background:#0b0b0b;border-color:#2a2419}
            .hsp-admin .hsp-card.gold b{color:#d8b45a}
            .hsp-admin .hsp-card.gold span{color:#a89e8c}
            .hsp-admin .hsp-box{background:#fff;border:1px solid #e3ddd0;border-radius:12px;padding:18px 20px;margin:0 0 16px}
            .hsp-admin .hsp-box h2{margin:0 0 6px;font-size:16px}
            .hsp-admin .hsp-box p{margin:0 0 12px;color:#555}
            .hsp-admin .button.hsp-gold{background:#c8a24a;border-color:#c8a24a;color:#080808;font-weight:700}
            .hsp-admin .button.hsp-gold:hover{background:#d8b45a;border-color:#d8b45a;color:#080808}
            .hsp-admin .button.hsp-danger{color:#b32d2e;border-color:#b32d2e}
            .hsp-admin .button.hsp-danger:hover{background:#b32d2e;color:#fff}
            .hsp-admin input[type=text]{border:1px solid #ccc;border-radius:6px;padding:7px 10px;min-width:140px}
            .hsp-admin .hsp-list{max-height:260px;overflow:auto;border:1px solid #eee;border-radius:8px;padding:10px 14px;background:#fafafa;font-size:13px;line-height:1.8}
            .hsp-admin .hsp-ok{background:#edf7ed;border:1px solid #b7dfb7;color:#1e4620;padding:10px 14px;border-radius:8px;margin:12px 0}
            .hsp-admin .hsp-warn{background:#fff8e5;border:1px solid #f0d38a;color:#5c4400;padding:10px 14px;border-radius:8px;margin:12px 0}
            @media(max-width:782px){.hsp-admin .hsp-cards{grid-template-columns:repeat(2,1fr)}}
        </style>

        <h1>Hassence Perfume Mode</h1>
        <p class="hsp-sub">Black &amp; gold perfume storefront · Men / Women / Unisex · keeps your current theme, logo and fonts.</p>

        <?php if ($msg) : ?><div class="hsp-ok"><?php echo esc_html($msg); ?></div><?php endif; ?>

        <div class="hsp-cards">
            <div class="hsp-card gold"><b><?php echo count($perfume); ?></b><span>Perfumes</span></div>
            <div class="hsp-card"><b><?php echo count($others); ?></b><span>Non-perfume products</span></div>
            <div class="hsp-card"><b><?php echo $publish; ?></b><span>Published products</span></div>
            <div class="hsp-card"><b><?php echo $dokan ? 'On' : 'Off'; ?></b><span>Dokan marketplace</span></div>
        </div>

        <div class="hsp-box">
            <h2>1. Organise the perfume catalogue</h2>
            <p>Creates <b>Men’s Perfume</b>, <b>Women’s Perfume</b>, <b>Unisex Perfume</b>, <b>Oud &amp; Attar</b> and <b>Gift Sets</b>, then puts your 21 existing perfumes in the right places and hides the old categories.</p>
            <form method="post">
                <?php wp_nonce_field('hsp_admin'); ?>
                <button class="button button-primary hsp-gold" name="hsp_action" value="setup">Create categories &amp; organise perfumes</button>
            </form>
        </div>

        <div class="hsp-box">
            <h2>2. Clean up the non-perfume products</h2>
            <p>These are the clothing, furniture, toys and padel products. <b>Export a backup first</b> — it downloads a CSV with every product’s name, price, categories and image.</p>

            <form method="post" style="display:inline-block;margin-right:8px">
                <?php wp_nonce_field('hsp_admin'); ?>
                <button class="button" name="hsp_action" value="export">Export CSV backup</button>
            </form>

            <form method="post" style="display:inline-block;margin-right:8px">
                <?php wp_nonce_field('hsp_admin'); ?>
                <button class="button" name="hsp_action" value="hide">Hide them (reversible)</button>
            </form>

            <form method="post" style="display:inline-block;margin-right:8px" onsubmit="return confirm('Move all non-perfume products to Trash? You can restore them from Products > Trash.');">
                <?php wp_nonce_field('hsp_admin'); ?>
                <button class="button" name="hsp_action" value="trash">Move to Trash</button>
            </form>

            <div class="hsp-warn" style="margin-top:14px">
                <b>Permanent delete.</b> This cannot be undone. Type <code>DELETE</code> and press the red button only when you are sure.
                <form method="post" style="margin-top:8px" onsubmit="return confirm('Permanently delete every non-perfume product? This cannot be undone.');">
                    <?php wp_nonce_field('hsp_admin'); ?>
                    <input type="text" name="hsp_confirm" placeholder="Type DELETE" autocomplete="off">
                    <button class="button hsp-danger" name="hsp_action" value="delete">Delete permanently</button>
                </form>
            </div>

            <form method="post" style="margin-top:10px">
                <?php wp_nonce_field('hsp_admin'); ?>
                <button class="button" name="hsp_action" value="restore">Restore hidden / trashed products</button>
            </form>
        </div>

        <div class="hsp-box">
            <h2>3. Remove the marketplace</h2>
            <p>Dokan adds seller signup, vendor dashboards and payout menus. A single-brand perfume store does not need them.</p>
            <form method="post">
                <?php wp_nonce_field('hsp_admin'); ?>
                <button class="button" name="hsp_action" value="dokan" <?php echo $dokan ? '' : 'disabled'; ?>>Deactivate Dokan Lite</button>
            </form>
        </div>

        <?php if ($others) : ?>
        <div class="hsp-box">
            <h2>Products that will be removed (<?php echo count($others); ?>)</h2>
            <div class="hsp-list">
                <?php foreach ($others as $id) : ?>
                    <div>#<?php echo (int) $id; ?> — <?php echo esc_html(get_the_title($id)); ?> <em>(<?php echo esc_html(get_post_status($id)); ?>)</em></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="hsp-box">
            <h2>Shortcodes &amp; notes</h2>
            <p>The perfume home page is applied automatically to the front page. The old theme page builder, banners and product cards are still used, so your logo, fonts and colours stay familiar.</p>
            <p>After the cleanup, add new perfumes under <b>Products → Add New</b> and choose one of the perfume categories. New perfumes are never deleted by this plugin.</p>
        </div>
    </div>
    <?php
}
