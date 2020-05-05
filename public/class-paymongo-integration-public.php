<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://jonmendoza.ph
 * @since      1.0.0
 *
 * @package    Paymongo_Integration
 * @subpackage Paymongo_Integration/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Paymongo_Integration
 * @subpackage Paymongo_Integration/public
 * @author     Jon Mendoza <jonazodnem26@gmail.com>
 */
class Paymongo_Integration_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/paymongo-integration-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/paymongo-integration-public.js', array( 'jquery' ), $this->version, false );

	}

	// public function paymongo_webhook_calls(){
	//    	$status = 'paymongo';
	//     $file = plugin_dir_path(__FILE__) . 'logs/' . current_time("timestamp") .'-'. $status . '.log';
	//     ob_start();
	//     $rawData = file_get_contents("php://input");
	//     var_dump($rawData);
	//     $data = ob_get_clean();

	//     $fp = fopen($file, "w");
	//     fwrite($fp, $data);
	//     fclose($fp);
	// }

	public function encrypt_ls($post){
      return base64_encode(gzcompress(serialize($post)));
    }

    public function decrypt_ls($post){
	  return stripslashes_deep(unserialize(gzuncompress(base64_decode($post))));
	}

	public function add_card_js_checkout_script(){ ?>
		<script src="<?php echo plugin_dir_url( __FILE__ ); ?>js/card.js"></script>
		<script type="text/javascript">
				function changeVaule(value){
					if(value === 'debit-credit'){
						document.getElementById('paymongo-card-container').style.display = 'block';
						new Card({
				            form: 'form.checkout',
				            container: '.paymongo-card-wrapper',
				            formSelectors: {
						        numberInput: 'input[name=billing_card_number]',
						        expiryInput: 'input[name=billing_card_expiry]',
						        cvcInput: 'input[name=billing_card_cvc]', 
						        nameInput: 'input[name=billing_card_name]'
						    },
				        });
					}else{
						document.getElementById('paymongo-card-container').style.display = 'none';
					}
				}
		</script>
	<?php
	}

	public function paymongo_create_payment($order_id){

	    $paymongoResponse = $this->decrypt_ls(get_post_meta($order_id, '_paymongo_response', true));
	    $sourceID = $paymongoResponse->data->id;
	    $amountPaid = $paymongoResponse->data->attributes->amount;
	    $payment_gateway_id = 'paymongo';
		$payment_gateways   = WC_Payment_Gateways::instance();
		$payment_gateway    = $payment_gateways->payment_gateways()[$payment_gateway_id];
		$testmode = 'yes' === $payment_gateway->settings['testmode'];
		$privateKey = $testmode ? $payment_gateway->settings['test_private_key'] : $payment_gateway->settings['private_key'];
		$publicKey = $testmode ? $payment_gateway->settings['test_public_key'] : $payment_gateway->settings['public_key'];

        $outPutGiven = get_post_meta($order_id, '_paymongo_payment_result', true);

        if(empty($outPutGiven)){

        	$ch = curl_init();
			$data = [
			   "data" => [
			         "attributes" => [
			            "source" => [
			               "id" => $sourceID, 
			               "type" => "source" 
			            ],
			            "description" => "Bought product from the website", 
			            "amount" => $amountPaid, 
			            "currency" => "PHP" 
			         ] 
			      ] 
			]; 

			curl_setopt($ch, CURLOPT_URL, 'https://api.paymongo.com/v1/payments');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

			$headers = array();
			$base64 = base64_encode($privateKey);
			$headers[] = 'Authorization: Basic ' . $base64;
			$headers[] = 'Content-Type: application/json';
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);
			if (curl_errno($ch)) {
			    echo 'Error:' . curl_error($ch);
			}
			curl_close($ch);

			$dataOutput = json_decode($result);
			$encrypted = $this->encrypt_ls($dataOutput);
			update_post_meta($order_id, '_paymongo_payment_result', $encrypted);

			$order = wc_get_order( $order_id );
		    $order->payment_complete();
		    wc_reduce_stock_levels($order_id);

			// echo '<pre>';
			// var_dump($dataOutput);
			// echo '</pre>';
	    }


  //       $outPutGiven = get_post_meta($order_id, '_paymongo_payment_result', true);
		// $outPutGiven = $this->decrypt_ls($outPutGiven);
		// echo '<pre>';
		// var_dump($outPutGiven);
		// echo '</pre>';	


	    	


		
		      

	}


}
