<?php
if(!defined('ABSPATH'))exit;
add_action('after_setup_theme',function(){
 add_theme_support('title-tag');add_theme_support('post-thumbnails');
 add_theme_support('custom-logo',['height'=>60,'width'=>220,'flex-width'=>true,'flex-height'=>true]);
 add_theme_support('woocommerce');
 add_theme_support('wc-product-gallery-zoom');add_theme_support('wc-product-gallery-lightbox');add_theme_support('wc-product-gallery-slider');
 add_theme_support('html5',['search-form','gallery','caption']);
 register_nav_menus(['primary'=>'Primary Menu']);
});
add_action('wp_enqueue_scripts',function(){
 $v=wp_get_theme()->get('Version');
 wp_enqueue_style('hassence',get_stylesheet_uri(),[],$v);
 wp_enqueue_script('hassence-js',get_template_directory_uri().'/main.js',[],$v,true);
});
add_action('init',function(){
 remove_action('woocommerce_before_main_content','woocommerce_output_content_wrapper',10);
 remove_action('woocommerce_after_main_content','woocommerce_output_content_wrapper_end',10);
 remove_action('woocommerce_sidebar','woocommerce_get_sidebar',10);
 add_action('woocommerce_before_main_content',function(){echo '<main class="wrap shop">';},10);
 add_action('woocommerce_after_main_content',function(){echo '</main>';},10);
});
add_filter('woocommerce_add_to_cart_fragments',function($f){
 $f['sup.cartcount']='<sup class="cartcount">'.WC()->cart->get_cart_contents_count().'</sup>';return $f;
});
function hs_emoji(){static $e=['👗','👜','👠','🧥','🎧','📱','🧸','💄'];static $i=0;return $e[$i++%8];}
function hs_slides(){
 $shop=function_exists('wc_get_page_permalink')?wc_get_page_permalink('shop'):home_url('/');
 $out=[];
 $bn=get_posts(['post_type'=>'hs_banner','numberposts'=>10,'orderby'=>'menu_order date','order'=>'ASC','post_status'=>'publish']);
 foreach($bn as $b){$img=get_the_post_thumbnail_url($b,'full');if($img){$lk=get_post_meta($b->ID,'_hs_link',true);$out[]=['img',$img,$lk?$lk:$shop,$b->post_title,get_post_meta($b->ID,'_hs_mob',true)];}}
 if($out)return $out;
 return apply_filters('hassence_slides',[
  ['linear-gradient(120deg,#111,#e0245e)','Mega Sale<br>Up to 70% off','New styles dropped daily.','SHOP NOW',$shop,'👗'],
  ['linear-gradient(120deg,#4338ca,#06b6d4)','Tech Fest','Phones, buds &amp; smart watches.','EXPLORE',$shop,'🎧'],
  ['linear-gradient(120deg,#047857,#a3e635)','Kids Corner','Comfy, colorful &amp; affordable.','BROWSE',$shop,'🧸'],
  ['linear-gradient(120deg,#be185d,#fb923c)','Beauty Week','Great deals on beauty.','GET GLAM',$shop,'💄'],
 ]);
}
function hs_get_cats($all=false){
 $a=['taxonomy'=>'product_cat','hide_empty'=>false,'parent'=>0,'orderby'=>'term_id','order'=>'ASC','exclude'=>[(int)get_option('default_product_cat')]];
 if(!$all)$a['meta_query']=[['key'=>'hs_hidden','compare'=>'NOT EXISTS']];
 $t=get_terms($a);return is_array($t)?$t:[];
}
function hs_catdd(){
 $cats=hs_get_cats();
 echo '<div class="catdd"><button type="button" class="catbtn" aria-haspopup="true" aria-expanded="false">☰ Categories ▾</button><div class="catmenu">';
 echo '<a class="ddit" href="'.esc_url(home_url('/')).'"><span class="ci">🏠</span><span>Home</span></a>';
 foreach($cats as $c){
  $tid=get_term_meta($c->term_id,'thumbnail_id',true);
  echo '<a class="ddit" href="'.esc_url(get_term_link($c)).'"><span class="ci">'.($tid?wp_get_attachment_image($tid,'thumbnail'):hs_cat_icon($c)).'</span><span>'.esc_html($c->name).'</span></a>';
 }
 echo '</div></div>';
}
function hs_nav(){
 if(function_exists('is_account_page')&&is_account_page())return;
 echo '<header><nav><div class="wrap">';
 hs_catdd();
 echo '</div></nav></header>';
}
function hs_q($a=[]){return function_exists('wc_get_products')?wc_get_products(array_merge(['status'=>'publish','limit'=>8],$a)):[];}
function hs_card($p){
 $id=$p->get_id();
 $terms=wp_get_post_terms($id,'product_cat',['fields'=>'slugs']);
 $badge='';
 if($p->is_on_sale()){
  $r=(float)$p->get_regular_price();$s=(float)$p->get_sale_price();
  $badge=($p->is_type('simple')&&$r>0&&$s>0)?'<span class="badge">-'.round((1-$s/$r)*100).'%</span>':'<span class="badge">SALE</span>';
 }
 $badge.=hs_prog_badge($id);
 $tt=get_the_terms($id,'product_cat');$ic=($tt&&!is_wp_error($tt))?hs_cat_icon($tt[0]):hs_emoji();
 $img=$p->get_image_id()?wp_get_attachment_image($p->get_image_id(),'woocommerce_thumbnail'):'<span class="ph">'.$ic.'</span>';
 $dc=$p->get_date_created();
 echo '<div class="p" data-cat="'.esc_attr(implode(' ',$terms)).'" data-name="'.esc_attr(strtolower($p->get_name())).'" data-price="'.esc_attr((float)$p->get_price()).'" data-pop="'.(int)$p->get_total_sales().'" data-date="'.($dc?$dc->getTimestamp():0).'"><div class="img"><a class="imga" href="'.esc_url($p->get_permalink()).'">'.$img.'</a>'.$badge;
 if($p->is_purchasable()&&$p->is_in_stock()&&get_post_meta($id,'_hs_sizes',true)){
  echo '<a class="cart" href="'.esc_url($p->get_permalink()).'" aria-label="Choose a size">+</a>';
 }elseif($p->is_purchasable()&&$p->is_in_stock()){
  printf('<a href="%s" data-quantity="1" data-product_id="%d" data-product_sku="%s" class="cart add_to_cart_button %s product_type_%s" aria-label="Add to cart" rel="nofollow">+</a>',esc_url($p->add_to_cart_url()),$id,esc_attr($p->get_sku()),$p->supports('ajax_add_to_cart')?'ajax_add_to_cart':'',esc_attr($p->get_type()));
 }
 echo '</div><div class="nm"><a href="'.esc_url($p->get_permalink()).'">'.esc_html($p->get_name()).'</a></div><div class="pr">'.$p->get_price_html().'</div>';
 if($p->get_average_rating())echo '<div class="rt">'.wc_get_rating_html($p->get_average_rating()).'</div>';
 echo '</div>';
}
function hs_deal($title,$args){
 $items=hs_q(array_merge(['limit'=>2],$args));
 echo '<div class="deal"><h5>'.esc_html($title).' <small>›</small></h5><div class="dp">';
 foreach($items as $p){
  $img=$p->get_image_id()?wp_get_attachment_image($p->get_image_id(),'woocommerce_thumbnail'):hs_emoji();
  echo '<a class="dpi" href="'.esc_url($p->get_permalink()).'"><div class="im">'.$img.'</div><b>'.$p->get_price_html().'</b></a>';
 }
 echo '</div></div>';
}

