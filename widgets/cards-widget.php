<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Map_Coverage_Cards_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'map_coverage_cards';
    }

    public function get_title() {
        return __( 'Rayon Kartları', 'map-coverage-plugin' );
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return [ 'coverage-category' ];
    }

    public function get_keywords() {
        return [ 'cards', 'region', 'grid', 'kartlar', 'rayon', 'əhatə' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Kart Tənzimləmələri', 'map-coverage-plugin' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => __( 'Sütun Sayı', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => __( '1 Sütun', 'map-coverage-plugin' ),
                    '2' => __( '2 Sütun', 'map-coverage-plugin' ),
                    '3' => __( '3 Sütun', 'map-coverage-plugin' ),
                    '4' => __( '4 Sütun', 'map-coverage-plugin' ),
                    '5' => __( '5 Sütun', 'map-coverage-plugin' ),
                ],
            ]
        );

        $this->add_control(
            'city_filter',
            [
                'label' => __( 'Şəhər Filtri', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __( 'şəhər-slug-i (məs: baki)', 'map-coverage-plugin' ),
                'description' => __( 'Yalnız müəyyən şəhərin rayonlarını göstər', 'map-coverage-plugin' ),
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => __( 'Kart Sayı', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 12,
                'min' => 1,
                'max' => 50,
                'description' => __( 'Göstərilən kart sayının limiti', 'map-coverage-plugin' ),
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
            ]
        );

        $this->add_control(
            'show_city_badge',
            [
                'label' => __( 'Şəhər Nişanı Göstər', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Bəli', 'map-coverage-plugin' ),
                'label_off' => __( 'Xeyr', 'map-coverage-plugin' ),
                'return_value' => 'true',
                'default' => 'true',
            ]
        );

        $this->add_control(
            'show_street_count',
            [
                'label' => __( 'Küçə Sayını Göstər', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Bəli', 'map-coverage-plugin' ),
                'label_off' => __( 'Xeyr', 'map-coverage-plugin' ),
                'return_value' => 'true',
                'default' => 'true',
            ]
        );

        $this->add_control(
            'clickable_images',
            [
                'label' => __( 'Şəkilləri Kliklənə bilər et', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Bəli', 'map-coverage-plugin' ),
                'label_off' => __( 'Xeyr', 'map-coverage-plugin' ),
                'return_value' => 'true',
                'default' => 'true',
                'description' => __( 'Şəkilləri kliklədikdə post səhifəsinə yönləndir', 'map-coverage-plugin' ),
            ]
        );

        $this->add_control(
            'order_by',
            [
                'label' => __( 'Sıralama', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'title',
                'options' => [
                    'title' => __( 'Başlığa görə', 'map-coverage-plugin' ),
                    'date' => __( 'Tarixə görə', 'map-coverage-plugin' ),
                    'menu_order' => __( 'Menyu sırasına görə', 'map-coverage-plugin' ),
                    'rand' => __( 'Təsadüfi', 'map-coverage-plugin' ),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => __( 'Sıralama İstiqaməti', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'ASC',
                'options' => [
                    'ASC' => __( 'Artan (A-Z)', 'map-coverage-plugin' ),
                    'DESC' => __( 'Azalan (Z-A)', 'map-coverage-plugin' ),
                ],
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __( 'Düymə Mətni', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Ətraflı Məlumat', 'map-coverage-plugin' ),
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __( 'Kart Stilləri', 'map-coverage-plugin' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_background',
            [
                'label' => __( 'Kart Arxa Plan', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .coverage-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label' => __( 'Kart Künc Radiusu', 'map-coverage-plugin' ),
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
                    '{{WRAPPER}} .coverage-card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_box_shadow',
                'label' => __( 'Kart Kölgəsi', 'map-coverage-plugin' ),
                'selector' => '{{WRAPPER}} .coverage-card',
            ]
        );

        $this->add_control(
            'card_spacing',
            [
                'label' => __( 'Kartlar Arası Məsafə', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 50,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .coverage-cards-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __( 'Başlıq Tipoqrafiya', 'map-coverage-plugin' ),
                'selector' => '{{WRAPPER}} .coverage-card-title',
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label' => __( 'Düymə Arxa Plan', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#6366f1',
                'selectors' => [
                    '{{WRAPPER}} .coverage-card-button' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .coverage-card-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Image Style Section
        $this->start_controls_section(
            'image_style_section',
            [
                'label' => __( 'Şəkil Stilləri', 'map-coverage-plugin' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'image_height',
            [
                'label' => __( 'Şəkil Hündürlüyü', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 150,
                        'max' => 400,
                        'step' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 200,
                ],
                'selectors' => [
                    '{{WRAPPER}} .coverage-card-image' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'image_border_radius',
            [
                'label' => __( 'Şəkil Künc Radiusu', 'map-coverage-plugin' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .coverage-card-image img' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Check if there are any coverage areas
        $test_query = get_posts( array( 
            'post_type' => 'coverage_area', 
            'numberposts' => 1, 
            'post_status' => 'publish'
        ) );
        
        if ( empty( $test_query ) ) {
            echo '<div style="background: #f0f8ff; border: 2px dashed #4285f4; padding: 20px; border-radius: 8px; text-align: center; color: #1a73e8; font-family: Arial, sans-serif;">
                <h3 style="margin: 0 0 10px 0; color: #1a73e8;">📋 Rayon Kartları Widget</h3>
                <p style="margin: 0;">Hələ ki heç bir əhatə sahəsi yaradılmayıb. Widgetin işləməsi üçün ilk öncə admin paneldən əhatə sahələri əlavə edin.</p>
                <small style="opacity: 0.8;">Admin → Əhatə Sahələri → Yeni Əlavə Et</small>
            </div>';
            return;
        }
        
        $shortcode_atts = [
            'columns' => $settings['columns'],
            'show_excerpt' => $settings['show_excerpt'],
            'show_city' => $settings['show_city_badge'],
            'order' => $settings['order'],
            'orderby' => $settings['order_by'],
            'clickable_images' => $settings['clickable_images'],
        ];
        
        if ( ! empty( $settings['city_filter'] ) ) {
            $shortcode_atts['city'] = $settings['city_filter'];
        }
        
        if ( ! empty( $settings['posts_per_page'] ) ) {
            $shortcode_atts['limit'] = $settings['posts_per_page'];
        }
        
        // Build shortcode string
        $shortcode = '[coverage_region_cards';
        foreach ( $shortcode_atts as $key => $value ) {
            $shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
        }
        $shortcode .= ']';
        
        // Add custom button text override
        if ( ! empty( $settings['button_text'] ) ) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    setTimeout(function() {
                        var buttons = document.querySelectorAll(".coverage-card-button");
                        buttons.forEach(function(button) {
                            button.textContent = "' . esc_js( $settings['button_text'] ) . '";
                        });
                    }, 100);
                });
            </script>';
        }
        
        // Custom styling for street count visibility
        if ( $settings['show_street_count'] !== 'true' ) {
            echo '<style>{{WRAPPER}} .coverage-card-streets { display: none !important; }</style>';
        }
        
        echo do_shortcode( $shortcode );
    }

    protected function content_template() {
        ?>
        <div class="elementor-widget-container">
            <div class="coverage-widget-preview" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 25px; border-radius: 12px; text-align: center; color: white;">
                <h3 style="margin: 0 0 10px 0; color: white;">📋 Rayon Kartları</h3>
                <p style="margin: 0; opacity: 0.9;">Responsive grid layout ilə region kartları</p>
                <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                    <strong>Xüsusiyyətlər:</strong> <span id="columns-preview">3</span> Sütun, Şəkillər, Filtrlər, Custom Stil
                </div>
            </div>
        </div>
        <script>
        // Update preview based on columns setting
        if (typeof elementor !== 'undefined') {
            elementor.hooks.addAction('panel/open_editor/widget/map_coverage_cards', function(panel, model, view) {
                model.on('change:settings:columns', function(model) {
                    var columns = model.get('settings').get('columns');
                    var preview = document.querySelector('#columns-preview');
                    if (preview) {
                        preview.textContent = columns;
                    }
                });
            });
        }
        </script>
        <?php
    }
}