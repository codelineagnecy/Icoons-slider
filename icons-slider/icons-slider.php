<?php
/**
 * Plugin Name: Icons Slider
 * Description: Create a horizontal slider of icons with logo, name and description.
 * Version: 1.0
 * Author: CodeLine Agency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// constants
if ( ! defined('ICONS_SLIDER_DIR') ) define('ICONS_SLIDER_DIR', plugin_dir_path(__FILE__));
if ( ! defined('ICONS_SLIDER_URL') ) define('ICONS_SLIDER_URL', plugin_dir_url(__FILE__));

// load Elementor widget if Elementor is active
add_action('elementor/widgets/register', 'icons_slider_register_elementor_widget');
function icons_slider_register_elementor_widget($widgets_manager) {
    if (class_exists('\Elementor\Widget_Base')) {
        require_once(ICONS_SLIDER_DIR . 'elementor-widget.php');
        $widgets_manager->register(new \Icons_Slider_Elementor_Widget());
    }
}

// enqueue admin styles for metabox layout
add_action('admin_enqueue_scripts','icons_slider_admin_styles');
function icons_slider_admin_styles($hook){
    // only load on icon_slide edit screens
    if(in_array($hook,array('post-new.php','post.php'))){
        $screen = get_current_screen();
        if($screen && $screen->post_type==='icon_slide'){
            wp_add_inline_style('wp-admin',
                '#icons_slider_details .inside { display:flex; flex-wrap: wrap; gap:8px; }
                 #icons_slider_details .inside > div { flex:1 1 140px; min-width:120px; }
                 #icons_slider_details label { font-weight:600; margin-bottom:4px; display:block; }
                 #icons_slider_details textarea, #icons_slider_details input { box-sizing:border-box; width:100%; }
                '
            );
        }
    }
}

// activation behaviour: optionally reset database
register_activation_hook(__FILE__,'icons_slider_activate');
function icons_slider_activate() {
    // check if a reset flag was set via URL parameter or option
    // this allows users to clean the database on reactivation
    if( get_option('icons_slider_reset_on_activate') ){
        // delete all icon slides
        $args = array(
            'post_type'      => 'icon_slide',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        );
        $query = new WP_Query($args);
        if($query->have_posts()){
            while($query->have_posts()){
                $query->the_post();
                wp_delete_post(get_the_ID(), true);
            }
            wp_reset_postdata();
        }
        // clear the flag after reset
        delete_option('icons_slider_reset_on_activate');
    }
    // previous activations created sample pages and slides.
    // we now leave the database untouched so the administrator can add
    // slides and pages explicitly.
    return;
}

/* enqueue styles/scripts */
add_action('wp_enqueue_scripts','icons_slider_assets');
function icons_slider_assets(){
    wp_register_style('icons-slider-style', ICONS_SLIDER_URL.'css/slider.css',array(),filemtime(ICONS_SLIDER_DIR.'css/slider.css'));
    wp_register_script('icons-slider-script', ICONS_SLIDER_URL.'js/slider.js',array('jquery'),filemtime(ICONS_SLIDER_DIR.'js/slider.js'),true);
}

