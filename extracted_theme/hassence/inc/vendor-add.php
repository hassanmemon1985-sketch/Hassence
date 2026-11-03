<?php
/** Seller "Add product" page: phone photo -> store-size listing photo -> details -> listing. Shortcode: [hassence_add_product] */
if (!defined('ABSPATH')) exit;

function hs_is_vendor() {
 if (!is_user_logged_in()) return false;
 $u = wp_get_current_user();
 return current_user_can('manage_woocommerce') || current_user_can('dokandar') || array_intersect(['seller', 'vendor', 'shop_manager', 'administrator'], (array) $u->roles);
}
function hs_ai_dir() {
 $u = wp_upload_dir(); $d = $u['basedir'] . '/hs-ai';
 if (!is_dir($d)) { wp_mkdir_p($d); @file_put_contents($d . '/index.html', ''); }
 return $d;
}
function hs_load_img($path, $type) {
 if ($type === IMAGETYPE_JPEG) return @imagecreatefromjpeg($path);
 if ($type === IMAGETYPE_PNG) return @imagecreatefrompng($path);
 if ($type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) return @imagecreatefromwebp($path);
 return false;
}
function hs_fit($im, $max) {
 $w = imagesx($im); $h = imagesy($im);
 if (max($w, $h) <= $max) return $im;
 $s = $max / max($w, $h);
 $n = imagescale($im, (int) ($w * $s), (int) ($h * $s));
 return $n ? $n : $im;
}
function hs_trim($im) {
 $w = imagesx($im); $h = imagesy($im); $x0 = $w; $y0 = $h; $x1 = 0; $y1 = 0;
 for ($y = 0; $y < $h; $y += 4) for ($x = 0; $x < $w; $x += 4) {
  if (((imagecolorat($im, $x, $y) >> 24) & 127) < 100) { if ($x < $x0) $x0 = $x; if ($x > $x1) $x1 = $x; if ($y < $y0) $y0 = $y; if ($y > $y1) $y1 = $y; }
 }
 if ($x1 <= $x0 || $y1 <= $y0) return $im;
 $c = imagecrop($im, ['x' => $x0, 'y' => $y0, 'width' => $x1 - $x0 + 4, 'height' => $y1 - $y0 + 4]);
 if ($c) { imagealphablending($c, false); imagesavealpha($c, true); return $c; }
 return $im;
}
/* Optional AI background removal (remove.bg). Needs an API key in Appearance > Customize > AI product photo. */
function hs_removebg($im, $key) {
 ob_start(); imagejpeg($im, null, 92); $jpg = ob_get_clean();
 $b = '----hs' . wp_generate_password(12, false);
 $body = "--$b\r\nContent-Disposition: form-data; name=\"size\"\r\n\r\nauto\r\n";
 $body .= "--$b\r\nContent-Disposition: form-data; name=\"format\"\r\n\r\npng\r\n";
 $body .= "--$b\r\nContent-Disposition: form-data; name=\"image_file\"; filename=\"p.jpg\"\r\nContent-Type: image/jpeg\r\n\r\n" . $jpg . "\r\n--$b--\r\n";
 $r = wp_remote_post('https://api.remove.bg/v1.0/removebg', ['timeout' => 60, 'headers' => ['X-Api-Key' => $key, 'Content-Type' => 'multipart/form-data; boundary=' . $b], 'body' => $body]);
 if (is_wp_error($r) || wp_remote_retrieve_response_code($r) !== 200) return null;
 $img = @imagecreatefromstring(wp_remote_retrieve_body($r));
 if (!$img) return null;
 imagealphablending($img, false); imagesavealpha($img, true);
 return $img;
}
/* Store size 1000 x 1333 (3:4). */
function hs_compose($im, $cut) {
 $W = 1000; $H = 1333;
 $c = imagecreatetruecolor($W, $H);
 if ($cut) {
  for ($y = 0; $y < $H; $y++) { $g = 255 - (int) (14 * $y / $H); imageline($c, 0, $y, $W, $y, imagecolorallocate($c, $g, $g, $g)); }
  $im = hs_trim($im);
  $w = imagesx($im); $h = imagesy($im);
  $s = min(860 / $w, 980 / $h); $nw = (int) ($w * $s); $nh = (int) ($h * $s);
  $x = (int) (($W - $nw) / 2); $y = (int) (($H - $nh) / 2) - 20;
  for ($i = 0; $i < 8; $i++) {
   $col = imagecolorallocatealpha($c, 0, 0, 0, 122);
   imagefilledellipse($c, (int) ($W / 2), $y + $nh - 6, (int) ($nw * (0.95 - 0.07 * $i)), (int) (26 - $i * 2), $col);
  }
  imagecopyresampled($c, $im, $x, $y, 0, 0, $nw, $nh, $w, $h);
 } else {
  $w = imagesx($im); $h = imagesy($im);
  $s = max($W / $w, $H / $h); $sw = (int) ($W / $s); $sh = (int) ($H / $s);
  imagecopyresampled($c, $im, 0, 0, (int) (($w - $sw) / 2), (int) (($h - $sh) / 2), $W, $H, $sw, $sh);
  imagefilter($c, IMG_FILTER_BRIGHTNESS, 6);
  imagefilter($c, IMG_FILTER_CONTRAST, -8);
  imageconvolution($c, [[0, -1, 0], [-1, 5, -1], [0, -1, 0]], 1, 0);
 }
 return $c;
}