add_action('init',function(){
 register_post_type('hs_banner',['labels'=>['name'=>'Banners','singular_name'=>'Banner','add_new'=>'Add new banner','add_new_item'=>'Add new banner','edit_item'=>'Edit banner','all_items'=>'All banners'],'public'=>false,'show_ui'=>true,'menu_icon'=>'dashicons-images-alt2','menu_position'=>25,'supports'=>['title','thumbnail','page-attributes']]);
});
add_action('add_meta_boxes',function(){add_meta_box('hs_link','Banner link','hs_link_box','hs_banner','normal');});
function hs_link_box($post){
 wp_nonce_field('hs_link','hs_link_n');
 echo '<p>Link when the banner is clicked (full address), for example https://hassence.com/shop/</p><input type="url" name="hs_link" style="width:100%" value="'.esc_attr(get_post_meta($post->ID,'_hs_link',true)).'">';
 echo '<p><b>Phone banner (optional):</b> paste the File URL of a phone-size picture (best 1200 x 800 px). Upload it in Media, click it, and copy "File URL".</p><input type="url" name="hs_mob" style="width:100%" value="'.esc_attr(get_post_meta($post->ID,'_hs_mob',true)).'">';
 echo '<p><b>Banner picture:</b> use the <b>Featured image</b> box on the right. Best size 1920 x 600 px. Keep the top 130 px free of text.<br><b>Order:</b> use the Order number under Page Attributes. The smallest number shows first.</p>';
}
add_action('save_post_hs_banner',function($id){
 if(!isset($_POST['hs_link_n'])||!wp_verify_nonce($_POST['hs_link_n'],'hs_link')||!current_user_can('edit_post',$id))return;
 update_post_meta($id,'_hs_link',esc_url_raw(isset($_POST['hs_link'])?$_POST['hs_link']:''));
 update_post_meta($id,'_hs_mob',esc_url_raw(isset($_POST['hs_mob'])?$_POST['hs_mob']:''));
});
add_action('customize_register',function($c){
 $c->add_section('hs_flash',['title'=>'Flash sale bar','priority'=>30]);
 $c->add_setting('hs_flash_text',['default'=>"⚡ FLASH SALE – up to 70% OFF\n🚚 Shipping or courier charges may apply according to the product\n💵 Cash on Delivery\n🎁 New arrivals every day",'sanitize_callback'=>'sanitize_textarea_field']);
 $c->add_control('hs_flash_text',['label'=>'Messages (one per line)','section'=>'hs_flash','type'=>'textarea']);
});

