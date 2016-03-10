<?php
/**
 * Plugin Name:     GoSquared
 * Plugin URI:      http://garman.io
 * Description:     Integrate GoSquared with your WordPress site, includes WooCommerce support.
 * Version:         1.0.0
 * Author:          Patrick Garman
 * Author URI:      http://pmgarman.me
 * Text Domain:     gio-gosquared
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'GIO_GoSquared' ) ) :

class GIO_GoSquared {

  /**
   * Settings
   */
  public $settings = null;

	/**
	* Construct the plugin.
	*/
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	* Initialize the plugin.
	*/
	public function init() {
    // Load and register settings
    require_once 'inc/class-gio-gosquared-settings.php';
    $this->settings = new GIO_GoSquared_Settings;

    if( ! empty( $this->settings->get( 'site_token' ) ) ) {
      add_action( 'wp_footer', array( $this, 'output_script' ) );
    }

    if( 1 == absint( $this->settings->get( 'people' ) ) && is_user_logged_in() ) {
      add_action( 'gio_gosquared_js_output', array( $this, 'add_people' ) );
    }

		// Checks if WooCommerce is installed and loads the integration
		if ( class_exists( 'WC_Integration' ) ) {
      require_once 'inc/class-gio-gosquared-wc-integration.php';

			// Register the integration with WooCommerce
			add_filter( 'woocommerce_integrations', array( $this, 'add_wc_integration' ) );
		}
	}

	/**
	 * Add a new integration to WooCommerce.
	 */
	public function add_wc_integration( $integrations ) {
		$integrations[] = 'GIO_GoSquared_WC_Integration';
		return $integrations;
	}

  public function output_script() { ?>
    <!-- GoSquared Start -->
    <!-- Integration by Garman.IO -->
    <script>
      !function(g,s,q,r,d){r=g[r]=g[r]||function(){(r.q=r.q||[]).push(
      arguments)};d=s.createElement(q);q=s.getElementsByTagName(q)[0];
      d.src='//d1l6p2sc9645hc.cloudfront.net/tracker.js';q.parentNode.
      insertBefore(d,q)}(window,document,'script','_gs');

      _gs('<?php echo $this->settings->get( 'site_token' ); ?>');

      <?php do_action( 'gio_gosquared_js_output' ); ?>
    </script>
    <!-- GoSquared End -->
  <?php }

  public function add_people() { global $current_user; get_currentuserinfo(); ?>
  _gs('identify', {
    id:       <?php echo $current_user->ID; ?>,
    username: '<?php echo $current_user->user_login; ?>',
    email:    '<?php echo $current_user->user_email; ?>'
  });
  <?php }

}

global $GIO_GoSquared;
$GIO_GoSquared = new GIO_GoSquared( __FILE__ );

endif;
