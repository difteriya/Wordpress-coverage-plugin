<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Map_Coverage_Map_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'map_coverage_map';
    }

    public function get_title() {
        return __( 'Əhatə Xəritəsi', 'map-coverage-plugin' );
    }

    public function get_icon() {
        return 'eicon-google-maps';
    }

    public function get_categories() {
        return [ 'coverage-category' ];
    }

    public function get_keywords() {
        return [ 'map', 'coverage', 'xəritə', 'əhatə', 'interaktiv' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Xəritə Tənzimləmələri', 'map-coverage-plugin' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'map_height',
            [
                'label' => __( 'Xəritə Hündürlüyü', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 300,
                        'max' => 800,
                        'step' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 500,
                ],
                'selectors' => [
                    '{{WRAPPER}} #map-coverage-frontend' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'show_search_form',
            [
                'label' => __( 'Axtarış Formu Göstər', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Bəli', 'map-coverage-plugin' ),
                'label_off' => __( 'Xeyr', 'map-coverage-plugin' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'search_title',
            [
                'label' => __( 'Axtarış Başlığı', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( '🗺️ Ünvanınız üçün Əhatə Yoxlayın', 'map-coverage-plugin' ),
                'condition' => [
                    'show_search_form' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'search_description',
            [
                'label' => __( 'Axtarış Təsviri', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __( 'Şəhərinizi, rayonunuzu və küçənizi seçin, sonra ev nömrənizi daxil edin ki, xidmətimizdən istifadə edə biləcəyinizi yoxlayaq.', 'map-coverage-plugin' ),
                'condition' => [
                    'show_search_form' => 'yes',
                ],
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
            'container_background',
            [
                'label' => __( 'Konteyner Arxa Plan', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .map-coverage-container' => 'background-color: {{VALUE}};',
                ],
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
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .map-coverage-container' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'container_box_shadow',
                'label' => __( 'Kölgə', 'map-coverage-plugin' ),
                'selector' => '{{WRAPPER}} .map-coverage-container',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Custom styling based on settings
        if ( $settings['show_search_form'] !== 'yes' ) {
            echo '<style>#map-coverage-search { display: none !important; }</style>';
        }
        
        if ( ! empty( $settings['search_title'] ) || ! empty( $settings['search_description'] ) ) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var searchContainer = document.querySelector("#map-coverage-search");
                    if (searchContainer) {
                        var h4 = searchContainer.querySelector("h4");
                        var p = searchContainer.querySelector("p");
                        if (h4) h4.textContent = "' . esc_js( $settings['search_title'] ) . '";
                        if (p) p.textContent = "' . esc_js( $settings['search_description'] ) . '";
                    }
                });
            </script>';
        }
        
        echo do_shortcode( '[map_coverage]' );
    }

    protected function content_template() {
        ?>
        <div class="elementor-widget-container">
            <div class="coverage-widget-preview" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 12px; text-align: center; color: white;">
                <h3 style="margin: 0 0 10px 0; color: white;">🗺️ İnteraktiv Xəritə</h3>
                <p style="margin: 0; opacity: 0.9;">Tam funksional xəritə və axtarış sistemi</p>
                <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                    <strong>Xüsusiyyətlər:</strong> Xəritə, Axtarış, Geometrik Əhatə, Real-time Yoxlama
                </div>
            </div>
        </div>
        <?php
    }
}