if (class_exists('WC_Payment_Gateway')) { require_once get_template_directory() . '/inc/easypaisa-gateway.php'; }

add_action('pre_get_posts',function($q){
 if(is_admin()||!$q->is_main_query()||empty($_GET['hs_sale'])||!function_exists('wc_get_product_ids_on_sale'))return;
 if($q->is_post_type_archive('product')||$q->is_tax('product_cat'))$q->set('post__in',array_merge([0],wc_get_product_ids_on_sale()));
});

add_action('init',function(){
 if(get_option('hs_cats_v1')||!taxonomy_exists('product_cat'))return;
 foreach(['Electronic Accessories','TV & Home Appliances','Health & Beauty','Mother & Baby','Electronic Devices','Groceries & Pets','Home & Lifestyle',"Women's Fashion","Men's Fashion",'Watches, Bags & Jewellery','Sports & Outdoor','Automotive & Motorbike'] as $n){
  if(!term_exists($n,'product_cat'))wp_insert_term($n,'product_cat');
 }
 update_option('hs_cats_v1',1);
},20);

function hs_cat_icon($t){
 $n=strtolower(is_object($t)?$t->name:(string)$t);
 $map=['daraz'=>'⚡','tv'=>'📺','appliance'=>'📺','watch'=>'⌚','jewel'=>'💍','accessor'=>'🎧','device'=>'📱','beauty'=>'💄','health'=>'💄','baby'=>'🍼','mother'=>'🍼','grocer'=>'🛒','pet'=>'🐾','lifestyle'=>'🛋️','home'=>'🛋️','women'=>'👗','men'=>'👔','sport'=>'⚽','outdoor'=>'⚽','motor'=>'🏍️','auto'=>'🏍️','bag'=>'👜'];
 foreach($map as $k=>$e){if(strpos($n,$k)!==false)return $e;}
 return '🛍️';
}
function hs_wa_link($msg=''){
 $n=preg_replace('/\D/','',get_theme_mod('hs_wa_num','923333848131'));
 $m=$msg?$msg:get_theme_mod('hs_wa_msg','Hello Hassence, I want to place an order.');
 return 'https://wa.me/'.$n.'?text='.rawurlencode($m);
}
add_action('customize_register',function($c){
 $c->add_section('hs_wa',['title'=>'WhatsApp chat','priority'=>31]);
 $c->add_setting('hs_wa_num',['default'=>'923333848131','sanitize_callback'=>'sanitize_text_field']);
 $c->add_control('hs_wa_num',['label'=>'WhatsApp number with country code, digits only (example 923001234567)','section'=>'hs_wa','type'=>'text']);
 $c->add_setting('hs_wa_msg',['default'=>'Hello Hassence, I want to place an order.','sanitize_callback'=>'sanitize_text_field']);
 $c->add_control('hs_wa_msg',['label'=>'First message','section'=>'hs_wa','type'=>'text']);
});

