<?php
/**
 * GoSquared WordPress settings.
 */

if ( ! class_exists( 'GIO_GoSquared_Settings' ) ) :

class GIO_GoSquared_Settings {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
    add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

  public function get( $key ) {
    return get_option( 'gio_gosquared_' . $key );
  }

  /**
   * Add settings to WordPress
   */
  public function register_settings() {
    $page = 'general';
    $section = 'gio_gosquared_section';

   	add_settings_section(
  		$section,
  		__( 'GoSquared Settings', 'gio-gosquared' ),
  		'__return_false',
  		$page
  	);

   	add_settings_field(
  		'gio_gosquared_site_token',
  		__( 'Site Token', 'gio-gosquared' ),
  		array( $this, 'site_token_output' ),
  		$page,
  		$section
  	);

   	add_settings_field(
  		'gio_gosquared_people',
  		__( 'People', 'gio-gosquared' ),
  		array( $this, 'people_output' ),
  		$page,
  		$section
  	);

   	register_setting( 'general', 'gio_gosquared_site_token' );
   	register_setting( 'general', 'gio_gosquared_people' );
  }

  public function site_token_output() { ?>
    <input name="gio_gosquared_site_token" id="gio_gosquared_site_token" type="textbox" value="<?php echo esc_attr( $this->get( 'site_token' ) ); ?>" /> <?php _e( 'GSN Site Token', 'gio-gosquared' ); ?>
  <?php }

  public function people_output() { ?>
    <input name="gio_gosquared_people" id="gio_gosquared_people" type="checkbox" value="1" <?php checked( 1, $this->get( 'people' ) ); ?> /> <?php _e( 'Add People Integration (May not work well in heavily cached environments)', 'gio-gosquared' ); ?>
  <?php }

}

endif;