/* post type */
add_action('init','icons_slider_register_post_type');
function icons_slider_register_post_type(){
    $labels=array(
        'name'=>'Icon Slides',
        'singular_name'=>'Icon Slide',
        'menu_name'=>'Icons Slider',
        'add_new'=>'Add New',
        'add_new_item'=>'Add New Icon Slide',
        'edit_item'=>'Edit Icon Slide',
        'new_item'=>'New Icon Slide',
        'view_item'=>'View Icon Slide',
        'all_items'=>'All Icon Slides',
        'search_items'=>'Search Icon Slides',
        'not_found'=>'No slides found',
        'not_found_in_trash'=>'No slides found in Trash',
    );
    $args=array(
        'labels'=>$labels,
        'public'=>true,
        'show_in_rest'=>true,
        'menu_icon'=>'dashicons-images-alt2',
        'supports'=>array('title','editor','thumbnail'),
        'taxonomies'=>array('icon_slide_category'),
    );
    register_post_type('icon_slide',$args);
    
    // register taxonomy (categories) for icon slides
    $cat_labels=array(
        'name'=>'Slide Categories',
        'singular_name'=>'Slide Category',
        'search_items'=>'Search Categories',
        'all_items'=>'All Categories',
        'edit_item'=>'Edit Category',
        'update_item'=>'Update Category',
        'add_new_item'=>'Add New Category',
        'new_item_name'=>'New Category Name',
        'menu_name'=>'Categories',
    );
    register_taxonomy('icon_slide_category','icon_slide',array(
        'hierarchical'=>true,
        'labels'=>$cat_labels,
        'show_ui'=>true,
        'show_in_rest'=>true,
        'show_admin_column'=>true,
        'query_var'=>true,
        'rewrite'=>array('slug'=>'slide-category'),
    ));
}

/* meta registration */
add_action('init','icons_slider_register_meta');
function icons_slider_register_meta(){
    register_meta('post','slide_description',array('single'=>true,'type'=>'string','show_in_rest'=>true,'object_subtype'=>'icon_slide'));
    register_meta('post','slide_order',array('single'=>true,'type'=>'number','show_in_rest'=>true,'object_subtype'=>'icon_slide'));
    register_meta('post','slide_logo_size',array('single'=>true,'type'=>'number','show_in_rest'=>true,'object_subtype'=>'icon_slide'));
}

/* add category count column in admin */
add_filter('manage_icon_slide_category_custom_column', 'icons_slider_category_column_content', 10, 3);
function icons_slider_category_column_content($content, $column_name, $term_id){
    if($column_name === 'slides_count'){
        $term = get_term($term_id, 'icon_slide_category');
        $count = $term->count;
        $link = add_query_arg(array(
            'post_type' => 'icon_slide',
            'icon_slide_category' => $term->slug
        ), admin_url('edit.php'));
        return '<a href="'.esc_url($link).'">'.$count.' slides</a>';
    }
    return $content;
}

add_filter('manage_edit-icon_slide_category_columns', 'icons_slider_category_columns');
function icons_slider_category_columns($columns){
    $new_columns = array();
    foreach($columns as $key => $value){
        $new_columns[$key] = $value;
        if($key === 'name'){
            $new_columns['slides_count'] = 'Number of Slides';
        }
    }
    return $new_columns;
}

/* admin metaboxes */
add_action('add_meta_boxes','icons_slider_metaboxes');
function icons_slider_metaboxes(){
    add_meta_box('icons_slider_details','Slide Details','icons_slider_details_cb','icon_slide','side','default');
}
function icons_slider_details_cb($post){
    wp_nonce_field('icons_slider_save','icons_slider_nonce');
    $desc=get_post_meta($post->ID,'slide_description',true);
    $order=get_post_meta($post->ID,'slide_order',true);
    ?>
    <div style="display:flex !important; gap:15px !important; width:100% !important;">
        <div style="flex:2; min-width:0;">
            <label for="slide_description_input" style="display:block; margin-bottom:5px; font-weight:bold;"><?php _e('Description','icons-slider');?></label>
            <textarea id="slide_description_input" name="slide_description" style="width:100%;" rows="3"><?php echo esc_textarea($desc);?></textarea>
        </div>
        <div style="flex:0 0 auto; min-width:90px;">
            <label for="slide_order_input" style="display:block; margin-bottom:5px; font-weight:bold;"><?php _e('Order','icons-slider');?></label>
            <input type="number" id="slide_order_input" name="slide_order" value="<?php echo esc_attr($order);?>" min="0" style="width:100%;" />
        </div>
    </div>
    <?php
}
add_action('save_post_icon_slide','icons_slider_save_meta');
function icons_slider_save_meta($post_id){
    if(!isset($_POST['icons_slider_nonce'])||!wp_verify_nonce(wp_unslash($_POST['icons_slider_nonce']),'icons_slider_save')) return;
    if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE) return;
    if(!current_user_can('edit_post', $post_id)) return;

    if(isset($_POST['slide_description'])) update_post_meta($post_id,'slide_description',sanitize_textarea_field(wp_unslash($_POST['slide_description'])));
    if(isset($_POST['slide_order'])){
        $val=intval(wp_unslash($_POST['slide_order']));
        if($val<=0) $val=1000;
        update_post_meta($post_id,'slide_order',$val);
    }else{
        update_post_meta($post_id,'slide_order',1000);
    }
}

