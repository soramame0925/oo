<?php
/**
 * Plugin Name: MNO Post Manager
 * Description: Provides structured meta fields and front-end rendering for posts.
 * Version: 1.3.0
 * Author: OpenAI Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MNO_Post_Manager {
    const META_PREFIX = '_mpm_';

    const VOICE_TYPE_OPTIONS = [
        'very_low'      => '非常に低い',
        'low'           => '低い',
        'mid'           => '中間（ナチュラル）',
        'high'          => '高い',
        'very_high'     => '非常に高い',
        'multiple'      => '複数人',
        'command_shift' => '命令口調に変化',
    ];

    const VOICE_TYPE_LEGACY_MAP = [
        'calm'     => 'low',
        'sweet'    => 'very_high',
        'mechanic' => 'mid',
        'sister'   => 'command_shift',
        'loli'     => 'high',
        'tsundere' => 'high',
        'boyish'   => 'mid',
        'mature'   => 'multiple',
        'sadistic' => 'high',
    ];

    const LEVEL_OPTIONS = [
        'soft'   => 'ソフト',
        'medium' => '中間',
        'hard'   => 'ハード',
    ];

    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'register_meta_boxes' ] );
        add_action( 'save_post', [ __CLASS__, 'save_post' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
    }

    public static function register_meta_boxes() {
        add_meta_box(
            'mno-post-manager',
            __( '投稿管理', 'mno-post-manager' ),
            [ __CLASS__, 'render_meta_box' ],
            'post',
            'normal',
            'high'
        );

        add_meta_box(
            'mno-post-manager-data',
            __( 'データ', 'mno-post-manager' ),
            [ __CLASS__, 'render_data_meta_box' ],
            'post',
            'normal',
            'high'
        );
    }

    public static function render_meta_box( $post ) {
        wp_nonce_field( 'mno_pm_save_post', 'mno_pm_nonce' );

        $values = self::get_post_values( $post->ID );

        include __DIR__ . '/partials/meta-box.php';
    }

    public static function render_data_meta_box( $post ) {
        $values       = self::get_post_values( $post->ID );
        $voice_types  = self::get_voice_type_options();
        $level_labels = self::get_level_options();

        include __DIR__ . '/partials/meta-box-data.php';
    }

    private static function get_post_values( $post_id ) {
        $defaults = [
            'gallery'       => [],
            'voice_sample'  => '',
            'normal_price'  => '',
            'sale_price'    => '',
            'sale_end_date' => '',
            'buy_url'       => '',
            'data_bars'     => [],
            'data_voice'    => [],
            'data_level'    => '',
        ];

        $data = [];
        foreach ( $defaults as $key => $default ) {
            $meta_key = self::META_PREFIX . $key;
            $value    = get_post_meta( $post_id, $meta_key, true );
            if ( '' === $value || null === $value ) {
                $value = $default;
            }
            $data[ $key ] = $value;
        }

        $data['gallery'] = is_array( $data['gallery'] ) ? array_map( 'intval', $data['gallery'] ) : [];

        $data_bars = [];
        if ( is_array( $data['data_bars'] ) ) {
            foreach ( $data['data_bars'] as $entry ) {
                if ( ! is_array( $entry ) ) {
                    continue;
                }

                $label = isset( $entry['label'] ) ? sanitize_text_field( $entry['label'] ) : '';
                $track = isset( $entry['track'] ) ? sanitize_text_field( $entry['track'] ) : '';

                $count = '';
                if ( isset( $entry['count'] ) && '' !== $entry['count'] && null !== $entry['count'] ) {
                    $count = (string) absint( $entry['count'] );
                }

                if ( '' === $label && '' === $track && '' === $count ) {
                    continue;
                }

                $data_bars[] = [
                    'label' => $label,
                    'track' => $track,
                    'count' => $count,
                ];
            }
        }
        $data['data_bars'] = $data_bars;

        $voice_entries = [];
        if ( is_array( $data['data_voice'] ) ) {
            $options    = array_keys( self::get_voice_type_options() );
            $legacy_map = self::VOICE_TYPE_LEGACY_MAP;
            foreach ( $data['data_voice'] as $entry ) {
                if ( ! is_array( $entry ) ) {
                    continue;
                }

                $name  = isset( $entry['name'] ) ? sanitize_text_field( $entry['name'] ) : '';
                $types = [];
                if ( isset( $entry['types'] ) && is_array( $entry['types'] ) ) {
                    foreach ( $entry['types'] as $type_key ) {
                        $type_key = sanitize_key( $type_key );
                        if ( isset( $legacy_map[ $type_key ] ) ) {
                            $type_key = $legacy_map[ $type_key ];
                        }
                        if ( in_array( $type_key, $options, true ) ) {
                            $types[] = $type_key;
                        }
                    }
                }

                if ( '' === $name && empty( $types ) ) {
                    continue;
                }

                $voice_entries[] = [
                    'name'  => $name,
                    'types' => array_values( array_unique( $types ) ),
                ];
            }
        }
        $data['data_voice'] = $voice_entries;

        $level = isset( $data['data_level'] ) ? sanitize_key( $data['data_level'] ) : '';
        $data['data_level'] = array_key_exists( $level, self::get_level_options() ) ? $level : '';

       

        return wp_parse_args( $data, $defaults );
    }

    public static function save_post( $post_id, $post ) {
        if ( ! isset( $_POST['mno_pm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mno_pm_nonce'] ) ), 'mno_pm_save_post' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( 'post' !== $post->post_type ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = [
           'voice_sample'  => [ __CLASS__, 'sanitize_voice_sample' ],
            'normal_price'  => 'sanitize_text_field',
            'sale_price'    => 'sanitize_text_field',
            'sale_end_date' => 'sanitize_text_field',
            'buy_url'       => 'esc_url_raw',
        ];

        foreach ( $fields as $key => $sanitize_callback ) {
            $raw = isset( $_POST[ 'mno_pm_' . $key ] ) ? wp_unslash( $_POST[ 'mno_pm_' . $key ] ) : '';
            $value = '';
            if ( '' !== $raw ) {
                $value = call_user_func( $sanitize_callback, $raw );
            }
            update_post_meta( $post_id, self::META_PREFIX . $key, $value );
        }

        $gallery_ids = [];
        if ( isset( $_POST['mno_pm_gallery'] ) && is_array( $_POST['mno_pm_gallery'] ) ) {
            $gallery_ids = array_filter( array_map( 'intval', wp_unslash( $_POST['mno_pm_gallery'] ) ) );
        }
        update_post_meta( $post_id, self::META_PREFIX . 'gallery', $gallery_ids );

        
        delete_post_meta( $post_id, self::META_PREFIX . 'sample_lines' );

        $data_bars = [];
        if ( isset( $_POST['mno_pm_data_bars'] ) && is_array( $_POST['mno_pm_data_bars'] ) ) {
            foreach ( wp_unslash( $_POST['mno_pm_data_bars'] ) as $entry ) {
                if ( ! is_array( $entry ) ) {
                    continue;
                }

                $label = isset( $entry['label'] ) ? sanitize_text_field( $entry['label'] ) : '';
                $track = isset( $entry['track'] ) ? sanitize_text_field( $entry['track'] ) : '';

                $count = '';
                if ( isset( $entry['count'] ) && '' !== $entry['count'] && null !== $entry['count'] ) {
                    $count = absint( $entry['count'] );
                }

                if ( '' === $label && '' === $track && '' === $count ) {
                    continue;
                }

                $data_bars[] = [
                    'label' => $label,
                    'track' => $track,
                    'count' => '' === $count ? '' : $count,
                ];
            }
        }
        update_post_meta( $post_id, self::META_PREFIX . 'data_bars', $data_bars );

        $voice_entries = [];
        if ( isset( $_POST['mno_pm_data_voice'] ) && is_array( $_POST['mno_pm_data_voice'] ) ) {
            $allowed    = array_keys( self::get_voice_type_options() );
            $legacy_map = self::VOICE_TYPE_LEGACY_MAP;
            foreach ( wp_unslash( $_POST['mno_pm_data_voice'] ) as $entry ) {
                if ( ! is_array( $entry ) ) {
                    continue;
                }

                $name  = isset( $entry['name'] ) ? sanitize_text_field( $entry['name'] ) : '';
                $types = [];
                if ( isset( $entry['types'] ) && is_array( $entry['types'] ) ) {
                    foreach ( $entry['types'] as $type_key ) {
                        $type_key = sanitize_key( $type_key );
                        if ( isset( $legacy_map[ $type_key ] ) ) {
                            $type_key = $legacy_map[ $type_key ];
                        }
                        if ( in_array( $type_key, $allowed, true ) ) {
                            $types[] = $type_key;
                        }
                    }
                }

                if ( '' === $name && empty( $types ) ) {
                    continue;
                }

                $voice_entries[] = [
                    'name'  => $name,
                    'types' => array_values( array_unique( $types ) ),
                ];
            }
        }
        update_post_meta( $post_id, self::META_PREFIX . 'data_voice', $voice_entries );

        $level = '';
        if ( isset( $_POST['mno_pm_data_level'] ) ) {
            $level = sanitize_key( wp_unslash( $_POST['mno_pm_data_level'] ) );
        }
        $level_options = array_keys( self::get_level_options() );
        update_post_meta(
            $post_id,
            self::META_PREFIX . 'data_level',
            in_array( $level, $level_options, true ) ? $level : ''
        );

        $sale_price    = get_post_meta( $post_id, self::META_PREFIX . 'sale_price', true );
        $sale_end_date = get_post_meta( $post_id, self::META_PREFIX . 'sale_end_date', true );

        if ( $sale_price ) {
            $timestamp = $sale_end_date ? strtotime( $sale_end_date . ' 23:59:59' ) : false;
            if ( $timestamp && $timestamp < current_time( 'timestamp' ) ) {
                update_post_meta( $post_id, self::META_PREFIX . 'sale_price', '' );
            }
        }
    }

    public static function get_voice_sample_allowed_tags() {
        $allowed = wp_kses_allowed_html( 'post' );

        $allowed['iframe'] = [
            'src'             => true,
            'width'           => true,
            'height'          => true,
            'frameborder'     => true,
            'allow'           => true,
            'allowfullscreen' => true,
            'loading'         => true,
            'title'           => true,
            'referrerpolicy'  => true,
        ];

        return $allowed;
    }

    private static function sanitize_voice_sample( $value ) {
        return wp_kses( $value, self::get_voice_sample_allowed_tags() );
    }

    public static function get_voice_type_options() {
        return self::VOICE_TYPE_OPTIONS;
    }

    public static function get_level_options() {
        return self::LEVEL_OPTIONS;
    }

    public static function enqueue_admin_assets( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style( 'mno-pm-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.css', [], '1.2.0' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'mno-pm-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.js', [ 'jquery', 'jquery-ui-sortable' ], '1.2.0', true );
    }

    public static function enqueue_frontend_assets() {
        if ( ! is_single() ) {
            return;
        }

        $script_version = file_exists( __DIR__ . '/assets/frontend.js' ) ? filemtime( __DIR__ . '/assets/frontend.js' ) : '1.3.0';
        wp_enqueue_script( 'mno-pm-frontend', plugin_dir_url( __FILE__ ) . 'assets/frontend.js', [], $script_version, true );
        wp_localize_script(
            'mno-pm-frontend',
            'mnoPmSlider',
            [
                'i18n' => [
                    'next'  => __( 'Next', 'mno-post-manager' ),
                    'prev'  => __( 'Previous', 'mno-post-manager' ),
                    'slide' => __( 'Go to slide %d', 'mno-post-manager' ),
                ],
            ]
        );
    }

    public static function get_post_data( $post_id = null ) {
        $post_id = $post_id ?: get_the_ID();
        if ( ! $post_id ) {
            return [];
        }

        return self::get_post_values( $post_id );
    }
}

MNO_Post_Manager::init();

function mno_pm_render_single_template( $post_id = null ) {
    $post_id = $post_id ?: get_the_ID();
    if ( ! $post_id ) {
        return '';
    }

    $data = MNO_Post_Manager::get_post_data( $post_id );

    ob_start();
    include __DIR__ . '/partials/frontend-template.php';
    return ob_get_clean();
}