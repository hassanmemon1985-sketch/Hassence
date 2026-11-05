<?php
/** Currency helper: shows approximate prices in USD, AED, SAR, GBP, EUR. Store and checkout stay in PKR. */
if (!defined('ABSPATH')) exit;

function hs_cur_defs() { return ['USD' => ['US$', 'US dollar'], 'AED' => ['AED', 'UAE dirham'], 'SAR' => ['SAR', 'Saudi riyal'], 'GBP' => ['£', 'British pound'], 'EUR' => ['€', 'Euro']]; }
/* Rates are "PKR for 1 unit". The numbers below are rough starting values: update them. */
function hs_fx_manual() {
 $d = ['USD' => 280, 'AED' => 76, 'SAR' => 75, 'GBP' => 370, 'EUR' => 325];
 foreach (preg_split('/\r\n|\r|\n/', (string) get_theme_mod('hs_fx_rates', '')) as $l) {
  if (strpos($l, '=') === false) continue;
  list($a, $b) = array_map('trim', explode('=', $l, 2)); $a = strtoupper($a);
  if (isset($d[$a]) && is_numeric($b) && (float) $b > 0) $d[$a] = (float) $b;
 }
 return $d;
}
function hs_fx_rates() {
 $r = hs_fx_manual();
 if (get_theme_mod('hs_fx_auto', false)) { $a = get_option('hs_fx_auto', []); if (!empty($a['rates'])) foreach ($a['rates'] as $k => $v) if (isset($r[$k]) && $v > 0) $r[$k] = (float) $v; }
 return $r;
}
add_action('init', function () { if (!wp_next_scheduled('hs_fx_update')) wp_schedule_event(time() + 900, 'daily', 'hs_fx_update'); });
add_action('hs_fx_update', function () {
 if (!get_theme_mod('hs_fx_auto', false)) return;
 $res = wp_remote_get('https://open.er-api.com/v6/latest/PKR', ['timeout' => 15]);
 if (is_wp_error($res)) return;
 $j = json_decode(wp_remote_retrieve_body($res), true);
 if (empty($j['rates'])) return;
 $o = []; foreach (hs_cur_defs() as $k => $d) { if (!empty($j['rates'][$k]) && $j['rates'][$k] > 0) $o[$k] = round(1 / $j['rates'][$k], 4); }
 if ($o) update_option('hs_fx_auto', ['rates' => $o, 'time' => time()], false);
});
add_action('customize_register', function ($c) {
 $c->add_section('hs_fx', ['title' => 'Currency converter', 'priority' => 38, 'description' => 'Customers can see approximate prices in other currencies. Orders are always charged in PKR. Rates mean PKR for 1 unit, one per line, for example USD=280.']);
 $c->add_setting('hs_fx_rates', ['default' => '', 'sanitize_callback' => 'sanitize_textarea_field']);
 $c->add_control('hs_fx_rates', ['label' => 'Rates (USD, AED, SAR, GBP, EUR)', 'section' => 'hs_fx', 'type' => 'textarea']);
 $c->add_setting('hs_fx_auto', ['default' => false, 'sanitize_callback' => 'rest_sanitize_boolean']);
 $c->add_control('hs_fx_auto', ['label' => 'Update the rates automatically every day', 'section' => 'hs_fx', 'type' => 'checkbox']);
});
function hs_cur_select() {
 echo '<select id="hsCur" class="cursel" aria-label="Show prices in"><option value="PKR">PKR</option>';
 foreach (hs_cur_defs() as $k => $d) echo '<option value="' . esc_attr($k) . '">' . esc_html($k) . '</option>';
 echo '</select>';
}
add_filter('woocommerce_get_price_html', function ($html, $p) {
 if (is_admin()) return $html;
 $v = (float) $p->get_price(); if ($p->is_type('variable')) $v = (float) $p->get_variation_price('min');
 if ($v <= 0) return $html;
 $reg = (float) $p->get_regular_price(); $reg = ($p->is_on_sale() && !$p->is_type('variable') && $reg > $v) ? $reg : 0;
 return '<span class="pk">' . $html . '</span><span class="fx" data-pkr="' . esc_attr($v) . '" data-reg="' . esc_attr($reg) . '"></span>';
}, 20, 2);
add_action('wp_footer', function () {
 $sym = []; foreach (hs_cur_defs() as $k => $d) $sym[$k] = $d[0]; ?>
<script>
(function(){var R=<?php echo wp_json_encode(hs_fx_rates()); ?>,S=<?php echo wp_json_encode($sym); ?>,K='hs_cur';
function cur(){try{return localStorage.getItem(K)||'PKR'}catch(e){return 'PKR'}}
function f(v,c){return S[c]+' '+(v/R[c]).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}
function apply(){var c=cur(),sel=document.getElementById('hsCur'),on=(c!=='PKR'&&!!R[c]);if(sel)sel.value=c;document.body.classList.toggle('cur-conv',on);
 document.querySelectorAll('.fx[data-pkr]').forEach(function(e){if(!on){e.innerHTML='';return}var v=+e.dataset.pkr,g=+e.dataset.reg||0;e.innerHTML=(g>v)?'<del>'+f(g,c)+'</del> <ins>'+f(v,c)+'</ins>':f(v,c)})}
document.addEventListener('change',function(e){if(e.target.id==='hsCur'){try{localStorage.setItem(K,e.target.value)}catch(x){}apply()}});
apply();if(window.jQuery)jQuery(document.body).on('updated_checkout updated_cart_totals',apply);
})();
</script>
<?php }, 60);