add_action('wp_ajax_hs_ai_photo', function () {
 check_ajax_referer('hs_vendor', 'nonce');
 if (!hs_is_vendor()) wp_send_json_error('Not allowed.');
 if (empty($_FILES['photo'])) wp_send_json_error('No photo received.');
 $f = $_FILES['photo'];
 if ($f['error'] || $f['size'] > 12 * 1024 * 1024) wp_send_json_error('Photo is too big (max 12 MB).');
 if (!function_exists('imagecreatetruecolor')) wp_send_json_error('Image tools (GD) are not enabled on the server.');
 $info = @getimagesize($f['tmp_name']);
 if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) wp_send_json_error('Please use a JPG, PNG or WEBP photo.');
 $src = hs_load_img($f['tmp_name'], $info[2]);
 if (!$src) wp_send_json_error('Could not read the photo.');
 if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
  $ex = @exif_read_data($f['tmp_name']);
  $o = isset($ex['Orientation']) ? (int) $ex['Orientation'] : 1;
  $a = [3 => 180, 6 => -90, 8 => 90];
  if (isset($a[$o])) { $r = imagerotate($src, $a[$o], 0); if ($r) $src = $r; }
 }
 $src = hs_fit($src, 1600);
 $cut = null; $key = trim((string) get_theme_mod('hs_removebg_key', ''));
 if ($key) $cut = hs_removebg($src, $key);
 $out = hs_compose($cut ? $cut : $src, (bool) $cut);
 foreach (glob(hs_ai_dir() . '/*.jpg') ?: [] as $old) { if (filemtime($old) < time() - DAY_IN_SECONDS) @unlink($old); }
 $name = wp_generate_password(20, false, false) . '.jpg';
 imagejpeg($out, hs_ai_dir() . '/' . $name, 90);
 $u = wp_upload_dir();
 wp_send_json_success(['file' => $name, 'url' => $u['baseurl'] . '/hs-ai/' . $name, 'mode' => $cut ? 'ai' : 'studio']);
});

