<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://jonmendoza.ph
 * @since      1.0.0
 *
 * @package    Paymongo_Integration
 * @subpackage Paymongo_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Paymongo_Integration
 * @subpackage Paymongo_Integration/admin
 * @author     Jon Mendoza <jonazodnem26@gmail.com>
 */
class Paymongo_Integration_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Paymongo_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Paymongo_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/paymongo-integration-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Paymongo_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Paymongo_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/paymongo-integration-admin.js', array( 'jquery' ), $this->version, false );

	}

	function paymongo_add_gateway_class( $gateways ) {
		// $gateways[] = 'GrabPayIntegration';
		// $gateways[] = 'GCashIntegration';
		$gateways[] = 'PaymongoIntegrationGateway';
		return $gateways;
	}

	function wc_paymongo_integration_gateway_init() {

		require plugin_dir_path( dirname( __FILE__ ) ) . 'paymongo/class-paymongo-integration-gateway.php';
		// require plugin_dir_path( dirname( __FILE__ ) ) . 'paymongo/class-paymongo-integration-grabpay.php';
		// require plugin_dir_path( dirname( __FILE__ ) ) . 'paymongo/class-paymongo-integration-gcash.php';

	}
}