add_action('init',function(){
 foreach([['woocommerce_before_shop_loop_item','woocommerce_template_loop_product_link_open',10],['woocommerce_before_shop_loop_item_title','woocommerce_show_product_loop_sale_flash',10],['woocommerce_before_shop_loop_item_title','woocommerce_template_loop_product_thumbnail',10],['woocommerce_shop_loop_item_title','woocommerce_template_loop_product_title',10],['woocommerce_after_shop_loop_item_title','woocommerce_template_loop_rating',5],['woocommerce_after_shop_loop_item_title','woocommerce_template_loop_price',10],['woocommerce_after_shop_loop_item','woocommerce_template_loop_product_link_close',5],['woocommerce_after_shop_loop_item','woocommerce_template_loop_add_to_cart',10]] as $r)remove_action($r[0],$r[1],$r[2]);
 add_action('woocommerce_before_shop_loop_item',function(){global $product;if($product)hs_card($product);},10);
});
add_filter('woocommerce_product_single_add_to_cart_text',function(){return '+ Add to cart';});

function hs_wa_svg($s=24){
 return '<svg class="wasvg" width="'.(int)$s.'" height="'.(int)$s.'" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
}

/* Shipping note + courier charges (admin editable) */
function hs_ship_note(){return get_theme_mod('hs_ship_note','Shipping or courier charges may apply according to the product.');}
add_action('customize_register',function($c){
 $c->add_section('hs_ship',['title'=>'Shipping note','priority'=>32]);
 $c->add_setting('hs_ship_note',['default'=>'Shipping or courier charges may apply according to the product.','sanitize_callback'=>'sanitize_textarea_field']);
 $c->add_control('hs_ship_note',['label'=>'Shipping / courier message shown on product, cart and checkout pages','section'=>'hs_ship','type'=>'textarea']);
});
add_action('woocommerce_product_options_general_product_data',function(){
 woocommerce_wp_text_input(['id'=>'_hs_ship','label'=>'Courier charge override (Rs.)','description'=>'Optional. Leave empty to use the default rules (Appearance > Customize > Courier charges). One charge per order: if the cart has several products, the highest charge applies once.','desc_tip'=>true,'type'=>'number','custom_attributes'=>['step'=>'1','min'=>'0']]);
});
add_action('woocommerce_process_product_meta',function($id){
 if(isset($_POST['_hs_ship']))update_post_meta($id,'_hs_ship',$_POST['_hs_ship']===''?'':wc_format_decimal(wp_unslash($_POST['_hs_ship'])));
});
add_action('woocommerce_before_cart',function(){echo '<p class="ship-note">🚚 '.esc_html(hs_ship_note()).'</p>';});
add_action('woocommerce_before_checkout_form',function(){echo '<p class="ship-note">🚚 '.esc_html(hs_ship_note()).'</p>';},5);