/* shortcode */
add_shortcode('icons_slider','icons_slider_shortcode');
function icons_slider_shortcode($atts){
    wp_enqueue_style('icons-slider-style');
    wp_enqueue_script('icons-slider-script');
    
    $default_logo_size = 55;
    $default_card_size = $default_logo_size + 20;
    
    // parse shortcode attributes
    $atts = shortcode_atts(array(
        'category' => '', // empty = all slides
    ), $atts, 'icons_slider');
    
    $args=array(
        'post_type'=>'icon_slide',
        'posts_per_page'=>-1,
        'orderby'=>array('meta_value_num'=>'ASC','menu_order'=>'ASC'),
        'meta_key'=>'slide_order',
        'order'=>'ASC',
    );
    
    // if category is specified, filter by it
    if(!empty($atts['category'])){
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'icon_slide_category',
                'field'    => 'slug',
                'terms'    => sanitize_title($atts['category']),
            ),
        );
    }
    
    $q=new WP_Query($args);
    ob_start();
    ?>
    <div class="icons-slider-wrapper" style="--icons-logo-size:<?php echo esc_attr($default_logo_size); ?>px; --icons-card-size:<?php echo esc_attr($default_card_size); ?>px; max-width:1000px; margin:0 auto; padding:20px 0; overflow:visible;">
        <div class="icons-slider-container" style="position:relative; overflow:hidden;">
            <div class="icons-slider-track" style="display:flex; gap:20px; align-items:center; flex-wrap:nowrap;">
            <?php while($q->have_posts()):$q->the_post();
                $desc=get_post_meta(get_the_ID(),'slide_description',true);
                if(!$desc) $desc=get_the_content();

                $slide_logo_size = 0;
                $cat_for_size = !empty($atts['category']) ? sanitize_title($atts['category']) : '';
                if ($cat_for_size) {
                    $slide_logo_size = (int) get_post_meta(get_the_ID(), 'slide_logo_size_' . $cat_for_size, true);
                }
                if ($slide_logo_size < 20) {
                    $slide_logo_size = (int) get_post_meta(get_the_ID(), 'slide_logo_size', true);
                }
                if ($slide_logo_size < 20) {
                    $slide_logo_size = $default_logo_size;
                }
                if ($slide_logo_size > 300) {
                    $slide_logo_size = 300;
                }
                $slide_card_size = $slide_logo_size + 20;

                $fit_mode = 'cover';
                $fit_class = 'logo-fit-cover';
                $thumb_id = get_post_thumbnail_id(get_the_ID());
                if ($thumb_id) {
                    $meta = wp_get_attachment_metadata($thumb_id);
                    if (!empty($meta['width']) && !empty($meta['height'])) {
                        $ratio = (float) $meta['width'] / max(1, (float) $meta['height']);
                        // Wide logos (usually text logos) should not be cropped.
                        if ($ratio >= 1.35) {
                            $fit_mode = 'contain';
                            $fit_class = 'logo-fit-contain';
                        }
                    }
                }
                ?>
                <div class="icons-slide <?php echo esc_attr($fit_class); ?>" style="--icons-logo-size:<?php echo esc_attr($slide_logo_size); ?>px; --icons-card-size:<?php echo esc_attr($slide_card_size); ?>px; width:<?php echo esc_attr($slide_card_size); ?>px; min-width:<?php echo esc_attr($slide_card_size); ?>px; max-width:<?php echo esc_attr($slide_card_size); ?>px; padding:10px; box-sizing:border-box; text-align:center; background:transparent; border:0; border-radius:0; box-shadow:none;">
                    <?php if(has_post_thumbnail()):?><div class="slide-image" style="display:flex; align-items:center; justify-content:center; min-height:<?php echo esc_attr($slide_logo_size); ?>px;"><?php the_post_thumbnail('full', array('style' => 'width:' . esc_attr($slide_logo_size) . 'px; height:' . esc_attr($slide_logo_size) . 'px; max-width:' . esc_attr($slide_logo_size) . 'px; max-height:' . esc_attr($slide_logo_size) . 'px; object-fit:' . esc_attr($fit_mode) . '; display:block; margin:0 auto 8px;')); ?></div><?php else: ?><div class="slide-placeholder" style="min-height:<?php echo esc_attr($slide_logo_size); ?>px;"></div><?php endif;?>
                    <div class="slide-tooltip-wrapper" style="display:none; position:fixed; z-index:9999; white-space:nowrap;">
                        <h4 class="slide-title"><?php the_title();?></h4>
                        <?php if($desc):?><p class="slide-desc"><?php echo esc_html($desc);?></p><?php endif;?>
                    </div>
                </div>
            <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* BULK IMAGE NORMALIZATION */

