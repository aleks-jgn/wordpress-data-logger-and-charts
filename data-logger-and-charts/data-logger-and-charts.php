<?php
/**
 * Plugin Name:     Data Logger & Charts
 * Description:     Fetches data daily or uses a manual JSON and displays a Chart.js graph via shortcode.
 * Version:         1.5
 * Author:          aleks-jgn
 * Author URI:      https://github.com/aleks-jgn
 * License:         GNU AFFERO GENERAL PUBLIC LICENSE Version 3 (AGPL-3.0 license)
 * Text Domain:     token-holder-data
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ============================================================================
 * PLUGIN ACTIVATION / DEACTIVATION
 * ============================================================================
 */

function crypto_activate_plugin() {
    $upload_dir = wp_upload_dir();
    $base_dir   = trailingslashit( $upload_dir['basedir'] );

    $files = [ 'cdlc-data.json', 'cdlc-data-custom.json' ];
    foreach ( $files as $filename ) {
        $file_path = $base_dir . $filename;
        if ( ! file_exists( $file_path ) ) {
            file_put_contents( $file_path, wp_json_encode( [], JSON_PRETTY_PRINT ), LOCK_EX );
        }
    }

    if ( ! wp_next_scheduled( 'crypto_daily_fetch' ) ) {
        wp_schedule_event( strtotime( 'tomorrow midnight', current_time( 'timestamp' ) ), 'daily', 'crypto_daily_fetch' );
    }

    if ( false === get_option( 'crypto_data_mode' ) ) {
        update_option( 'crypto_data_mode', 'auto' );
    }
}
register_activation_hook( __FILE__, 'crypto_activate_plugin' );

function crypto_deactivate_plugin() {
    wp_clear_scheduled_hook( 'crypto_daily_fetch' );
}
register_deactivation_hook( __FILE__, 'crypto_deactivate_plugin' );

/**
 * ============================================================================
 * ADMIN SETTINGS PAGE
 * ============================================================================
 */

function crypto_add_admin_menu() {
    add_options_page(
        __( 'Data Logger & Charts Settings', 'token-holder-data' ),
        __( 'Data Logger & Charts', 'token-holder-data' ),
        'manage_options',
        'crypto-data-logger',
        'crypto_render_settings_page'
    );
}
add_action( 'admin_menu', 'crypto_add_admin_menu' );

function crypto_register_settings() {
    register_setting(
        'crypto_data_logger_settings',
        'crypto_data_mode',
        [
            'type'              => 'string',
            'sanitize_callback' => function( $value ) {
                return in_array( $value, [ 'auto', 'custom' ], true ) ? $value : 'auto';
            },
            'default'           => 'auto',
        ]
    );

    register_setting(
        'crypto_data_logger_settings',
        'crypto_api_url',
        [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => 'https://polygon.blockscout.com/api/v2/tokens/0xc2132D05D31c914a87C6611C10748AEb04B58e8F/',
        ]
    );

    register_setting(
        'crypto_data_logger_settings',
        'crypto_chart_height',
        [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 200,
        ]
    );
}
add_action( 'admin_init', 'crypto_register_settings' );

