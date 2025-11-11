<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Map_Coverage_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'map_coverage_widget';
    }

    public function get_title() {
        return __( 'Əhatə Xəritəsi', 'map-coverage-plugin' );
    }

    public function get_icon() {
        return 'eicon-map-pin';
    }

    public function get_categories() {
        return [ 'coverage-category' ];
    }

    public function get_keywords() {
        return [ 'map', 'coverage', 'xəritə', 'əhatə' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Tənzimləmələr', 'map-coverage-plugin' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'widget_type',
            [
                'label' => __( 'Vidcet Növü', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'full_map',
                'options' => [
                    'full_map' => __( 'Tam Xəritə + Axtarış', 'map-coverage-plugin' ),
                    'search_only' => __( 'Yalnız Axtarış Formu', 'map-coverage-plugin' ),
                    'region_cards' => __( 'Rayon Kartları', 'map-coverage-plugin' ),
                ],
            ]
        );

        $this->add_control(
            'redirect_page',
            [
                'label' => __( 'Yönləndirmə Səhifəsi', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => '/ehate/rayon-adi',
                'description' => __( 'Axtarış nəticəsində yönləndiriləcək səhifə', 'map-coverage-plugin' ),
                'condition' => [
                    'widget_type' => 'search_only',
                ],
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => __( 'Sütun Sayı', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1 Sütun',
                    '2' => '2 Sütun',
                    '3' => '3 Sütun',
                    '4' => '4 Sütun',
                ],
                'condition' => [
                    'widget_type' => 'region_cards',
                ],
            ]
        );

        $this->add_control(
            'city_filter',
            [
                'label' => __( 'Şəhər Filtri', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'şəhər-slug-i',
                'description' => __( 'Yalnız müəyyən şəhərin rayonlarını göstər', 'map-coverage-plugin' ),
                'condition' => [
                    'widget_type' => 'region_cards',
                ],
            ]
        );

        $this->add_control(
            'show_excerpt',
            [
                'label' => __( 'Qısa Mətn Göstər', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Bəli', 'map-coverage-plugin' ),
                'label_off' => __( 'Xeyr', 'map-coverage-plugin' ),
                'return_value' => 'true',
                'default' => 'true',
                'condition' => [
                    'widget_type' => 'region_cards',
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
                    '{{WRAPPER}} .coverage-cards-container' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $type = $settings['widget_type'];
        
        if ( $type === 'full_map' ) {
            echo do_shortcode( '[map_coverage]' );
        } elseif ( $type === 'search_only' ) {
            $redirect_page = ! empty( $settings['redirect_page'] ) ? $settings['redirect_page'] : '';
            echo do_shortcode( '[coverage_search redirect_page="' . esc_attr( $redirect_page ) . '"]' );
        } elseif ( $type === 'region_cards' ) {
            $columns = ! empty( $settings['columns'] ) ? $settings['columns'] : '3';
            $city_filter = ! empty( $settings['city_filter'] ) ? $settings['city_filter'] : '';
            $show_excerpt = ! empty( $settings['show_excerpt'] ) ? 'true' : 'false';
            
            $shortcode = '[coverage_region_cards columns="' . esc_attr( $columns ) . '"';
            if ( ! empty( $city_filter ) ) {
                $shortcode .= ' city="' . esc_attr( $city_filter ) . '"';
            }
            $shortcode .= ' show_excerpt="' . esc_attr( $show_excerpt ) . '"]';
            
            echo do_shortcode( $shortcode );
        }
    }

    protected function content_template() {
        ?>
        <div class="elementor-widget-container">
            <div class="coverage-widget-preview">
                <# if ( 'full_map' === settings.widget_type ) { #>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                        <h4>🗺️ Əhatə Xəritəsi</h4>
                        <p>Tam xəritə və axtarış formu</p>
                    </div>
                <# } else if ( 'search_only' === settings.widget_type ) { #>
                    <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
                        <h4>🔍 Axtarış Formu</h4>
                        <p>Yalnız axtarış formu</p>
                    </div>
                <# } else if ( 'region_cards' === settings.widget_type ) { #>
                    <div style="background: #f3e5f5; padding: 20px; border-radius: 8px; text-align: center;">
                        <h4>📋 Rayon Kartları</h4>
                        <p>{{ settings.columns }} sütun düzümü</p>
                    </div>
                <# } #>
            </div>
        </div>
        <?php
    }
}