/**
 * Add admin menu for image normalization
 */
add_action('admin_menu', 'icons_slider_add_admin_menu');
function icons_slider_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=icon_slide',
        'Edit Images',
        'Edit Images',
        'manage_options',
        'icons_slider_normalize',
        'icons_slider_normalize_page'
    );
}

/**
 * Admin page - categories overview or per-category logos
 */
function icons_slider_normalize_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $cat_slug = isset($_GET['cat']) ? sanitize_title(wp_unslash($_GET['cat'])) : '';

    if ($cat_slug) {
        // ── CATEGORY DETAIL VIEW ──────────────────────────────────
        $term = get_term_by('slug', $cat_slug, 'icon_slide_category');
        if (!$term) {
            wp_die('Category not found');
        }

        $args = array(
            'post_type'      => 'icon_slide',
            'posts_per_page' => -1,
            'tax_query'      => array(array(
                'taxonomy' => 'icon_slide_category',
                'field'    => 'slug',
                'terms'    => $cat_slug,
            )),
        );
        $slides = (new WP_Query($args))->posts;
        $back_url = admin_url('edit.php?post_type=icon_slide&page=icons_slider_normalize');
        ?>
        <div class="wrap">
            <h1>
                <a href="<?php echo esc_url($back_url); ?>" style="text-decoration:none; color:#50575e; font-size:1rem; margin-right:10px;">← Back</a>
                Edit Images · <?php echo esc_html($term->name); ?>
            </h1>

            <div style="background:#fff; padding:20px; border-radius:8px; margin-top:20px;">

                <!-- Adjust ALL logos in this category -->
                <div style="background:#e8f4f8; border:1px solid #0073aa; padding:15px; border-radius:6px; margin-bottom:20px;">
                    <h3 style="margin-top:0;">Alle logos tegelijk aanpassen</h3>
                    <p style="margin-top:0; color:#666;">Vul een grootte in en klik op Toepassen. Alle logos in deze categorie worden ingesteld op die grootte.</p>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="number" id="bulk-size-input" value="" min="20" max="300" placeholder="bijv. 80" style="width:90px; padding:6px;" />
                        <span style="color:#666;">px</span>
                        <button id="btn-bulk-apply" class="button button-primary">Toepassen op alle logos</button>
                        <span id="bulk-save-msg" style="color:green; display:none;">✓ Opgeslagen</span>
                    </div>
                </div>

                <!-- Per-logo size table -->
                <h3>Per logo aanpassen</h3>
                <p style="color:#666; margin-top:0;">Leeglaten = 55px standaard.</p>
                <div style="border:1px solid #eee; border-radius:6px; background:#fff; overflow:auto; max-height:420px;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f6f7f7;">
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #eee;">Logo</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #eee;">Naam</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #eee;">Grootte (px)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($slides as $slide):
                            $custom_size = (int) get_post_meta($slide->ID, 'slide_logo_size_' . $cat_slug, true);
                            if ($custom_size < 20) {
                                $custom_size = (int) get_post_meta($slide->ID, 'slide_logo_size', true);
                            }
                            $thumb_url   = get_the_post_thumbnail_url($slide->ID, 'thumbnail');
                        ?>
                            <tr>
                                <td style="padding:10px; border-bottom:1px solid #f1f1f1; width:60px;">
                                    <?php if ($thumb_url): ?>
                                        <img src="<?php echo esc_url($thumb_url); ?>" style="width:40px; height:40px; object-fit:contain; display:block;" />
                                    <?php else: ?><span style="color:#999;">-</span><?php endif; ?>
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #f1f1f1;"><?php echo esc_html(get_the_title($slide->ID)); ?></td>
                                <td style="padding:10px; border-bottom:1px solid #f1f1f1; width:160px;">
                                    <input type="number" class="per-logo-size-input"
                                        data-slide-id="<?php echo esc_attr($slide->ID); ?>"
                                        value="<?php echo $custom_size > 0 ? esc_attr($custom_size) : ''; ?>"
                                        min="20" max="300" placeholder="55" style="width:80px;" />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:12px; display:flex; gap:10px; align-items:center;">
                    <button id="btn-save-per-logo-sizes" class="button button-secondary">Opslaan</button>
                    <span id="per-logo-save-message" style="color:green; display:none;">✓ Opgeslagen</span>
                </div>

                <!-- Normalize button -->
                <div style="background:#fff9e6; border:1px solid #ffb81c; padding:15px; border-radius:6px; margin-top:24px;">
                    <p style="margin:0;"><strong>⚠️ Normaliseren</strong> past de werkelijke afbeeldingsbestanden aan naar de ingestelde px. Originelen worden niet verwijderd.</p>
                </div>
                <button id="btn-normalize-images" class="button button-primary button-large" style="margin-top:12px;">
                    Normaliseer alle afbeeldingen
                </button>
                <div id="normalize-progress" style="display:none; margin-top:20px;">
                    <div class="notice notice-info" style="margin:0;">
                        <p id="progress-text">Bezig...</p>
                        <div style="width:100%; background:#e0e0e0; height:20px; border-radius:4px; overflow:hidden;">
                            <div id="progress-bar" style="height:100%; background:#0073aa; width:0%; transition:width 0.3s;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var saveNonce   = '<?php echo esc_js(wp_create_nonce("icons_slider_save_per_logo_sizes_nonce")); ?>';
            var normNonce   = '<?php echo esc_js(wp_create_nonce("icons_slider_normalize_nonce")); ?>';
            var catSlug     = '<?php echo esc_js($cat_slug); ?>';

            // Bulk apply: fill inputs + directly save all logos in this category
            $('#btn-bulk-apply').on('click', function() {
                var size = parseInt($('#bulk-size-input').val(), 10);
                if (!size || size < 20) { alert('Vul een geldige grootte in (minimaal 20).'); return; }

                // Fill all inputs visually
                $('.per-logo-size-input').val(size);

                // Collect all slide IDs from this category
                var rows = [];
                $('.per-logo-size-input').each(function() {
                    rows.push({ id: parseInt($(this).data('slide-id'), 10), size: size });
                });

                var $btn = $(this);
                $btn.prop('disabled', true).text('Opslaan...');

                $.ajax({
                    url: ajaxurl, type: 'POST',
                    data: { action: 'icons_slider_save_per_logo_sizes', nonce: saveNonce, rows: rows, cat: catSlug },
                    success: function(r) {
                        $btn.prop('disabled', false);
                        if (r.success) {
                            $btn.text('✓ Opgeslagen voor alle logos!');
                            setTimeout(function() { $btn.text('Toepassen op alle logos'); }, 2500);
                        } else {
                            $btn.text('Toepassen op alle logos');
                            alert('Fout bij opslaan.');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('Toepassen op alle logos');
                        alert('AJAX fout.');
                    }
                });
            });

            // Save per-logo sizes
            $('#btn-save-per-logo-sizes').on('click', function() {
                var rows = [];
                $('.per-logo-size-input').each(function() {
                    rows.push({ id: parseInt($(this).data('slide-id'), 10), size: $(this).val() });
                });
                $.ajax({
                    url: ajaxurl, type: 'POST',
                    data: { action: 'icons_slider_save_per_logo_sizes', nonce: saveNonce, rows: rows, cat: catSlug },
                    success: function(r) {
                        if (r.success) {
                            $('#per-logo-save-message').show();
                            $('#btn-bulk-apply').text('Toepassen op alle logos');
                            setTimeout(function() { $('#per-logo-save-message').fadeOut(); }, 2000);
                        } else { alert('Fout bij opslaan.'); }
                    },
                    error: function() { alert('AJAX fout.'); }
                });
            });

            // Normalize
            $('#btn-normalize-images').on('click', function() {
                if (!confirm('Alle afbeeldingen in deze categorie normaliseren?')) return;
                $(this).prop('disabled', true);
                $('#normalize-progress').show();
                $.ajax({
                    url: ajaxurl, type: 'POST',
                    data: { action: 'icons_slider_normalize_images', nonce: normNonce, cat: catSlug },
                    success: function(r) {
                        if (r.success) {
                            $('#progress-text').html('<strong style="color:green;">✓ ' + r.data.processed + ' afbeeldingen genormaliseerd!</strong>');
                            $('#progress-bar').css({ width: '100%', background: '#00aa00' });
                            setTimeout(function() { window.location.reload(); }, 2000);
                        } else {
                            $('#progress-text').html('<strong style="color:red;">✗ Fout: ' + r.data + '</strong>');
                        }
                    },
                    error: function() { $('#progress-text').html('<strong style="color:red;">✗ AJAX fout</strong>'); }
                });
            });
        });
        </script>
        <?php

    } else {
        // ── CATEGORIES OVERVIEW ───────────────────────────────────
        $categories = get_terms(array('taxonomy' => 'icon_slide_category', 'hide_empty' => false));
        $base_url   = admin_url('edit.php?post_type=icon_slide&page=icons_slider_normalize');
        ?>
        <div class="wrap">
            <h1>Edit Images</h1>
            <p style="color:#666; margin-top:4px;">Kies een categorie om de logos te bewerken.</p>

            <?php if (empty($categories) || is_wp_error($categories)): ?>
                <div class="notice notice-warning"><p>Er zijn nog geen categorieën aangemaakt. Maak eerst categorieën aan onder <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=icon_slide_category&post_type=icon_slide')); ?>">Icons Slider → Categories</a>.</p></div>
            <?php else: ?>
                <div style="display:flex; flex-wrap:wrap; gap:16px; margin-top:20px;">
                <?php foreach ($categories as $cat):
                    $cat_url   = add_query_arg('cat', $cat->slug, $base_url);
                    $count     = $cat->count;
                    $slides_q  = get_posts(array('post_type' => 'icon_slide', 'numberposts' => 3,
                        'tax_query' => array(array('taxonomy' => 'icon_slide_category', 'field' => 'slug', 'terms' => $cat->slug))));
                ?>
                    <a href="<?php echo esc_url($cat_url); ?>" style="display:block; text-decoration:none; background:#fff; border:1px solid #ddd; border-radius:10px; padding:18px 22px; min-width:200px; box-shadow:0 1px 3px rgba(0,0,0,.07); transition:box-shadow .15s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.12)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,.07)'">
                        <div style="display:flex; gap:8px; margin-bottom:12px;">
                            <?php foreach ($slides_q as $sq):
                                $t = get_the_post_thumbnail_url($sq->ID, 'thumbnail');
                                if ($t): ?>
                                    <img src="<?php echo esc_url($t); ?>" style="width:32px; height:32px; object-fit:contain; border-radius:4px; background:#f6f7f7; padding:2px;" />
                                <?php endif;
                            endforeach; ?>
                        </div>
                        <strong style="color:#1d2327; font-size:15px;"><?php echo esc_html($cat->name); ?></strong><br>
                        <span style="color:#999; font-size:13px;"><?php echo esc_html($count); ?> logo<?php echo $count !== 1 ? "'s" : ''; ?></span>
                    </a>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}