if (class_exists('WooCommerce')) { require_once get_template_directory() . '/inc/sku.php'; require_once get_template_directory() . '/inc/vendor-add.php'; }

/* ---------- Category visibility (show / hide from the website) ---------- */
add_action('product_cat_add_form_fields',function(){echo '<div class="form-field"><input type="hidden" name="hs_hidden_present" value="1"><label><input type="checkbox" name="hs_hidden" value="1"> Hide this category on the website (menus, dropdown and tabs)</label></div>';});
add_action('product_cat_edit_form_fields',function($t){$h=get_term_meta($t->term_id,'hs_hidden',true);echo '<tr class="form-field"><th>Website visibility</th><td><input type="hidden" name="hs_hidden_present" value="1"><label><input type="checkbox" name="hs_hidden" value="1" '.checked($h,'1',false).'> Hide this category on the website (menus, dropdown and tabs)</label></td></tr>';});
function hs_save_hidden($id){
 if(!isset($_POST['hs_hidden_present'])||!current_user_can('manage_product_terms'))return;
 if(!empty($_POST['hs_hidden']))update_term_meta($id,'hs_hidden','1');else delete_term_meta($id,'hs_hidden');
}
add_action('created_product_cat','hs_save_hidden');add_action('edited_product_cat','hs_save_hidden');

/* ---------- Currency label ---------- */
add_filter('woocommerce_currency_symbol',function($s,$c){return $c==='PKR'?'PKR&nbsp;':$s;},10,2);

