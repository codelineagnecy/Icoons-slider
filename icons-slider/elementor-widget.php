<?php
/**
 * Icons Slider Elementor Widget
 * Custom widget with category dropdown for easy selection
 */

if (!defined('ABSPATH')) exit;

class Icons_Slider_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'icons_slider';
    }

    public function get_title() {
        return 'Icons Slider';
    }

    public function get_icon() {
        return 'eicon-slider-push';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_style_depends() {
        return ['icons-slider-style'];
    }

    public function get_script_depends() {
        return ['icons-slider-script'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Slider Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // get all icon slide categories
        $categories = get_terms([
            'taxonomy' => 'icon_slide_category',
            'hide_empty' => false,
        ]);

        $category_options = ['all' => 'All Categories'];
        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $cat) {
                $category_options[$cat->slug] = $cat->name;
            }
        }

        $this->add_control(
            'category',
            [
                'label' => 'Select Category',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'all',
                'options' => $category_options,
                'description' => 'Choose which category of logos to display',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $category = $settings['category'];

        if ($category === 'all' || empty($category)) {
            echo do_shortcode('[icons_slider]');
        } else {
            echo do_shortcode('[icons_slider category="' . esc_attr($category) . '"]');
        }
    }

    protected function content_template() {
        ?>
        <# 
        var category = settings.category;
        if (category === 'all' || !category) {
            print('[icons_slider]');
        } else {
            print('[icons_slider category="' + category + '"]');
        }
        #>
        <?php
    }
}
