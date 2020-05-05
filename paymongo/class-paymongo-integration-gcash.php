<?php

class GCashIntegration extends WC_Payment_Gateway {
 
  /**
   * Instance
   *
   * @since 1.0.0
   * @access private
   * @static
   *
   * @var Plugin The single instance of the class.
   */
  private static $_instance = null;
 
  /**
   * Instance
   *
   * Ensures only one instance of the class is loaded or can be loaded.
   *
   * @since 1.2.0
   * @access public
   *
   * @return Plugin An instance of the class.
   */
  public static function instance() {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }
           
    return self::$_instance;
  }
 
  /**
   * Register Widgets
   *
   * Register new Elementor widgets.
   *
   * @since 1.2.0
   * @access public
   */
  public function register_widgets() {

    
  }
 
  /**
   *  Plugin class constructor
   *
   * Register plugin action hooks and filters
   *
   * @since 1.2.0
   * @access public
   */
  public function __construct() {
      $this->id = 'gcash'; // payment gateway plugin ID
      $this->icon = plugin_dir_url( dirname( __FILE__ ) ) . 'paymongo/assets/gcash.png'; // URL of the icon that will be displayed on checkout page near your gateway name
      $this->has_fields = true; // in case you need a custom credit card form
      $this->method_title = 'Pay via GCash';
      $this->method_description = 'Make fast and secure mobile payments with GCash. Pay via GCash powered by Paymongo.'; // will be displayed on the options page

      // gateways can support subscriptions, refunds, saved payment methods,
      // but in this tutorial we begin with simple payments
      $this->supports = array(
      'products'
      );

      // Method with all the options fields
      $this->init_form_fields();

      // Load the settings.
      $this->init_settings();
      $this->title = $this->get_option( 'title' );
      $this->description = $this->get_option( 'description' );
      $this->enabled = $this->get_option( 'enabled' );
      $this->testmode = 'yes' === $this->get_option( 'testmode' );
      $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
      $this->public_key = $this->testmode ? $this->get_option( 'test_public_key' ) : $this->get_option( 'public_key' );

      // This action hook saves the settings
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

      // We need custom JavaScript to obtain a token
      // add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
    }


    public function init_form_fields(){
      $this->form_fields = array(
        'enabled' => array(
          'title'       => 'Enable/Disable',
          'label'       => 'Enable Paymongo Integration',
          'type'        => 'checkbox',
          'description' => '',
          'default'     => 'no'
        ),
        'title' => array(
          'title'       => 'Title',
          'type'        => 'text',
          'default'     => 'Pay via GCash',
          'desc_tip'    => true,
        ),
        'description' => array(
          'title'       => 'Description',
          'type'        => 'text',
          'default'     => 'Make fast and secure mobile payments with GCash. Pay via GCash powered by Paymongo.',
        ),
        'testmode' => array(
          'title'       => 'Test mode',
          'label'       => 'Enable Test Mode',
          'type'        => 'checkbox',
          'description' => 'Place the payment gateway in test mode using test API keys.',
          'default'     => 'yes',
          'desc_tip'    => true,
        ),
        'test_public_key' => array(
          'title'       => 'Test Public Key',
          'type'        => 'text',
          'description' => 'Paymongo API Keys can be seen <a href="https://dashboard.paymongo.com/developers" target="_blank">here</a>'
        ),
        'test_private_key' => array(
          'title'       => 'Test Private Key',
          'type'        => 'password',
        ),
        'public_key' => array(
          'title'       => 'Live Public Key',
          'type'        => 'text'
        ),
        'private_key' => array(
          'title'       => 'Live Private Key',
          'type'        => 'password'
        )
      );
    }

}
 
// Instantiate Plugin Class
GCashIntegration::instance();