/* ---------- Product page ---------- */
add_action('init',function(){
 remove_action('woocommerce_single_product_summary','woocommerce_template_single_excerpt',20);
 remove_action('woocommerce_single_product_summary','woocommerce_template_single_meta',40);
 remove_action('woocommerce_single_product_summary','woocommerce_template_single_sharing',50);
 remove_action('woocommerce_after_single_product_summary','woocommerce_output_product_data_tabs',10);
});
function hs_is_frag($p){
 $s=strtolower($p->get_name().' '.wp_strip_all_tags(wc_get_product_category_list($p->get_id())));
 $w=array_filter(array_map('trim',explode(',',strtolower(get_theme_mod('hs_frag_words','fragrance,perfume,parfum,attar,cologne,scent,body spray,eau de')))));
 foreach($w as $k){if($k!==''&&strpos($s,$k)!==false)return true;}
 return false;
}
function hs_notes($p){
 $frag=hs_is_frag($p);
 $d=$frag?"Premium scent at an affordable price\nAccurate scent profile\nFast delivery":"Premium quality at an affordable price\nAccurate product details\nFast delivery";
 $l=preg_split('/\r\n|\r|\n/',get_theme_mod($frag?'hs_notes_frag':'hs_notes_gen',$d));
 return array_values(array_filter(array_map('trim',$l)));
}
add_action('woocommerce_single_product_summary',function(){
 global $product;if(!$product)return;
 echo '<div class="pdp-cat">'.wc_get_product_category_list($product->get_id(),', ').'</div>';
},4);
add_action('woocommerce_single_product_summary',function(){
 global $product;if($product&&function_exists('hs_is_aff')&&hs_is_aff($product->get_id()))return;
 echo '<p class="pdp-shiplabel">'.esc_html(get_theme_mod('hs_ship_label','Shipping calculated at checkout.')).'</p>';
},11);
add_action('woocommerce_before_add_to_cart_quantity',function(){
 global $product;if($product&&!$product->is_sold_individually())echo '<label class="qty-l">Quantity</label>';
});
add_action('woocommerce_after_add_to_cart_quantity',function(){
 global $product;if(!$product)return;
 echo '<ul class="pdp-notes">';foreach(hs_notes($product) as $n)echo '<li>✓ '.esc_html($n).'</li>';echo '</ul>';
});
add_action('woocommerce_after_add_to_cart_button',function(){
 global $product;if(!$product||$product->is_type('external'))return;
 $u=esc_url(add_query_arg('hs_buy_now',1,get_permalink($product->get_id())));
 echo '<button type="submit" class="buy-now"'.($product->is_type('simple')?' name="add-to-cart" value="'.(int)$product->get_id().'"':'').' formaction="'.$u.'">Buy now</button>';
});
add_filter('woocommerce_add_to_cart_redirect',function($u){return !empty($_REQUEST['hs_buy_now'])?wc_get_checkout_url():$u;});
add_action('woocommerce_single_product_summary',function(){
 global $product;if(!$product)return;
 $d=$product->get_description()?$product->get_description():$product->get_short_description();
 $si=get_theme_mod('hs_ship_info','Courier charges are added at checkout, based on the product. One courier charge applies per delivery address. We deliver across Pakistan by courier. Cash on Delivery and Easypaisa QR payment are available.');
 echo '<div class="pdp-acc">';
 echo '<details><summary>Description</summary><div class="acc-b">'.($d?wpautop(wp_kses_post($d)):'<p>No description yet.</p>').'</div></details>';
 echo '<details><summary>Shipping information</summary><div class="acc-b"><p>'.esc_html($si).'</p></div></details>';
 $can=hs_can_review($product->get_id());
 if($product->get_review_count()>0||$can){echo '<details id="reviews-acc"><summary>Reviews</summary><div class="acc-b">';comments_template();if(!$can)echo '<p class="rv-note">Only customers who bought this product can write a review.</p>';echo '</div></details>';}
 echo '</div>';
},60);
add_action('woocommerce_single_product_summary',function(){
 global $product;if(!$product)return;
 $u=get_permalink($product->get_id());$t=$product->get_name().' - Hassence';
 $e=rawurlencode($u);$x=rawurlencode($t);
 echo '<div class="share" data-url="'.esc_url($u).'" data-title="'.esc_attr($t).'"><button type="button" class="share-btn">↗ Share</button><div class="share-menu">';
 echo '<a target="_blank" rel="noopener" href="https://wa.me/?text='.$x.'%20'.$e.'">WhatsApp</a>';
 echo '<a target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u='.$e.'">Facebook</a>';
 echo '<a target="_blank" rel="noopener" href="https://t.me/share/url?url='.$e.'&text='.$x.'">Telegram</a>';
 echo '<a target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url='.$e.'&text='.$x.'">X</a>';
 echo '<a href="mailto:?subject='.$x.'&body='.$e.'">Email</a>';
 echo '<button type="button" class="share-copy">Copy link</button></div></div>';
},70);
add_action('customize_register',function($c){
 $c->add_section('hs_pdp',['title'=>'Product page text','priority'=>34]);
 $f=[['hs_ship_label','Text under the price','text','Shipping calculated at checkout.'],
 ['hs_notes_frag','3 notes for perfume / fragrance products (one per line)','textarea',"Premium scent at an affordable price\nAccurate scent profile\nFast delivery"],
 ['hs_notes_gen','3 notes for all other products (one per line)','textarea',"Premium quality at an affordable price\nAccurate product details\nFast delivery"],
 ['hs_ship_info','Shipping information dropdown text','textarea','Courier charges are added at checkout, based on the product. One courier charge applies per delivery address. We deliver across Pakistan by courier. Cash on Delivery and Easypaisa QR payment are available.']];
 foreach($f as $x){$c->add_setting($x[0],['default'=>$x[3],'sanitize_callback'=>'sanitize_textarea_field']);$c->add_control($x[0],['label'=>$x[1],'section'=>'hs_pdp','type'=>$x[2]]);}
});

