<?php

class PaymongoIntegrationGateway extends WC_Payment_Gateway {
 
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
      $this->id = 'paymongo'; // payment gateway plugin ID
      $this->icon = plugin_dir_url( dirname( __FILE__ ) ) . 'paymongo/assets/paymongo.png'; // URL of the icon that will be displayed on checkout page near your gateway name
      $this->has_fields = true; // in case you need a custom credit card form
      $this->method_title = 'Pay via Paymongo';
      $this->method_description = 'PayMongo makes it easy for you to run an online business. We help you get paid by your customer, any time and anywhere.'; // will be displayed on the options page

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
      $this->addpaymongofee = 'yes' === $this->get_option( 'addpaymongofee' );
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
          'default'     => 'Pay via Paymongo',
          'desc_tip'    => true,
        ),
        'description' => array(
          'title'       => 'Description',
          'type'        => 'text',
          'default'     => 'Pay via Paymongo; you can pay through debit and credit card and e-wallets.',
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
        ),
        'addpaymongofee' => array(
          'title'       => 'Paymongo Fee',
          'label'       => 'Add Paymongo Fee',
          'type'        => 'checkbox',
          'description' => 'Check <a href="https://paymongo.com/pricing" target="_blank">Paymongo Pricing</a>.',
          'default'     => 'no',
        )
      );
    }


    public function payment_fields() {
 
        if ( $this->description ) {
          if ( $this->testmode ) {
            $this->description .= ' <b style="color: red">TEST MODE ENABLED</b>';
            $this->description  = trim( $this->description );
          }
          echo wpautop( wp_kses_post( $this->description ) );
        }
       
        echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-paymongo-payment-form">';
       
        do_action( 'woocommerce_credit_card_form_start', $this->id );
       
        ?>
          <div class="form-row form-row-wide"><label>Payment Partner <span class="required">*</span></label>
            <select onchange="changeVaule(this.value)" name="billing_paymongo_gateway">
              <option value="">Select Payment</option>
              <option value="gcash">GCash</option>
              <option value="grab_pay">Grab Pay</option>
              <option value="debit-credit">Debit/Credit Card</option>
            </select>
          </div>

        <div class="paymongo-card-container" id="paymongo-card-container" style="display: none;">
            <div class="paymongo-card-wrapper"></div>
            <div class="form-container active">
                <input placeholder="Card number" type="tel" name="billing_card_number">
                <input placeholder="Full name" type="text" name="billing_card_name">
                <input placeholder="MM/YY" type="tel" name="billing_card_expiry">
                <input placeholder="CVC" type="number" name="billing_card_cvc">
            </div>
            <p>* Card Information will not be sent to our servers.</p>
        </div>

        <div class="clear"></div>

          <?php
       
        do_action( 'woocommerce_credit_card_form_end', $this->id );
       
        echo '<div class="clear"></div></fieldset>';
 
    }

    public function validate_fields(){
      if( empty( $_POST[ 'billing_paymongo_gateway' ]) ) {
        wc_add_notice(  '<b>Payment Partner</b> is a required field.', 'error' );
        return false;
      }

      return true;
    }

    public function encrypt_ls($post){
      return base64_encode(gzcompress(serialize($post)));
    }

    public function process_payment( $order_id ) {
 
      global $woocommerce;
     
      $order = wc_get_order( $order_id );

        $paymentType = $_POST[ 'billing_paymongo_gateway' ];
        $paymentTotal = str_replace('.', '', get_post_meta($order_id,'_order_total',true));
        $billingName = get_post_meta($order_id,'_billing_first_name',true) . ' ' . get_post_meta($order_id,'_billing_last_name',true);
        $billingPhone = get_post_meta($order_id,'_billing_phone',true);
        $billingEmail = get_post_meta($order_id,'_billing_email',true);
        $billingLine1 = get_post_meta($order_id,'_billing_address_1',true);
        $billingLine2 = get_post_meta($order_id,'_billing_address_2',true);
        $billingState = get_post_meta($order_id,'_billing_state',true);
        $billingCode = get_post_meta($order_id,'_billing_postcode',true);
        $billingCity = get_post_meta($order_id,'_billing_city',true);
        $billingCountry = get_post_meta($order_id,'_billing_country',true);

        $success = $this->get_return_url( $order );
        $failed = get_home_url() . '/checkout/error';

        update_post_meta($order_id,'_billing_paymongo_gateway',$paymentType);

        $ch = curl_init();

        if($paymentType == 'debit-credit'){

          $data = [
             "data" => [
                   "attributes" => [
                      "payment_method_allowed" => [
                         "card" 
                      ], 
                      "payment_method_options" => [
                            "card" => [
                               "request_three_d_secure" => "automatic" 
                            ] 
                         ], 
                      "currency" => "PHP", 
                      "amount" => intval($paymentTotal), 
                      "description" => "Bought product from the website" 
                   ] 
                ] 
          ]; 

          curl_setopt($ch, CURLOPT_URL, 'https://api.paymongo.com/v1/payment_intents');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
          $base64 = base64_encode($this->private_key . ':' . $this->private_key);
          $headers = array();
          $headers[] = 'Authorization: Basic ' . $base64;
          $headers[] = 'Content-Type: application/json';
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

          $result = curl_exec($ch);
          if (curl_errno($ch)) {
              echo 'result1Error:' . curl_error($ch);
          }
          curl_close($ch);

          $dataOutput = json_decode($result);   

          // update_post_meta($order_id, '_paymongo_client_key', $dataOutput->data->attributes->client_key);

          $clientkey = $dataOutput->data->attributes->client_key;

          $ch2 = curl_init();

          $expiry = explode('/', esc_html($_POST['billing_card_expiry']));


           $data2 = [
           "data" => [
                 "attributes" => [
                    "details" => [
                       "card_number" => str_replace(' ', '', esc_html($_POST['billing_card_number'])), 
                       "exp_month" => intval($expiry[0]), 
                       "exp_year" => intval($expiry[1]), 
                       "cvc" => esc_html($_POST['billing_card_cvc']) 
                    ], 
                    "billing" => [
                          "address" => [
                             "line1" => $billingLine1, 
                             "line2" => $billingLine2, 
                             "state" => $billingState, 
                             "postal_code" => $billingCode, 
                             "city" => $billingCity, 
                             "country" => $billingCountry 
                          ],  
                          "name" => esc_html($_POST['billing_card_name']), 
                          "email" => $billingEmail 
                       ], 
                    "type" => "card", 
                    "phone" => $billingPhone 
                 ] 
              ] 
          ]; 

          curl_setopt($ch2, CURLOPT_URL, 'https://api.paymongo.com/v1/payment_methods');
          curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch2, CURLOPT_POST, 1);
          curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($data2));

          $headers2 = array();
          $headers2[] = 'Authorization: Basic ' . $base64;
          $headers2[] = 'Content-Type: application/json';
          curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers2);

          $result2 = curl_exec($ch2);
          if (curl_errno($ch2)) {
              echo 'result2Error:' . curl_error($ch2);
          }
          curl_close($ch2);    


          $dataOutput2 = json_decode($result2);

          if(isset($dataOutput2->errors)){
              // if($dataOutput->errors[0]->code == 'parameter_below_minimum'){
              //   $responseError = 'The total cannot be less than ₱100.00';
              // }else if($dataOutput->errors[0]->code == 'api_key_invalid'){
              //   $responseError = 'API key is invalid. Contact technical support for assistance';
              //   //send email to inform admin that someone experience this.
              // }else{
                $responseError = $dataOutput2->errors[0]->detail;

              // }
                wc_add_notice( $responseError, 'error' );
                return;     
          }else{

            $paymentMethodId = $dataOutput2->data->id;

            $paymentIntentId = explode('_client', $clientkey)[0];

            $ch3 = curl_init();

            $data3 = [
               "data" => [
                     "attributes" => [
                        "payment_method" => $paymentMethodId, 
                        "client_key" => $clientkey, 
                        "return_url" => $success 
                     ] 
                  ] 
            ]; 

            curl_setopt($ch3, CURLOPT_URL, 'https://api.paymongo.com/v1/payment_intents/'.$paymentIntentId.'/attach');
            curl_setopt($ch3, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch3, CURLOPT_POST, 1);
            curl_setopt($ch3, CURLOPT_POSTFIELDS, json_encode($data3));

            $headers3 = array();
            $headers3[] = 'Authorization: Basic ' . $base64;
            $headers3[] = 'Content-Type: application/json';
            curl_setopt($ch3, CURLOPT_HTTPHEADER, $headers3);

            $result3 = curl_exec($ch3);
            if (curl_errno($ch3)) {
                echo 'result3Error:' . curl_error($ch3);
            }
            curl_close($ch3);

            $dataOutput3 = json_decode($result3);


            if(isset($dataOutput3->errors)){
              // if($dataOutput->errors[0]->code == 'parameter_below_minimum'){
              //   $responseError = 'The total cannot be less than ₱100.00';
              // }else if($dataOutput->errors[0]->code == 'api_key_invalid'){
              //   $responseError = 'API key is invalid. Contact technical support for assistance';
              //   //send email to inform admin that someone experience this.
              // }else{
                $responseError = $dataOutput3->errors[0]->detail;
              // }
              
                wc_add_notice( $responseError, 'error' );
                return;

            }else{
              $statusPaymentAttach = $dataOutput3->data->attributes->status;
              if ($statusPaymentAttach === 'awaiting_next_action') {
                $responseError = $dataOutput3->data->attributes->next_action;
                wc_add_notice( $responseError, 'error' );
                return;
              } else if ($statusPaymentAttach === 'succeeded') {

                $order->payment_complete();
                $order->reduce_order_stock();
                $woocommerce->cart->empty_cart();

                $encrypted = $this->encrypt_ls($dataOutput3);
                update_post_meta($order_id, '_paymongo_response', $encrypted);

                return array(
                  'result' => 'success',
                  'redirect' => $success
                );

              } else if($statusPaymentAttach === 'awaiting_payment_method') {
                $responseError = $dataOutput3->data->attributes->last_payment_error;
                wc_add_notice( $responseError, 'error' );
                return;
              }
            }
          }

        }else{

         $data = [
           "data" => [
                 "attributes" => [
                    "redirect" => [
                       "success" => $success, 
                       "failed" => $failed 
                    ], 
                    "billing" => [
                          "address" => [
                             "line1" => $billingLine1, 
                             "line2" => $billingLine2, 
                             "state" => $billingState, 
                             "postal_code" => $billingCode, 
                             "city" => $billingCity, 
                             "country" => $billingCountry 
                          ],  
                          "name" => $billingName, 
                          "phone" => $billingPhone, 
                          "email" => $billingEmail 
                       ], 
                    "type" => $paymentType, 
                    "amount" => intval($paymentTotal), 
                    "currency" => "PHP" 
                 ] 
              ] 
        ]; 
 
        curl_setopt($ch, CURLOPT_URL, 'https://api.paymongo.com/v1/sources');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $base64 = base64_encode($this->public_key . ':' . $this->private_key);
        $headers = array();
        $headers[] = 'Authorization: Basic ' . $base64;
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $dataOutput = json_decode($result);

        if(isset($dataOutput->errors)){
        	if($dataOutput->errors[0]->code == 'parameter_below_minimum'){
        		$responseError = 'The total cannot be less than ₱100.00';
        	}else if($dataOutput->errors[0]->code == 'api_key_invalid'){
        		$responseError = 'API key is invalid. Contact technical support for assistance';
        		//send email to inform admin that someone experience this.
        	}else{
        		$responseError = $dataOutput->errors[0]->detail;
        	}
        	
            wc_add_notice( $responseError, 'error' );
            return;
        }else{
            $encrypted = $this->encrypt_ls($dataOutput);
            update_post_meta($order_id, '_paymongo_response', $encrypted);
            update_post_meta($order_id, '_paymongo_source_id', $dataOutput->data->id);

            $redirectPayment = $dataOutput->data->attributes->redirect->checkout_url;

            $order->update_status('pending');
            $order->reduce_order_stock();
            $woocommerce->cart->empty_cart();
         
              return array(
                'result' => 'success',
                'redirect' => $redirectPayment
              );
          }
        }
    }



}

 
// Instantiate Plugin Class
PaymongoIntegrationGateway::instance();