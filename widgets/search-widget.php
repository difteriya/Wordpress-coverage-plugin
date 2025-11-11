<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Map_Coverage_Search_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'map_coverage_search';
    }

    public function get_title() {
        return __( 'Əhatə Axtarışı', 'map-coverage-plugin' );
    }

    public function get_icon() {
        return 'eicon-search';
    }

    public function get_categories() {
        return [ 'coverage-category' ];
    }

    public function get_keywords() {
        return [ 'search', 'coverage', 'axtarış', 'əhatə', 'form' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Axtarış Tənzimləmələri', 'map-coverage-plugin' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'search_title',
            [
                'label' => __( 'Başlıq', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( '🗺️ Ünvanınız üçün Əhatə Yoxlayın', 'map-coverage-plugin' ),
            ]
        );

        $this->add_control(
            'search_description',
            [
                'label' => __( 'Təsvir', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __( 'Şəhərinizi seçin və xidmət əhatəsini yoxlayın.', 'map-coverage-plugin' ),
            ]
        );

        $this->add_control(
            'redirect_page',
            [
                'label' => __( 'Yönləndirmə Səhifəsi', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __( '/ehate/rayon-adi', 'map-coverage-plugin' ),
                'description' => __( 'Axtarış nəticəsində yönləndiriləcək səhifə URL-i', 'map-coverage-plugin' ),
                'default' => [
                    'url' => '',
                    'is_external' => false,
                    'nofollow' => false,
                ],
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __( 'Düymə Mətni', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( '🔍 Axtar', 'map-coverage-plugin' ),
            ]
        );

        $this->add_control(
            'show_city_dropdown',
            [
                'label' => __( 'Şəhər Seçimi Göstər', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Bəli', 'map-coverage-plugin' ),
                'label_off' => __( 'Xeyr', 'map-coverage-plugin' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __( 'Stil', 'map-coverage-plugin' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'form_background',
            [
                'label' => __( 'Form Arxa Plan', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .map-coverage-search-only' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background',
            [
                'label' => __( 'Düymə Arxa Plan', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007bff',
                'selectors' => [
                    '{{WRAPPER}} #search-coverage-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __( 'Düymə Mətn Rəngi', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} #search-coverage-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __( 'Başlıq Tipoqrafiya', 'map-coverage-plugin' ),
                'selector' => '{{WRAPPER}} .map-coverage-search-only h4',
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => __( 'Künc Radiusu', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 15,
                ],
                'selectors' => [
                    '{{WRAPPER}} .map-coverage-search-only' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} #search-coverage-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $redirect_url = '';
        
        if ( ! empty( $settings['redirect_page']['url'] ) ) {
            $redirect_url = $settings['redirect_page']['url'];
        }
        
        // Add custom styling and text overrides
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var searchContainer = document.querySelector("#map-coverage-search-form");
                if (searchContainer) {
                    var h4 = searchContainer.querySelector("h4");
                    var p = searchContainer.querySelector("p");
                    var button = document.querySelector("#search-coverage-btn");
                    
                    if (h4 && "' . esc_js( $settings['search_title'] ) . '") {
                        h4.textContent = "' . esc_js( $settings['search_title'] ) . '";
                    }
                    if (p && "' . esc_js( $settings['search_description'] ) . '") {
                        p.textContent = "' . esc_js( $settings['search_description'] ) . '";
                    }
                    if (button && "' . esc_js( $settings['button_text'] ) . '") {
                        button.textContent = "' . esc_js( $settings['button_text'] ) . '";
                    }
                }
            });
        </script>';
        
        if ( $settings['show_city_dropdown'] !== 'yes' ) {
            echo '<style>.map-search-controls select { display: none !important; }</style>';
        }
        
        echo do_shortcode( '[coverage_search redirect_page="' . esc_attr( $redirect_url ) . '"]' );
    }

    protected function content_template() {
        ?>
        <div class="elementor-widget-container">
            <div class="coverage-widget-preview" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 25px; border-radius: 12px; text-align: center; color: white;">
                <h3 style="margin: 0 0 10px 0; color: white;">🔍 Axtarış Formu</h3>
                <p style="margin: 0; opacity: 0.9;">Tək axtarış funksiyası ilə sadə form</p>
                <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                    <strong>Xüsusiyyətlər:</strong> Şəhər Seçimi, Custom Yönləndirmə, Responsive Dizayn
                </div>
            </div>
        </div>
        <?php
    }
}