/* ---------- Facebook / WhatsApp link previews (Open Graph) ---------- */
add_action('wp_head',function(){
 if(defined('WPSEO_VERSION')||class_exists('RankMath'))return;
 $site=get_bloginfo('name');$url=home_url('/');$title=$site;$desc=get_theme_mod('hs_site_desc','Hassence is an online store in Pakistan for fragrances, fashion, beauty, electronics and more. Shop at great prices with Cash on Delivery and Easypaisa, delivered across Pakistan.');
 $img=get_template_directory_uri().'/assets/og-default.jpg';$type='website';$extra='';
 if(function_exists('is_product')&&is_product()){
  $p=wc_get_product(get_queried_object_id());
  if($p){
   $title=$p->get_name().' - '.$site;$url=get_permalink($p->get_id());$type='product';
   $d=wp_strip_all_tags($p->get_short_description()?$p->get_short_description():$p->get_description());
   $desc=$d?wp_trim_words($d,30,'...'):'Buy '.$p->get_name().' on '.$site.'.';
   $sd=get_post_meta($p->get_id(),'_hs_seo_desc',true);if($sd)$desc=$sd;
   if($p->get_image_id()){$s=wp_get_attachment_image_src($p->get_image_id(),'full');if($s)$img=$s[0];}
   if($p->get_price()!=='')$extra='<meta property="product:price:amount" content="'.esc_attr($p->get_price()).'"><meta property="product:price:currency" content="'.esc_attr(get_woocommerce_currency()).'">';
  }
 }elseif(is_singular()){
  $title=get_the_title().' - '.$site;$url=get_permalink();$t=get_the_post_thumbnail_url(null,'full');if($t)$img=$t;
 }elseif(function_exists('is_product_category')&&is_product_category()){
  $o=get_queried_object();if($o){$title=$o->name.' - '.$site;$url=get_term_link($o);}
 }
 echo "\n".'<meta name="description" content="'.esc_attr($desc).'"><meta property="og:site_name" content="'.esc_attr($site).'"><meta property="og:type" content="'.esc_attr($type).'"><meta property="og:title" content="'.esc_attr($title).'"><meta property="og:description" content="'.esc_attr($desc).'"><meta property="og:url" content="'.esc_url($url).'"><meta property="og:image" content="'.esc_url($img).'"><meta property="og:image:secure_url" content="'.esc_url($img).'"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="'.esc_attr($title).'"><meta name="twitter:description" content="'.esc_attr($desc).'"><meta name="twitter:image" content="'.esc_url($img).'">'.$extra."\n";
},5);

/* ---------- Courier charges (editable) ---------- */
add_action('customize_register',function($c){
 $c->add_section('hs_courier',['title'=>'Courier charges','priority'=>31,'description'=>'Shown only in the cart and checkout total. One charge per order (per delivery address).']);
 $f=[['hs_courier_frag','Courier charge for fragrance / perfume products (Rs.)',270,'number'],['hs_courier_other','Courier charge for all other products (Rs.), 0 = none',0,'number'],['hs_frag_words','Words that mark a fragrance product (name or category), separated by commas','fragrance,perfume,parfum,attar,cologne,scent,body spray,eau de','text']];
 foreach($f as $x){$c->add_setting($x[0],['default'=>$x[2],'sanitize_callback'=>$x[3]==='number'?'hs_num':'sanitize_text_field']);$c->add_control($x[0],['label'=>$x[1],'section'=>'hs_courier','type'=>$x[3]]);}
});
function hs_num($v){return max(0,(float)$v);}
add_filter('woocommerce_cart_needs_shipping','__return_false');