function crypto_render_settings_page() {
    $mode         = get_option( 'crypto_data_mode', 'auto' );
    $api_url      = get_option( 'crypto_api_url' );
    $chart_height = get_option( 'crypto_chart_height', 200 );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Data Logger & Charts Settings', 'token-holder-data' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'crypto_data_logger_settings' );
            do_settings_sections( 'crypto-data-logger' );
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Data Source Mode', 'token-holder-data' ); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="crypto_data_mode" value="auto" <?php checked( 'auto', $mode ); ?> />
                                <?php esc_html_e( 'Automatic (fetch from API daily)', 'token-holder-data' ); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="crypto_data_mode" value="custom" <?php checked( 'custom', $mode ); ?> />
                                <?php esc_html_e( 'Manual (use data from "cdlc-data-custom.json")', 'token-holder-data' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="crypto_api_url"><?php esc_html_e( 'API URL', 'token-holder-data' ); ?></label>
                    </th>
                    <td>
                        <input type="url" name="crypto_api_url" id="crypto_api_url"
                            value="<?php echo esc_attr( $api_url ); ?>"
                            class="regular-text" />
                        <p class="description">
                            <?php esc_html_e( 'Endpoint returning token data (must contain "holders_count" field). Used only in automatic mode.', 'token-holder-data' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="crypto_chart_height"><?php esc_html_e( 'Chart Height (px)', 'token-holder-data' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="crypto_chart_height" id="crypto_chart_height"
                            value="<?php echo esc_attr( $chart_height ); ?>"
                            class="small-text" min="100" step="1" />
                        <p class="description"><?php esc_html_e( 'Height of the chart container in pixels. Width is always 100% of the parent element.', 'token-holder-data' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr>
        <h2><?php esc_html_e( 'Usage Instructions', 'token-holder-data' ); ?></h2>
        <ol>
            <li>
                <strong><?php esc_html_e( 'Shortcode', 'token-holder-data' ); ?>:</strong>
                <code>[crypto_chart]</code> – <?php esc_html_e( 'insert the chart into any post, page or widget.', 'token-holder-data' ); ?>
                <?php esc_html_e( 'In PHP templates use:', 'token-holder-data' ); ?>
                <code>&lt;?php echo do_shortcode('[crypto_chart]'); ?&gt;</code>
            </li>
            <li>
                <strong><?php esc_html_e( 'Mode switching', 'token-holder-data' ); ?>:</strong>
                <?php esc_html_e( 'Choose "Automatic" to let the plugin fetch data via the API daily and store it in "cdlc-data.json". Choose "Manual" to read data from "cdlc-data-custom.json" that you maintain yourself.', 'token-holder-data' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Custom JSON file format', 'token-holder-data' ); ?>:</strong>
                <?php esc_html_e( 'The file must be a valid JSON array of objects with "date" (YYYY-MM-DD) and "value" (integer):', 'token-holder-data' ); ?>
                <pre>[
  {"date":"2025-01-01","value":1024},
  {"date":"2025-01-02","value":1056},
  ...
]</pre>
                <?php esc_html_e( 'Place the file in the WordPress uploads folder (e.g.,', 'token-holder-data' ); ?>
                <code>/wp-content/uploads/cdlc-data-custom.json</code>).
            </li>
            <li>
                <?php esc_html_e( 'The chart always uses the last 7 data points. Empty or invalid files will simply show an empty chart.', 'token-holder-data' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'You can place multiple charts on the same page – each will be independent.', 'token-holder-data' ); ?>
            </li>
        </ol>
    </div>
    <?php
}

/**
 * ============================================================================
 * DATA FETCHING AND STORAGE (CRON JOB)
 * ============================================================================
 */

function crypto_get_current_holders_count_from_api() {
    $api_url = get_option( 'crypto_api_url' );
    $response = wp_remote_get( $api_url );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! is_array( $data ) || ! isset( $data['holders_count'] ) ) {
        return false;
    }

    return intval( $data['holders_count'] );
}

function crypto_fetch_and_store_data() {
    if ( 'auto' !== get_option( 'crypto_data_mode', 'auto' ) ) {
        return;
    }

    $holders = crypto_get_current_holders_count_from_api();
    if ( false === $holders ) {
        return;
    }

    $upload_dir = wp_upload_dir();
    $file       = trailingslashit( $upload_dir['basedir'] ) . 'cdlc-data.json';

    $history = @json_decode( file_get_contents( $file ), true );
    if ( ! is_array( $history ) ) {
        $history = [];
    }

    $today = wp_date( 'Y-m-d' );

    if ( empty( $history ) || end( $history )['date'] !== $today ) {
        $history[] = [
            'date'  => $today,
            'value' => $holders,
        ];
        file_put_contents( $file, wp_json_encode( $history, JSON_PRETTY_PRINT ), LOCK_EX );
    }
}
add_action( 'crypto_daily_fetch', 'crypto_fetch_and_store_data' );

/**
 * ============================================================================
 * REST API ENDPOINT
 * ============================================================================
 */

function crypto_register_rest_routes() {
    register_rest_route( 'token-data/v1', '/history', [
        'methods'             => 'GET',
        'callback'            => 'crypto_rest_history',
        'permission_callback' => '__return_true',
    ] );
}
add_action( 'rest_api_init', 'crypto_register_rest_routes' );

function crypto_rest_history() {
    $mode = get_option( 'crypto_data_mode', 'auto' );

    $upload_dir = wp_upload_dir();
    $base       = trailingslashit( $upload_dir['basedir'] );
    $file_name  = ( 'custom' === $mode ) ? 'cdlc-data-custom.json' : 'cdlc-data.json';
    $file_path  = $base . $file_name;

    if ( ! file_exists( $file_path ) ) {
        return rest_ensure_response( [] );
    }

    $data = json_decode( file_get_contents( $file_path ), true );
    if ( ! is_array( $data ) ) {
        return rest_ensure_response( [] );
    }

    return rest_ensure_response( $data );
}

/**
 * ============================================================================
 * FRONT‑END SCRIPTS AND SHORTCODE
 * ============================================================================
 */

function crypto_enqueue_scripts() {
    wp_register_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true );

    wp_register_script(
        'crypto-chart',
        plugins_url( 'customized-chart.js', __FILE__ ),
        [ 'chartjs' ],
        '2.1',
        true
    );

    wp_localize_script( 'crypto-chart', 'CRYPTO_SETTINGS', [
        'endpoint' => esc_url_raw( rest_url( 'token-data/v1/history' ) ),
    ] );
}
add_action( 'wp_enqueue_scripts', 'crypto_enqueue_scripts' );

function crypto_chart_shortcode() {
    static $instance = 0;
    $instance++;

    wp_enqueue_script( 'chartjs' );
    wp_enqueue_script( 'crypto-chart' );

    $height = absint( get_option( 'crypto_chart_height', 200 ) );
    $canvas_id = 'tokenChart-' . $instance;

    // Inline style for container
    $style = sprintf( 'width:100%%; height:%dpx; display:block;', $height );

    return sprintf(
        '<div class="crypto-chart-container" style="%s"><canvas class="crypto-chart-canvas" id="%s"></canvas></div>',
        esc_attr( $style ),
        esc_attr( $canvas_id )
    );
}
add_shortcode( 'crypto_chart', 'crypto_chart_shortcode' );