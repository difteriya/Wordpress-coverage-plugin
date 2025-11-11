<?php
/**
 * Plugin Name: Map Coverage Plugin with Region Cards
 * Description: Advanced WordPress plugin for managing coverage areas with interactive maps, intelligent search, region cards display, and full Azerbaijani translation. Features include OpenLayers mapping, autocomplete address search, responsive card layouts, Elementor integration, and comprehensive coverage management.
 * Version: 1.1.4
 * Author: Khudiyev
 * Author URI: https://xudiyev.com
 * Text Domain: map-coverage-plugin
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Map_Coverage_Plugin {
    const POST_TYPE = 'coverage_area';
    const META_KEY = '_coverage_geojson';
    const CITY_TAXONOMY = 'coverage_city';
    
    private static $shortcode_used = false;

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_city_taxonomy' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );
        add_action( 'wp_footer', array( $this, 'frontend_localize_data' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'init_settings' ) );
        add_action( 'wp_ajax_search_streets', array( $this, 'ajax_search_streets' ) );
        add_action( 'wp_ajax_nopriv_search_streets', array( $this, 'ajax_search_streets' ) );
        
        // Add Elementor hooks - they will only execute if Elementor is active
        add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ), 10 );
        add_action( 'elementor/elements/categories_registered', array( $this, 'add_elementor_widget_categories' ), 10 );
        
        // Alternative hook for older Elementor versions
        add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_elementor_widgets_legacy' ), 10 );
        
        add_shortcode( 'map_coverage', array( $this, 'shortcode_map' ) );
        add_shortcode( 'map_coverage_search', array( $this, 'shortcode_search_only' ) );
        add_shortcode( 'coverage_search', array( $this, 'shortcode_search_only' ) );
        add_shortcode( 'coverage_cards', array( $this, 'shortcode_region_cards' ) );
        add_shortcode( 'coverage_region_cards', array( $this, 'shortcode_region_cards' ) );
    }

    public function register_post_type() {
        $labels = array(
            'name' => __( 'Əhatə Sahələri', 'map-coverage-plugin' ),
            'singular_name' => __( 'Əhatə Sahəsi', 'map-coverage-plugin' ),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'has_archive' => false,
            'rewrite' => array( 'slug' => 'ehate' ),
            'supports' => array( 'title', 'editor', 'thumbnail' ),
            'capability_type' => 'post',
            'taxonomies' => array( self::CITY_TAXONOMY ),
        );

        register_post_type( self::POST_TYPE, $args );
    }

    public function register_city_taxonomy() {
        $labels = array(
            'name' => __( 'Şəhərlər', 'map-coverage-plugin' ),
            'singular_name' => __( 'Şəhər', 'map-coverage-plugin' ),
            'menu_name' => __( 'Şəhərlər', 'map-coverage-plugin' ),
            'all_items' => __( 'Bütün Şəhərlər', 'map-coverage-plugin' ),
            'edit_item' => __( 'Şəhəri Redaktə Et', 'map-coverage-plugin' ),
            'view_item' => __( 'Şəhərə Bax', 'map-coverage-plugin' ),
            'update_item' => __( 'Şəhəri Yenilə', 'map-coverage-plugin' ),
            'add_new_item' => __( 'Yeni Şəhər Əlavə Et', 'map-coverage-plugin' ),
            'new_item_name' => __( 'Yeni Şəhər Adı', 'map-coverage-plugin' ),
            'search_items' => __( 'Şəhər Axtar', 'map-coverage-plugin' ),
            'not_found' => __( 'Heç bir şəhər tapılmadı', 'map-coverage-plugin' ),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => 'city' ),
            'show_in_rest' => true,
            'meta_box_cb' => 'post_categories_meta_box',
        );

        register_taxonomy( self::CITY_TAXONOMY, self::POST_TYPE, $args );
    }

    public function add_meta_boxes() {
        add_meta_box( 'coverage_map_meta', __( 'Əhatə Geometriyası', 'map-coverage-plugin' ), array( $this, 'render_meta_box' ), self::POST_TYPE, 'normal', 'high' );
    }

    public function render_meta_box( $post ) {
        $geojson = get_post_meta( $post->ID, self::META_KEY, true );
        $addresses = get_post_meta( $post->ID, '_coverage_addresses', true );
        if ( is_string( $addresses ) ) {
            $addresses = maybe_unserialize( $addresses );
        }
        if ( ! $addresses ) {
            $addresses = '';
        }
        if ( ! $geojson ) {
            $geojson = '';
        }
        wp_nonce_field( 'save_coverage_geojson', 'coverage_geojson_nonce' );
        ?>
        <div id="coverage-admin-map" style="width:100%;height:400px;border:1px solid #ddd;margin-bottom:8px"></div>
        <p>
            <button type="button" class="button" id="coverage-draw-point">Nöqtə Çək</button>
            <button type="button" class="button" id="coverage-draw-polygon">Çoxbucaq Çək</button>
            <button type="button" class="button" id="coverage-draw-circle">Dairə Çək</button>
            <button type="button" class="button" id="coverage-clear">Təmizlə</button>
        </p>
        <input type="hidden" id="coverage-geojson" name="coverage_geojson" value="<?php echo esc_attr( $geojson ); ?>" />
        <p class="description">Əhatə sahəsini müəyyən etmək üçün xəritədə fiqurlar çəkin. Geometriya GeoJSON kimi saxlanılacaq.</p>
        <div id="coverage-props" style="display:none;border:1px solid #e1e1e1;padding:8px;margin-top:8px;background:#fff;">
            <h4 style="margin:0 0 8px 0;">Xüsusiyyətlər</h4>
            <p style="margin:0 0 8px 0">
                <label>Başlıq<br/>
                    <input type="text" id="coverage-prop-title" style="width:100%" />
                </label>
            </p>
            <p style="margin:0 0 8px 0">
                <label>Radius (metr)<br/>
                    <input type="number" id="coverage-prop-radius" style="width:100%" />
                </label>
            </p>
            <p style="margin:0 0 8px 0">
                <label>Rəng<br/>
                    <input type="color" id="coverage-prop-color" value="#007bff" />
                </label>
            </p>
            <p style="margin-top:8px">
                <button type="button" class="button button-primary" id="coverage-prop-save">Saxla</button>
                <button type="button" class="button" id="coverage-prop-delete">Sil</button>
                <button type="button" class="button" id="coverage-prop-close">Bağla</button>
            </p>
        </div>
        <div id="coverage-addresses" style="margin-top:12px">
            <h4 style="margin:6px 0">Bu Əhatə Sahəsi üçün Ünvanlar</h4>
            <p class="description">Bu xidmət sahəsinin əhatə etdiyi küçələri və ev nömrələrini əlavə edin. Sadəcə küçə adlarını daxil edin və əhatə sahəsinə daxil olan ev nömrələrini əlavə edin.</p>
            
            <div id="address-rows-container">
                <?php
                if ( is_array( $addresses ) && !empty( $addresses ) ) {
                    foreach ( $addresses as $index => $addr ) {
                        $street = isset($addr['street']) ? esc_attr($addr['street']) : '';
                        $numbers = isset($addr['numbers']) && is_array($addr['numbers']) ? $addr['numbers'] : array();
                        ?>
                        <div class="address-row" data-index="<?php echo $index; ?>">
                            <div class="address-row-header">
                                <input type="text" class="street-input" name="addresses[<?php echo $index; ?>][street]" value="<?php echo $street; ?>" placeholder="Küçə adı" style="width: 300px; margin-right: 10px;" />
                                <button type="button" class="button remove-address-row" style="margin-left: 10px; color: #a00;">Küçəni Sil</button>
                            </div>
                            <div class="numbers-container" style="margin-left: 20px; margin-top: 8px;">
                                <?php foreach ( $numbers as $numIndex => $number ) : ?>
                                <div class="number-row">
                                    <input type="text" class="number-input" name="addresses[<?php echo $index; ?>][numbers][<?php echo $numIndex; ?>]" value="<?php echo esc_attr($number); ?>" placeholder="Nömrə" style="width: 80px; margin-right: 10px;" />
                                    <button type="button" class="button remove-number" style="margin-left: 10px; color: #a00;">Sil</button>
                                </div>
                                <?php endforeach; ?>
                                <div class="add-number-container">
                                    <button type="button" class="button add-number">Nömrə Əlavə Et</button>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="address-row" data-index="0">
                        <div class="address-row-header">
                            <input type="text" class="street-input" name="addresses[0][street]" value="" placeholder="Küçə adı" style="width: 300px; margin-right: 10px;" />
                            <button type="button" class="button remove-address-row" style="margin-left: 10px; color: #a00;">Küçəni Sil</button>
                        </div>
                        <div class="numbers-container" style="margin-left: 20px; margin-top: 8px;">
                            <div class="add-number-container">
                                <button type="button" class="button add-number">Nömrə Əlavə Et</button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <div style="margin-top: 12px;">
                <button type="button" class="button button-primary" id="add-address-row">Küçə Əlavə Et</button>
            </div>
        </div>
        <?php
    }

    public function save_post( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( $post->post_type !== self::POST_TYPE ) {
            return;
        }

        if ( ! isset( $_POST['coverage_geojson_nonce'] ) || ! wp_verify_nonce( $_POST['coverage_geojson_nonce'], 'save_coverage_geojson' ) ) {
            return;
        }

        if ( isset( $_POST['coverage_geojson'] ) ) {
            $geojson_raw = wp_unslash( $_POST['coverage_geojson'] );
            // Basic validation: try to json_decode
            $decoded = json_decode( $geojson_raw );
            if ( $decoded !== null ) {
                update_post_meta( $post_id, self::META_KEY, wp_json_encode( $decoded ) );
            } else {
                // invalid JSON; don't update
            }
        }

        // save addresses
        if ( isset( $_POST['addresses'] ) && is_array( $_POST['addresses'] ) ) {
            $addresses = array();
            foreach ( $_POST['addresses'] as $addr_data ) {
                if ( empty( $addr_data['street'] ) ) continue;
                
                $street = sanitize_text_field( $addr_data['street'] );
                $numbers = array();
                
                if ( isset( $addr_data['numbers'] ) && is_array( $addr_data['numbers'] ) ) {
                    foreach ( $addr_data['numbers'] as $number ) {
                        $number = sanitize_text_field( $number );
                        if ( !empty( $number ) ) {
                            $numbers[] = $number;
                        }
                    }
                }
                
                $addresses[] = array(
                    'street' => $street,
                    'numbers' => $numbers
                );
            }
            update_post_meta( $post_id, '_coverage_addresses', $addresses );
        } else if ( isset( $_POST['coverage_addresses'] ) ) {
            // Legacy fallback for old textarea format
            $addr_raw = wp_unslash( $_POST['coverage_addresses'] );
            $parsed = $this->parse_addresses_text($addr_raw);
            update_post_meta( $post_id, '_coverage_addresses', $parsed );
        }
    }

    private function parse_addresses_text( $text ) {
        $lines = preg_split('/\r?\n/', $text);
        $out = array();
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( $line === '' ) continue;
            if ( strpos( $line, ':' ) !== false ) {
                list( $street, $nums ) = explode(':', $line, 2);
                $street = trim( $street );
                $nums = array_map('trim', explode(',', $nums));
                $nums = array_values(array_filter($nums, function($v){ return $v !== ''; }));
                $out[] = array( 'street' => $street, 'numbers' => $nums );
            } else {
                $out[] = array( 'street' => $line, 'numbers' => array() );
            }
        }
        return $out;
    }

    private function addresses_to_text( $addresses ) {
        if ( ! is_array( $addresses ) ) return '';
        $lines = array();
        foreach ( $addresses as $a ) {
            $street = isset($a['street']) ? $a['street'] : '';
            $nums = isset($a['numbers']) && is_array($a['numbers']) ? implode(',', $a['numbers']) : '';
            if ( $nums !== '' ) $lines[] = $street . ':' . $nums; else $lines[] = $street;
        }
        return implode("\n", $lines);
    }

    public function admin_enqueue( $hook ) {
        global $post_type;
        if ( $post_type !== self::POST_TYPE ) {
            return;
        }

        // OpenLayers from CDN
        wp_enqueue_style( 'ol-css', 'https://cdn.jsdelivr.net/npm/ol@v7.4.0/ol.css' );
        wp_enqueue_script( 'ol-js', 'https://cdn.jsdelivr.net/npm/ol@v7.4.0/dist/ol.js', array(), null, true );
        wp_enqueue_script( 'map-coverage-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.js', array( 'ol-js', 'jquery' ), null, true );
    }

    public function frontend_enqueue() {
        // Only enqueue when shortcode present would be optimal; simple approach: enqueue always
        wp_enqueue_style( 'ol-css', 'https://cdn.jsdelivr.net/npm/ol@v7.4.0/ol.css' );
        wp_enqueue_script( 'ol-js', 'https://cdn.jsdelivr.net/npm/ol@v7.4.0/dist/ol.js', array(), null, true );
        // Turf for point-in-polygon checks
        wp_enqueue_script( 'turf-js', 'https://cdn.jsdelivr.net/npm/@turf/turf@6.5.0/turf.min.js', array(), null, true );
        wp_enqueue_script( 'map-coverage-frontend', plugin_dir_url( __FILE__ ) . 'assets/frontend.js', array( 'ol-js', 'turf-js', 'jquery' ), '1.0.2', true );
        wp_enqueue_style( 'map-coverage-style', plugin_dir_url( __FILE__ ) . 'assets/style.css', array(), '1.0.1' );
    }

    public function frontend_localize_data() {
        if ( ! self::$shortcode_used ) {
            return;
        }
        
        // Query coverage posts and pass GeoJSON and addresses to JS
        $posts = get_posts( array( 'post_type' => self::POST_TYPE, 'numberposts' => -1, 'post_status' => 'publish' ) );
        $features = array();
        $posts_data = array();
        foreach ( $posts as $p ) {
            $g = get_post_meta( $p->ID, self::META_KEY, true );
            $addrs = get_post_meta( $p->ID, '_coverage_addresses', true );
            if ( is_string( $addrs ) ) {
                $addrs = maybe_unserialize( $addrs );
            }
            if ( ! $addrs ) $addrs = array();
            if ( $g ) {
                $decoded = json_decode( $g, true );
                if ( $decoded ) {
                    // If it's a FeatureCollection, merge features, else try to wrap
                    if ( isset( $decoded['type'] ) && $decoded['type'] === 'FeatureCollection' && isset( $decoded['features'] ) ) {
                        foreach ( $decoded['features'] as $f ) {
                            $f['properties'] = isset( $f['properties'] ) ? $f['properties'] : array();
                            $f['properties']['_post_id'] = $p->ID;
                            $f['properties']['_post_title'] = $p->post_title;
                            $features[] = $f;
                        }
                    } else {
                        // Assume single feature or geometry
                        $feature = null;
                        if ( isset( $decoded['type'] ) && isset( $decoded['coordinates'] ) ) {
                            // geometry
                            $feature = array( 'type' => 'Feature', 'geometry' => $decoded, 'properties' => array() );
                        } else if ( isset( $decoded['type'] ) && $decoded['type'] === 'Feature' ) {
                            $feature = $decoded;
                        }
                        if ( $feature ) {
                            $feature['properties'] = isset( $feature['properties'] ) ? $feature['properties'] : array();
                            $feature['properties']['_post_id'] = $p->ID;
                            $feature['properties']['_post_title'] = $p->post_title;
                            $features[] = $feature;
                        }
                    }
                }
            }
            // collect post-level data (addresses and basic info)
            $city_terms = get_the_terms( $p->ID, self::CITY_TAXONOMY );
            $city_name = '';
            if ( $city_terms && ! is_wp_error( $city_terms ) ) {
                $city_name = $city_terms[0]->name;
            }
            
            $posts_data[] = array(
                'id' => $p->ID,
                'title' => $p->post_title,
                'slug' => $p->post_name,
                'city' => $city_name,
                'addresses' => $addrs,
            );
        }

        $collection = array( 'geojson' => array( 'type' => 'FeatureCollection', 'features' => $features ), 'posts' => $posts_data );
        wp_localize_script( 'map-coverage-frontend', 'MAP_COVERAGE_DATA', $collection );
        
        // Localize contact page setting
        $contact_page = get_option( 'map_coverage_contact_page', '' );
        wp_localize_script( 'map-coverage-frontend', 'MAP_COVERAGE_CONTACT_PAGE', $contact_page );
    }

    public function shortcode_map( $atts ) {
        // Mark that shortcode is used on this page
        self::$shortcode_used = true;

        ob_start();
        ?>
        <div class="map-coverage-container">
            <div id="map-coverage-search">
                <h4>🗺️ Ünvanınız üçün Əhatə Yoxlayın</h4>
                <p>Şəhərinizi, rayonunuzu və küçənizi seçin, sonra ev nömrənizi daxil edin ki, xidmətimizdən istifadə edə biləcəyinizi yoxlayaq.</p>
                <div class="map-controls-grid">
                    <select id="map-city-select">
                        <option value="">🏙️ Şəhər Seçin...</option>
                    </select>
                    <select id="map-region-select">
                        <option value="">📍 Rayon Seçin...</option>
                    </select>
                    <input type="text" id="map-street-input" placeholder="🛣️ Küçə adını yazın..." autocomplete="off" />
                    <div id="map-street-suggestions" class="street-suggestions"></div>
                    <input type="text" id="map-number-input" placeholder="🏠 Ev nömrəsi" />
                    <div id="map-number-suggestions" class="number-suggestions"></div>
                    <button type="button" id="map-check-coverage">✨ Əhatəni Yoxla</button>
                </div>
                <div id="map-check-result"></div>
            </div>
            <div id="map-coverage-frontend"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            'Əhatə Tənzimləmələri',
            'Tənzimləmələr',
            'manage_options',
            'map_coverage_settings',
            array( $this, 'settings_page' )
        );
    }

    public function init_settings() {
        register_setting( 'map_coverage_settings', 'map_coverage_contact_page' );
        
        add_settings_section(
            'map_coverage_general',
            'Ümumi Tənzimləmələr',
            array( $this, 'settings_section_callback' ),
            'map_coverage_settings'
        );
        
        add_settings_field(
            'contact_page',
            'Əlaqə Səhifəsi',
            array( $this, 'contact_page_field_callback' ),
            'map_coverage_settings',
            'map_coverage_general'
        );
    }

    public function settings_section_callback() {
        echo '<p>Əhatə xəritəsi plugininin ümumi tənzimləmələri.</p>';
    }

    public function contact_page_field_callback() {
        $value = get_option( 'map_coverage_contact_page', '' );
        $pages = get_pages();
        echo '<select id="map_coverage_contact_page" name="map_coverage_contact_page">';
        echo '<option value="">' . __( 'Seçin...', 'map-coverage-plugin' ) . '</option>';
        foreach ( $pages as $page ) {
            $selected = selected( $value, $page->ID, false );
            echo '<option value="' . esc_attr( $page->ID ) . '" ' . $selected . '>' . esc_html( $page->post_title ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">İstifadəçilər əhatə sahəsində olmadıqda yönləndirilək səhifəni seçin.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Əhatə Xəritəsi Tənzimləmələri</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'map_coverage_settings' );
                do_settings_sections( 'map_coverage_settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function ajax_search_streets() {
        $term = sanitize_text_field( $_GET['term'] ?? '' );
        $city = sanitize_text_field( $_GET['city'] ?? '' );
        $region_id = intval( $_GET['region_id'] ?? 0 );
        
        if ( empty( $term ) || empty( $city ) || empty( $region_id ) ) {
            wp_send_json_error( 'Parametrlər eksik.' );
        }
        
        $posts = get_posts( array( 
            'post_type' => self::POST_TYPE, 
            'numberposts' => -1, 
            'post_status' => 'publish',
            'include' => array( $region_id )
        ) );
        
        $streets = array();
        
        foreach ( $posts as $post ) {
            $city_terms = get_the_terms( $post->ID, self::CITY_TAXONOMY );
            $post_city = '';
            if ( $city_terms && ! is_wp_error( $city_terms ) ) {
                $post_city = $city_terms[0]->name;
            }
            
            if ( $post_city !== $city ) continue;
            
            $addresses = get_post_meta( $post->ID, '_coverage_addresses', true );
            if ( is_string( $addresses ) ) {
                $addresses = maybe_unserialize( $addresses );
            }
            
            if ( is_array( $addresses ) ) {
                foreach ( $addresses as $addr ) {
                    $street = $addr['street'] ?? '';
                    if ( stripos( $street, $term ) !== false ) {
                        $streets[] = array(
                            'label' => $street,
                            'value' => $street,
                            'numbers' => $addr['numbers'] ?? array()
                        );
                    }
                }
            }
        }
        
        // Remove duplicates
        $unique_streets = array();
        $street_names = array();
        foreach ( $streets as $street ) {
            if ( ! in_array( $street['value'], $street_names ) ) {
                $unique_streets[] = $street;
                $street_names[] = $street['value'];
            }
        }
        
        wp_send_json_success( array_slice( $unique_streets, 0, 10 ) ); // Limit to 10 results
    }

    public function shortcode_search_only( $atts ) {
        $atts = shortcode_atts( array(
            'redirect_page' => '',
        ), $atts );
        
        // Mark that shortcode is used on this page
        self::$shortcode_used = true;
        
        ob_start();
        ?>
        <div class="map-coverage-search-only">
            <div id="map-coverage-search-form">
                <h4>🗺️ Ünvanınız üçün Əhatə Yoxlayın</h4>
                <p>Şəhərinizi seçin və xidmət əhatəsini yoxlayın.</p>
                <div class="map-search-controls">
                    <select id="search-city-select" required>
                        <option value="">🏙️ Şəhər Seçin...</option>
                    </select>
                    <button type="submit" id="search-coverage-btn">🔍 Axtar</button>
                </div>
                <div id="search-result-message"></div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const redirectPage = '<?php echo esc_js( $atts['redirect_page'] ); ?>';
            initializeSimpleSearchForm(redirectPage);
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function shortcode_region_cards( $atts ) {
        $atts = shortcode_atts( array(
            'city' => '', // Filter by specific city
            'columns' => '3', // Number of columns (1-5)
            'show_excerpt' => 'true', // Show post excerpt
            'show_city' => 'true', // Show city name
            'order' => 'ASC', // ASC or DESC
            'orderby' => 'title', // title, date, menu_order, rand
            'limit' => -1, // Number of posts to show (-1 for all)
            'clickable_images' => 'true', // Make images clickable
        ), $atts );
        
        // Query coverage posts
        $query_args = array( 
            'post_type' => self::POST_TYPE, 
            'numberposts' => intval( $atts['limit'] ), 
            'post_status' => 'publish',
            'order' => $atts['order'],
            'orderby' => $atts['orderby']
        );
        
        // Filter by city if specified
        if ( ! empty( $atts['city'] ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => self::CITY_TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => $atts['city'],
                ),
            );
        }
        
        $posts = get_posts( $query_args );
        
        if ( empty( $posts ) ) {
            return '<p class="coverage-cards-empty">Əhatə sahəsi tapılmadı.</p>';
        }
        
        // Validate columns value
        $columns = intval( $atts['columns'] );
        if ( $columns < 1 || $columns > 5 ) {
            $columns = 3;
        }
        
        ob_start();
        ?>
        <div class="coverage-cards-container">
            <div class="coverage-cards-grid coverage-cards-columns-<?php echo esc_attr( $columns ); ?>">
                <?php foreach ( $posts as $post ) : 
                    $city_terms = get_the_terms( $post->ID, self::CITY_TAXONOMY );
                    $city_name = '';
                    if ( $city_terms && ! is_wp_error( $city_terms ) ) {
                        $city_name = $city_terms[0]->name;
                    }
                    
                    $featured_image = get_the_post_thumbnail_url( $post->ID, 'medium' );
                    $default_image = plugin_dir_url( __FILE__ ) . 'assets/default-region.jpg';
                    $image_url = $featured_image ? $featured_image : $default_image;
                    ?>
                    <div class="coverage-card">
                        <div class="coverage-card-image">
                            <?php if ( $atts['clickable_images'] === 'true' ) : ?>
                                <a href="/ehate/<?php echo esc_attr( $post->post_name ); ?>" title="<?php echo esc_attr( $post->post_title ); ?>">
                                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $post->post_title ); ?>" />
                                </a>
                            <?php else : ?>
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $post->post_title ); ?>" />
                            <?php endif; ?>
                            <?php if ( $atts['show_city'] === 'true' && $city_name ) : ?>
                                <div class="coverage-card-city"><?php echo esc_html( $city_name ); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="coverage-card-content">
                            <h3 class="coverage-card-title">
                                <a href="/ehate/<?php echo esc_attr( $post->post_name ); ?>"><?php echo esc_html( $post->post_title ); ?></a>
                            </h3>
                            <?php if ( $atts['show_excerpt'] === 'true' && $post->post_excerpt ) : ?>
                                <p class="coverage-card-excerpt"><?php echo esc_html( wp_trim_words( $post->post_excerpt, 20 ) ); ?></p>
                            <?php elseif ( $atts['show_excerpt'] === 'true' ) : ?>
                                <p class="coverage-card-excerpt"><?php echo esc_html( wp_trim_words( $post->post_content, 20 ) ); ?></p>
                            <?php endif; ?>
                            <div class="coverage-card-meta">
                                <?php 
                                $addresses = get_post_meta( $post->ID, '_coverage_addresses', true );
                                if ( is_string( $addresses ) ) {
                                    $addresses = maybe_unserialize( $addresses );
                                }
                                $street_count = is_array( $addresses ) ? count( $addresses ) : 0;
                                ?>
                                <span class="coverage-card-streets">📍 <?php echo $street_count; ?> küçə</span>
                            </div>
                        </div>
                        <div class="coverage-card-footer">
                            <a href="/ehate/<?php echo esc_attr( $post->post_name ); ?>" class="coverage-card-button">
                                Ətraflı Məlumat
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function add_elementor_widget_categories( $elements_manager ) {
        // Check if elements manager is available and method exists
        if ( ! $elements_manager || ! method_exists( $elements_manager, 'add_category' ) ) {
            return;
        }
        
        $elements_manager->add_category(
            'coverage-category',
            [
                'title' => __( 'Əhatə Xəritəsi', 'map-coverage-plugin' ),
                'icon' => 'fa fa-map',
            ]
        );
    }

    public function register_elementor_widgets_legacy( $widgets_manager = null ) {
        // For older Elementor versions
        if ( ! $widgets_manager ) {
            $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
        }
        
        $this->register_elementor_widgets( $widgets_manager );
    }

    public function register_elementor_widgets( $widgets_manager ) {
        // Debug logging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( "Map Coverage Plugin: Attempting to register Elementor widgets" );
        }
        
        // Check if Elementor is active and widget manager is available
        if ( ! $widgets_manager || ! class_exists( '\Elementor\Widget_Base' ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( "Map Coverage Plugin: Elementor not available or Widget_Base class missing" );
            }
            return;
        }
        
        // Get plugin directory path
        $plugin_dir = plugin_dir_path( __FILE__ );
        
        // Define widget files with their class names
        $widget_files = [
            'widgets/map-widget.php' => 'Map_Coverage_Map_Widget',
            'widgets/search-widget.php' => 'Map_Coverage_Search_Widget', 
            'widgets/cards-widget.php' => 'Map_Coverage_Cards_Widget'
        ];
        
        foreach ( $widget_files as $file => $class_name ) {
            $full_path = $plugin_dir . $file;
            
            if ( file_exists( $full_path ) ) {
                require_once( $full_path );
                
                // Check if class exists and register it
                if ( class_exists( $class_name ) ) {
                    try {
                        $widgets_manager->register( new $class_name() );
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( "Map Coverage Plugin: Successfully registered widget: " . $class_name );
                        }
                    } catch ( Exception $e ) {
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( "Map Coverage Plugin: Error registering widget " . $class_name . ": " . $e->getMessage() );
                        }
                    }
                } else {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( "Map Coverage Plugin: Widget class not found: " . $class_name );
                    }
                }
            } else {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( "Map Coverage Plugin: Widget file not found: " . $full_path );
                }
            }
        }
    }
}

new Map_Coverage_Plugin();