/* ---------- Reviews: only customers who bought the product ---------- */
function hs_can_review($pid){
 if(!is_user_logged_in())return false;
 $u=wp_get_current_user();
 return wc_customer_bought_product($u->user_email,$u->ID,$pid);
}
add_filter('comments_open',function($open,$pid){
 if(get_post_type($pid)!=='product')return $open;
 return hs_can_review($pid)?$open:false;
},10,2);
add_action('init',function(){
 if(get_option('hs_rv_v1'))return;
 update_option('woocommerce_review_rating_verification_required','yes');
 update_option('hs_rv_v1',1);
},30);

/* ---------- "+" add to cart on the product image ---------- */
add_filter('woocommerce_single_product_image_thumbnail_html',function($html,$id){
 static $done=false;global $product;
 if($done||!$product||!$product->is_purchasable()||!$product->is_in_stock())return $html;
 $done=true;
 $btn='<button type="button" class="pdp-plus" aria-label="Add to cart">+</button>';
 $n=preg_replace('/<\/div>\s*$/',$btn.'</div>',$html,1);
 return $n?$n:$html;
},10,2);

add_action('customize_register',function($c){
 $c->add_section('hs_desc',['title'=>'Site description','priority'=>35]);
 $c->add_setting('hs_site_desc',['default'=>'Hassence is an online store in Pakistan for fragrances, fashion, beauty, electronics and more. Shop at great prices with Cash on Delivery and Easypaisa, delivered across Pakistan.','sanitize_callback'=>'sanitize_textarea_field']);
 $c->add_control('hs_site_desc',['label'=>'Description shown on Google and when the site is shared','section'=>'hs_desc','type'=>'textarea']);
});
require_once get_template_directory() . '/inc/pages.php';
if (class_exists('WooCommerce')) { require_once get_template_directory() . '/inc/checkout.php'; require_once get_template_directory() . '/inc/emails.php'; require_once get_template_directory() . '/inc/dashboard.php'; require_once get_template_directory() . '/inc/affiliate.php'; require_once get_template_directory() . '/inc/importer.php'; require_once get_template_directory() . '/inc/passwords.php'; require_once get_template_directory() . '/inc/country.php'; require_once get_template_directory() . '/inc/account.php'; require_once get_template_directory() . '/inc/bulk.php'; require_once get_template_directory() . '/inc/sizes.php'; require_once get_template_directory() . '/inc/daraz.php'; require_once get_template_directory() . '/inc/currency.php'; }

/* ---------- Courier charge: one charge per order (highest applies) ---------- */
function hs_courier_total($cart){
 $frag=(float)get_theme_mod('hs_courier_frag',270);$other=(float)get_theme_mod('hs_courier_other',0);$max=0;
 foreach($cart->get_cart() as $i){
  $pid=$i['product_id'];$m=get_post_meta($pid,'_hs_ship',true);
  if($m!==''&&$m!==false){$c=(float)$m;}
  else{$pp=wc_get_product($pid);$c=($pp&&hs_is_frag($pp))?$frag:$other;}
  if($c>$max)$max=$c;
 }
 return $max;
}
add_action('woocommerce_cart_calculate_fees',function($cart){
 if(is_admin()&&!defined('DOING_AJAX'))return;
 $t=hs_courier_total($cart);
 if($t>0)$cart->add_fee('Courier charges',$t,false);
});
require_once get_template_directory() . '/inc/app.php';

/* ---------- Small Buy now button beside the price ---------- */
add_action('init',function(){
 remove_action('woocommerce_single_product_summary','woocommerce_template_single_price',10);
 add_action('woocommerce_single_product_summary',function(){
  global $product;if(!$product)return;
  echo '<div class="pdp-pricerow"><p class="price">'.$product->get_price_html().'</p>';
  if($product->is_purchasable()&&$product->is_in_stock()&&!$product->is_type('external'))echo '<button type="button" class="buy-mini">⚡ Buy now</button>';
  echo '</div>';
 },10);
},30);