add_action('wp_ajax_hs_ai_publish', function () {
 check_ajax_referer('hs_vendor', 'nonce');
 if (!hs_is_vendor()) wp_send_json_error('Not allowed.');
 $title = sanitize_text_field(wp_unslash(isset($_POST['title']) ? $_POST['title'] : ''));
 $desc = wp_kses_post(wp_unslash(isset($_POST['desc']) ? $_POST['desc'] : ''));
 $price = (float) wc_format_decimal(wp_unslash(isset($_POST['price']) ? $_POST['price'] : ''));
 $cat = (int) (isset($_POST['cat']) ? $_POST['cat'] : 0);
 $file = sanitize_text_field(wp_unslash(isset($_POST['file']) ? $_POST['file'] : ''));
 if (strlen($title) < 3) wp_send_json_error('Please enter the product name.');
 if ($price <= 0) wp_send_json_error('Please enter a price.');
 if (!$cat || !term_exists($cat, 'product_cat')) wp_send_json_error('Please choose a category.');
 if (!preg_match('/^[A-Za-z0-9]{20}\.jpg$/', $file) || !file_exists(hs_ai_dir() . '/' . $file)) wp_send_json_error('Photo expired. Please upload it again.');
 $live = current_user_can('manage_woocommerce');
 $p = new WC_Product_Simple();
 $p->set_name($title); $p->set_description($desc);
 $p->set_short_description(wp_trim_words(wp_strip_all_tags($desc), 30));
 $p->set_regular_price($price); $p->set_category_ids([$cat]);
 $p->set_status($live ? 'publish' : 'pending');
 $p->set_catalog_visibility('visible'); $p->set_manage_stock(false); $p->set_stock_status('instock');
 $id = $p->save();
 if (!$id) wp_send_json_error('Could not save the product.');
 require_once ABSPATH . 'wp-admin/includes/image.php';
 $up = wp_upload_dir();
 $dest = wp_unique_filename($up['path'], sanitize_title($title) . '.jpg');
 if (copy(hs_ai_dir() . '/' . $file, $up['path'] . '/' . $dest)) {
  $att = wp_insert_attachment(['post_mime_type' => 'image/jpeg', 'post_title' => $title, 'post_status' => 'inherit'], $up['path'] . '/' . $dest, $id);
  wp_update_attachment_metadata($att, wp_generate_attachment_metadata($att, $up['path'] . '/' . $dest));
  set_post_thumbnail($id, $att);
 }
 @unlink(hs_ai_dir() . '/' . $file);
 $sku = wc_get_product($id)->get_sku();
 wp_send_json_success(['msg' => $live ? 'Your product is now live.' : 'Product submitted. It will go live after approval.', 'sku' => $sku]);
});

add_shortcode('hassence_add_product', function () {
 if (!is_user_logged_in()) return '<div class="hs-vf"><p>Please log in to add products.</p><a class="btn-pill" href="' . esc_url(wc_get_page_permalink('myaccount')) . '">Log in / Register</a></div>';
 if (!hs_is_vendor()) return '<div class="hs-vf"><p>Your account is not a seller account yet. Please ask the store owner to approve you as a seller.</p></div>';
 wp_enqueue_script('hs-vendor', get_template_directory_uri() . '/vendor.js', [], wp_get_theme()->get('Version'), true);
 wp_localize_script('hs-vendor', 'HSV', ['ajax' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('hs_vendor')]);
 $cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'orderby' => 'term_id', 'exclude' => [(int) get_option('default_product_cat')]]);
 if (!is_array($cats)) $cats = [];
 ob_start(); ?>
<div class="hs-vf">
<h2>Add a product</h2>
<div class="vs" id="vs1"><h3>Step 1: Take or choose a photo</h3>
<input type="file" id="vf_photo" accept="image/*" capture="environment">
<p class="hint">Put the product on a plain surface in good light. We fit it to the store photo size.</p></div>
<div class="vs" id="vs2" hidden><h3>Step 2: Check the photo</h3>
<div id="vf_prev"></div><p id="vf_msg"></p>
<button type="button" id="vf_ok" class="btn-pill" disabled>✓ Use this photo</button>
<button type="button" id="vf_retry" class="btn-line">Try another photo</button></div>
<div class="vs" id="vs3" hidden><h3>Step 3: Product details</h3>
<label>Product name<input type="text" id="vf_title" maxlength="120"></label>
<label>Description<textarea id="vf_desc" rows="5"></textarea></label>
<label>Price (Rs.)<input type="number" id="vf_price" min="1" step="1" inputmode="numeric"></label>
<label>Category<select id="vf_cat"><option value="">Choose a category</option>
<?php foreach ($cats as $c) echo '<option value="' . (int) $c->term_id . '">' . esc_html($c->name) . '</option>'; ?>
</select></label>
<button type="button" id="vf_submit" class="btn-pill">List product</button>
<p id="vf_res"></p></div>
</div>
<?php return ob_get_clean();
});

add_action('customize_register', function ($c) {
 $c->add_section('hs_ai', ['title' => 'AI product photo', 'priority' => 33, 'description' => 'Optional. Paste a remove.bg API key to remove photo backgrounds. Leave empty to only fit and brighten photos.']);
 $c->add_setting('hs_removebg_key', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
 $c->add_control('hs_removebg_key', ['label' => 'remove.bg API key', 'section' => 'hs_ai', 'type' => 'text']);
});
add_action('init', function () {
 if (get_option('hs_addpage_v1')) return;
 wp_insert_post(['post_title' => 'Add Product', 'post_name' => 'add-product', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '[hassence_add_product]']);
 update_option('hs_addpage_v1', 1);
}, 25);