/**
 * AJAX handler for saving per-logo custom sizes
 */
add_action('wp_ajax_icons_slider_save_per_logo_sizes', 'icons_slider_ajax_save_per_logo_sizes');
function icons_slider_ajax_save_per_logo_sizes() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'icons_slider_save_per_logo_sizes_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $rows = isset($_POST['rows']) && is_array($_POST['rows']) ? wp_unslash($_POST['rows']) : array();
    $cat_slug_save = isset($_POST['cat']) ? sanitize_title(wp_unslash($_POST['cat'])) : '';
    $meta_key = $cat_slug_save ? 'slide_logo_size_' . $cat_slug_save : 'slide_logo_size';
    $updated = 0;

    foreach ($rows as $row) {
        $slide_id = isset($row['id']) ? intval($row['id']) : 0;
        if ($slide_id <= 0 || get_post_type($slide_id) !== 'icon_slide') {
            continue;
        }

        $size_raw = isset($row['size']) ? trim((string) $row['size']) : '';
        if ($size_raw === '') {
            delete_post_meta($slide_id, $meta_key);
            $updated++;
            continue;
        }

        $size = intval($size_raw);
        if ($size < 20) {
            delete_post_meta($slide_id, $meta_key);
            $updated++;
            continue;
        }
        if ($size > 300) {
            $size = 300;
        }

        update_post_meta($slide_id, $meta_key, $size);
        $updated++;
    }

    wp_send_json_success(array('updated' => $updated));
}

