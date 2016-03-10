<?php
/**
 * Integrate GoSquared with WooCommerce.
 */

if ( ! class_exists( 'GIO_GoSquared_WC_Integration' ) ) :

class GIO_GoSquared_WC_Integration extends WC_Integration {

	/**
	 * Order ID cache location for tracking code
	 */
	private $order_id = false;

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		global $woocommerce;
		global $GIO_GoSquared;

		$this->id                 = 'gio-gosquared';
		$this->method_title       = __( 'GoSquared', 'gio-gosquared' );
		$this->method_description = __( 'Add GoSquared e-commerce tracking to WooCommerce.', 'gio-gosquared' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define setting variables.
		$this->enabled            = $this->get_option( 'enabled' );

		// Actions.
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );

		// Add better people tracking with WC
		remove_action( 'gio_gosquared_js_output', array( $GIO_GoSquared, 'add_people' ) );

		if( 1 == absint( $GIO_GoSquared->settings->get( 'people' ) ) && is_user_logged_in() ) {
			add_action( 'gio_gosquared_js_output', array( $this, 'add_wc_people' ) );
		}

		add_action( 'woocommerce_thankyou', array( $this, 'cache_order_id' ) );

		add_action( 'gio_gosquared_js_output', array( $this, 'track_events' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue() {
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Initialize integration settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'account' => array(
				'title'             => __( 'Enable', 'gio-gosquared' ),
				'type'              => 'checkbox',
				'description'       => __( 'Enable e-commerce tracking for GoSquared.', 'gio-gosquared' ),
				'desc_tip'          => true,
				'default'           => 'no'
			)
		);
	}

  public function add_wc_people() {
		global $current_user;
    get_currentuserinfo();

		$meta = get_user_meta( get_current_user_id() );

		$data = array(
	    'id' 				=> get_current_user_id(),
	    'username'	=> $current_user->user_login
		);

		// Use their billing email instead of the user email
		// But fall back to user email if the billing email is not set
		if( isset( $meta['billing_email'][0] ) && is_email( $meta['billing_email'][0] ) ) {
			$data['email'] = $meta['billing_email'][0];
		} else {
			$data['email'] = $current_user->user_email;
		}

		$keys = array(
			'billing_first_name',
			'billing_last_name',
			'billing_phone'
		);

		foreach( $keys as $key ) {
			if( isset( $meta[ $key ][0] ) && ! empty( $meta[ $key ][0] ) ) {
				$data[ str_replace( 'billing_', '', $key ) ] = $meta[ $key ][0];
			}
		}

	?>
  _gs('identify', <?php echo json_encode( $data ); ?> );
  <?php }

  public function cache_order_id( $order_id = 0 ) {
    $this->order_id = $order_id;

		// Now that the order ID is cached, trigger the transaction tracking output
		add_action( 'gio_gosquared_js_output', array( $this, 'track_transaction' ) );
  }

	public function track_transaction() {
		// If no order ID is cached, don't add tracking JS
		if( false === $this->order_id ) {
			return;
		}

		$order = wc_get_order( $this->order_id );
		$items = $order->get_items();

    $products = array();
		$total_qty = 0;
    foreach( $items as $item ) {
      $products[] = json_encode( array(
        'name' => $item['name'],
        'price' => $item['line_total'] / $item['qty'],
				'revenue' => $item['line_total'],
        'quantity' => $item['qty']
      ) );

			$total_qty += $item['qty'];
    }
		?>

		_gs('transaction', '<?php echo $this->order_id; ?>', {
			track: true,
			quantity: <?php echo $total_qty; ?>,
			revenue: <?php echo $order->order_total; ?>
		}, [
			<?php echo implode( ',', $products ); ?>
		]);

	<?php }

	public function track_events() { ?>
		jQuery( document.body ).on('added_to_cart', function() {
			_gs('event', 'Clicked Add To Cart');
		});

		jQuery( 'form' ).on('checkout_place_order', function() {
			_gs('event', 'Clicked Place Order');
		});
	<?php }

}

endif;
