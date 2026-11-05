<?php
/** Mobile app support: web app manifest + Android app (APK) download offer. */
if (!defined('ABSPATH')) exit;

add_action('init', function () {
 $p = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
 $mp = parse_url(home_url('/manifest.webmanifest'), PHP_URL_PATH);
 if ($p !== $mp) return;
 $a = get_template_directory_uri() . '/assets/';
 header('Content-Type: application/manifest+json; charset=utf-8');
 header('Cache-Control: public, max-age=3600');
 echo wp_json_encode([
  'name' => 'Hassence', 'short_name' => 'Hassence', 'description' => 'Shop fragrances, fashion, beauty and more at Hassence.',
  'id' => '/', 'start_url' => '/?source=app', 'scope' => '/', 'display' => 'standalone', 'orientation' => 'portrait',
  'background_color' => '#07080a', 'theme_color' => '#e0245e', 'lang' => 'en',
  'icons' => [
   ['src' => $a . 'icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
   ['src' => $a . 'icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
   ['src' => $a . 'icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
  ],
 ], JSON_UNESCAPED_SLASHES);
 exit;
}, 1);

add_action('wp_head', function () {
 echo '<link rel="manifest" href="' . esc_url(home_url('/manifest.webmanifest')) . '"><meta name="theme-color" content="#e0245e"><link rel="apple-touch-icon" href="' . esc_url(get_template_directory_uri() . '/assets/icon-192.png') . '">' . "\n";
}, 4);

add_action('customize_register', function ($c) {
 $c->add_section('hs_app', ['title' => 'Mobile app (APK)', 'priority' => 36, 'description' => 'Paste the full web address of your APK file, for example https://hassence.com/downloads/hassence.apk. While this is empty, the app popup and button stay hidden.']);
 $c->add_setting('hs_apk_url', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
 $c->add_control('hs_apk_url', ['label' => 'APK file address', 'section' => 'hs_app', 'type' => 'url']);
});

add_action('wp_footer', function () {
 if (function_exists('hs_is_co') && hs_is_co()) return;
 $apk = get_theme_mod('hs_apk_url', '');
 if (!$apk) return; ?>
<div class="app-pop" id="appPop" hidden role="dialog" aria-label="Download the Hassence app">
<button type="button" class="app-x" aria-label="Close">×</button>
<b>Get the Hassence app</b>
<p>Do you want to download the APK file for hassence.com?</p>
<div class="app-b"><a class="app-yes" href="<?php echo esc_url($apk); ?>" download>Yes, download</a><button type="button" class="app-no">No</button></div>
</div>
<script>
(function(){
var p=document.getElementById('appPop');if(!p)return;
var force=/[?&]apk=1/.test(location.search),ua=navigator.userAgent;
var inApp=matchMedia('(display-mode: standalone)').matches||document.referrer.indexOf('android-app://')===0||/;\s*wv\)/.test(ua);
if(inApp||(!force&&!/Android/i.test(ua)))return;
var K='hs_app_ask',seen=false;try{seen=!!localStorage.getItem(K)}catch(e){}
if(force||!seen)setTimeout(function(){p.hidden=false},2500);
function done(v){try{localStorage.setItem(K,v)}catch(e){}p.hidden=true}
p.querySelector('.app-yes').addEventListener('click',function(){done('yes')});
p.querySelector('.app-no').addEventListener('click',function(){done('no')});
p.querySelector('.app-x').addEventListener('click',function(){done('no')});
})();
</script>
<?php }, 50);