/**
 * AJAX handler for bulk image normalization
 */
add_action('wp_ajax_icons_slider_normalize_images', 'icons_slider_ajax_normalize_images');
function icons_slider_ajax_normalize_images() {
    // verify nonce and admin capability
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'icons_slider_normalize_nonce')) {
        wp_send_json_error('Nonce verification failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    // optionally filter by category
    $cat_slug = isset($_POST['cat']) ? sanitize_title(wp_unslash($_POST['cat'])) : '';
    $args = array(
        'post_type'      => 'icon_slide',
        'posts_per_page' => -1,
    );
    if ($cat_slug) {
        $args['tax_query'] = array(array(
            'taxonomy' => 'icon_slide_category',
            'field'    => 'slug',
            'terms'    => $cat_slug,
        ));
    }
    $query = new WP_Query($args);

    $processed = 0;
    
    foreach ($query->posts as $post) {
        if (has_post_thumbnail($post->ID)) {
            $attachment_id = get_post_thumbnail_id($post->ID);
            if ($attachment_id) {
                $norm_meta_key = $cat_slug ? 'slide_logo_size_' . $cat_slug : 'slide_logo_size';
                $slide_size = (int) get_post_meta($post->ID, $norm_meta_key, true);
                if ($slide_size < 20) {
                    $slide_size = (int) get_post_meta($post->ID, 'slide_logo_size', true);
                }
                if ($slide_size < 20) {
                    $slide_size = 55;
                }
                if ($slide_size > 300) {
                    $slide_size = 300;
                }
                $size = array($slide_size, $slide_size);

                // regenerate thumbnail
                $result = icons_slider_regenerate_thumbnail($attachment_id, $size);
                if ($result) {
                    $processed++;
                }
            }
        }
    }
    
    wp_send_json_success(array('processed' => $processed));
}

/**
 * Regenerate thumbnail to specific size
 */
function icons_slider_regenerate_thumbnail($attachment_id, $size = array(55, 55)) {
    $file = get_attached_file($attachment_id);
    
    if (!file_exists($file)) {
        return false;
    }
    
    // get image editor
    $image = wp_get_image_editor($file);
    
    if (is_wp_error($image)) {
        return false;
    }

    // Trim transparent / near-white borders first so logos with large empty
    // canvas areas do not stay visually tiny after resizing.
    icons_slider_trim_image_canvas($file);

    // Reload the editor after trimming and resize proportionally.
    $image = wp_get_image_editor($file);
    if (is_wp_error($image)) {
        return false;
    }

    $image->resize($size[0], $size[1], false);
    
    // save modified image
    $save_result = $image->save($file);
    
    if (is_wp_error($save_result)) {
        return false;
    }
    
    // update image metadata
    $metadata = wp_generate_attachment_metadata($attachment_id, $file);
    if ($metadata) {
        update_post_meta($attachment_id, '_wp_attachment_metadata', $metadata);
    }
    
    return true;
}

    function icons_slider_trim_image_canvas($file) {
        if (!file_exists($file) || !function_exists('getimagesize')) {
            return false;
        }

        $info = @getimagesize($file);
        if (!$info || empty($info[2])) {
            return false;
        }

        $image_type = $info[2];
        $source = null;

        switch ($image_type) {
            case IMAGETYPE_PNG:
                if (function_exists('imagecreatefrompng')) {
                    $source = @imagecreatefrompng($file);
                }
                break;
            case IMAGETYPE_JPEG:
                if (function_exists('imagecreatefromjpeg')) {
                    $source = @imagecreatefromjpeg($file);
                }
                break;
            case IMAGETYPE_GIF:
                if (function_exists('imagecreatefromgif')) {
                    $source = @imagecreatefromgif($file);
                }
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $source = @imagecreatefromwebp($file);
                }
                break;
        }

        if (!$source) {
            return false;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $min_x = $width;
        $min_y = $height;
        $max_x = -1;
        $max_y = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($source, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;

                $is_transparent = $alpha >= 120;
                $is_white = $red >= 245 && $green >= 245 && $blue >= 245;

                if (!$is_transparent && !$is_white) {
                    if ($x < $min_x) {
                        $min_x = $x;
                    }
                    if ($y < $min_y) {
                        $min_y = $y;
                    }
                    if ($x > $max_x) {
                        $max_x = $x;
                    }
                    if ($y > $max_y) {
                        $max_y = $y;
                    }
                }
            }
        }

        if ($max_x < 0 || $max_y < 0) {
            imagedestroy($source);
            return false;
        }

        $crop_width = $max_x - $min_x + 1;
        $crop_height = $max_y - $min_y + 1;

        if ($crop_width >= $width && $crop_height >= $height) {
            imagedestroy($source);
            return false;
        }

        $cropped = imagecreatetruecolor($crop_width, $crop_height);
        if (!$cropped) {
            imagedestroy($source);
            return false;
        }

        if (in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP), true)) {
            imagealphablending($cropped, false);
            imagesavealpha($cropped, true);
            $transparent = imagecolorallocatealpha($cropped, 255, 255, 255, 127);
            imagefill($cropped, 0, 0, $transparent);
        }

        imagecopy($cropped, $source, 0, 0, $min_x, $min_y, $crop_width, $crop_height);

        $saved = false;
        switch ($image_type) {
            case IMAGETYPE_PNG:
                $saved = imagepng($cropped, $file);
                break;
            case IMAGETYPE_JPEG:
                $saved = imagejpeg($cropped, $file, 92);
                break;
            case IMAGETYPE_GIF:
                $saved = imagegif($cropped, $file);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    $saved = imagewebp($cropped, $file, 92);
                }
                break;
        }

        imagedestroy($cropped);
        imagedestroy($source);

        return (bool) $saved;
    }
