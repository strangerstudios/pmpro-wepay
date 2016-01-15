<?php
	//load classes init method
	add_action('init', array('PMProGateway_wepay', 'init'));

	/**
	 * PMProGateway_gatewayname Class
	 *
	 * Handles example integration.
	 *
	 */
	class PMProGateway_wepay extends PMProGateway
	{
		function __construct($gateway = NULL)
		{
			$this->gateway = $gateway;
			
			$this->loadWePaySDK();
			
			return $this->gateway;
		}										

		/**
		 * Load the Stripe API library.
		 *
		 * @since 1.8
		 * Moved into a method in version 1.8 so we only load it when needed.
		 */
		function loadWePaySDK()
		{
			//load Stripe library if it hasn't been loaded already (usually by another plugin using Stripe)
			if(!class_exists("WePay"))
				require_once(PMPRO_WEPAY_DIR . "/includes/lib/wepay.php");
		}
		
		/**
		 * Run on WP init
		 *
		 * @since 1.8
		 */
		static function init()
		{
			//make sure example is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_wepay', 'pmpro_gateways'));

			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_wepay', 'pmpro_payment_options'));
			add_filter('pmpro_payment_option_fields', array('PMProGateway_wepay', 'pmpro_payment_option_fields'), 10, 2);

			//add some fields to edit user page (Updates)
			add_action('pmpro_after_membership_level_profile_fields', array('PMProGateway_wepay', 'user_profile_fields'));
			add_action('profile_update', array('PMProGateway_wepay', 'user_profile_fields_save'));

			//updates cron
			add_action('pmpro_activation', array('PMProGateway_wepay', 'pmpro_activation'));
			add_action('pmpro_deactivation', array('PMProGateway_wepay', 'pmpro_deactivation'));
			add_action('pmpro_cron_wepay_subscription_updates', array('PMProGateway_wepay', 'pmpro_cron_wepay_subscription_updates'));

			//code to add at checkout if example is the current gateway
			$gateway = pmpro_getOption("gateway");
			if($gateway == "wepay")
			{
				add_action('pmpro_checkout_preheader', array('PMProGateway_wepay', 'pmpro_checkout_preheader'));
				add_filter('pmpro_checkout_order', array('PMProGateway_wepay', 'pmpro_checkout_order'));
				add_filter('pmpro_include_billing_address_fields', array('PMProGateway_wepay', 'pmpro_include_billing_address_fields'));
				add_filter('pmpro_include_cardtype_field', '__return_false');
				add_filter('pmpro_include_payment_information_fields', array('PMProGateway_wepay', 'pmpro_include_payment_information_fields'));
			}
		}

		/**
		 * Make sure example is in the gateways list
		 *
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['wepay']))
				$gateways['wepay'] = __('WePay', 'pmpro');

			return $gateways;
		}

		/**
		 * Get a list of payment options that the example gateway needs/supports.
		 *
		 * @since 1.8
		 */
		static function getGatewayOptions()
		{
			$options = array(
				'wepay_client_id',
				'wepay_client_secret',
				'wepay_access_token',
				'wepay_account_id',
				'wepay_billingaddress',
				'sslseal',
				'nuclear_HTTPS',
				'gateway_environment',
				'currency',
				'use_ssl',
				'tax_state',
				'tax_rate',
				'accepted_credit_cards'
			);

			return $options;
		}

		/**
		 * Check settings if billing address should be shown.
		 * @since 1.8
		 */
		static function pmpro_include_billing_address_fields($include)
		{
			//check settings RE showing billing address
			if(!pmpro_getOption("wepay_billingaddress"))
				$include = false;

			return $include;			
		}				
		
		/**
		 * Set payment options for payment settings page.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_options($options)
		{
			//get example options
			$wepay_options = PMProGateway_wepay::getGatewayOptions();

			//merge with others.
			$options = array_merge($wepay_options, $options);

			return $options;
		}

		/**
		 * Display fields for example options.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_option_fields($values, $gateway)
		{
		?>
		<tr class="pmpro_settings_divider gateway gateway_wepay" <?php if($gateway != "wepay") { ?>style="display: none;"<?php } ?>>
			<td colspan="2">
				<?php _e('WePay Settings', 'pmpro'); ?>
			</td>
		</tr>		
		<tr class="gateway gateway_wepay" <?php if($gateway != "wepay") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="wepay_client_id"><?php _e('Client ID', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="wepay_client_id" name="wepay_client_id" size="60" value="<?php echo esc_attr($values['wepay_client_id'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_wepay" <?php if($gateway != "wepay") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="wepay_client_secret"><?php _e('Client Secret', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="wepay_client_secret" name="wepay_client_secret" size="60" value="<?php echo esc_attr($values['wepay_client_secret'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_wepay" <?php if($gateway != "wepay") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="wepay_access_token"><?php _e('Access Token', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="wepay_access_token" name="wepay_access_token" size="60" value="<?php echo esc_attr($values['wepay_access_token'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_wepay" <?php if($gateway != "wepay") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="wepay_account_id"><?php _e('Account ID', 'pmpro');?>:</label>
			</th>
			<td>
				<input type="text" id="wepay_account_id" name="wepay_account_id" size="60" value="<?php echo esc_attr($values['wepay_account_id'])?>" />
			</td>
		</tr>
		<tr class="gateway gateway_wepay" <?php if($gateway != "wepay") { ?>style="display: none;"<?php } ?>>
			<th scope="row" valign="top">
				<label for="wepay_billingaddress"><?php _e('Show Billing Address Fields', 'pmpro');?>:</label>
			</th>
			<td>
				<select id="wepay_billingaddress" name="wepay_billingaddress">
					<option value="0" <?php if(empty($values['wepay_billingaddress'])) { ?>selected="selected"<?php } ?>><?php _e('No', 'pmpro');?></option>
					<option value="1" <?php if(!empty($values['wepay_billingaddress'])) { ?>selected="selected"<?php } ?>><?php _e('Yes', 'pmpro');?></option>
				</select>
				<small><?php _e("WePay doesn't require billing address fields. Choose 'No' to hide them on the checkout page.<br /><strong>If No, make sure you disable address verification in the WePay dashboard settings.</strong>", 'pmpro');?></small>
			</td>
		</tr>
		<?php
		}

		/**
		 * Filtering orders at checkout.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_order($morder)
		{
			//add token to order
			if(isset($_REQUEST['WePayToken']))
				$morder->wepay_token = $_REQUEST['WePayToken'];
			else
				$morder->wepay_token = "";
			
			return $morder;
		}

		/**
		 * Code to run after checkout
		 *
		 * @since 1.8
		 */
		static function pmpro_after_checkout($user_id, $morder)
		{
		}
		
		/**
		 * Use our own payment fields at checkout. (Remove the name attributes.)		
		 * @since 1.8
		 */
		static function pmpro_include_payment_information_fields($include)
		{
			return true;
		}

		/**
		 * Fields shown on edit user page
		 *
		 * @since 1.8
		 */
		static function user_profile_fields($user)
		{
		}

		/**
		 * Process fields from the edit user page
		 *
		 * @since 1.8
		 */
		static function user_profile_fields_save($user_id)
		{
		}

		/**
		 * Cron activation for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_activation()
		{
			wp_schedule_event(time(), 'daily', 'pmpro_cron_wepay_subscription_updates');
		}

		/**
		 * Cron deactivation for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_deactivation()
		{
			wp_clear_scheduled_hook('pmpro_cron_wepay_subscription_updates');
		}

		/**
		 * Cron job for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_cron_wepay_subscription_updates()
		{
		}
		
		function process(&$order)
		{			
			//check for initial payment
			if(floatval($order->InitialPayment) == 0)
			{
				//auth first, then process
				if($this->authorize($order))
				{						
					$this->void($order);										
					if(!pmpro_isLevelTrial($order->membership_level))
					{
						//subscription will start today with a 1 period trial (initial payment charged separately)
						$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
						$order->TrialBillingPeriod = $order->BillingPeriod;
						$order->TrialBillingFrequency = $order->BillingFrequency;													
						$order->TrialBillingCycles = 1;
						$order->TrialAmount = 0;
						
						//add a billing cycle to make up for the trial, if applicable
						if(!empty($order->TotalBillingCycles))
							$order->TotalBillingCycles++;
					}
					elseif($order->InitialPayment == 0 && $order->TrialAmount == 0)
					{
						//it has a trial, but the amount is the same as the initial payment, so we can squeeze it in there
						$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";														
						$order->TrialBillingCycles++;
						
						//add a billing cycle to make up for the trial, if applicable
						if($order->TotalBillingCycles)
							$order->TotalBillingCycles++;
					}
					else
					{
						//add a period to the start date to account for the initial payment
						$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
					}
					
					$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
					return $this->subscribe($order);
				}
				else
				{
					if(empty($order->error))
						$order->error = __("Unknown error: Authorization failed.", "pmpro");
					return false;
				}
			}
			else
			{
				//charge first payment
				if($this->charge($order))
				{							
					//set up recurring billing					
					if(pmpro_isLevelRecurring($order->membership_level))
					{						
						if(!pmpro_isLevelTrial($order->membership_level))
						{
							//subscription will start today with a 1 period trial
							$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
							$order->TrialBillingPeriod = $order->BillingPeriod;
							$order->TrialBillingFrequency = $order->BillingFrequency;													
							$order->TrialBillingCycles = 1;
							$order->TrialAmount = 0;
							
							//add a billing cycle to make up for the trial, if applicable
							if(!empty($order->TotalBillingCycles))
								$order->TotalBillingCycles++;
						}
						elseif($order->InitialPayment == 0 && $order->TrialAmount == 0)
						{
							//it has a trial, but the amount is the same as the initial payment, so we can squeeze it in there
							$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";														
							$order->TrialBillingCycles++;
							
							//add a billing cycle to make up for the trial, if applicable
							if(!empty($order->TotalBillingCycles))
								$order->TotalBillingCycles++;
						}
						else
						{
							//add a period to the start date to account for the initial payment
							$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $this->BillingFrequency . " " . $this->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
						}
						
						$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
						if($this->subscribe($order))
						{
							return true;
						}
						else
						{
							if($this->void($order))
							{
								if(!$order->error)
									$order->error = __("Unknown error: Payment failed.", "pmpro");
							}
							else
							{
								if(!$order->error)
									$order->error = __("Unknown error: Payment failed.", "pmpro");
								
								$order->error .= " " . __("A partial payment was made that we could not void. Please contact the site owner immediately to correct this.", "pmpro");
							}
							
							return false;								
						}
					}
					else
					{
						//only a one time charge
						$order->status = "success";	//saved on checkout page											
						return true;
					}
				}
				else
				{
					if(empty($order->error))
						$order->error = __("Unknown error: Payment failed.", "pmpro");
					
					return false;
				}	
			}	
		}
		
		/*
			Run an authorization at the gateway.

			Required if supporting recurring subscriptions
			since we'll authorize $1 for subscriptions
			with a $0 initial payment.
		*/
		function authorize(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//code to authorize with gateway and test results would go here
			
				
			//simulate a successful authorization
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("authorized");													
			return true;					
		}
		
		/*
			Void a transaction at the gateway.

			Required if supporting recurring transactions
			as we void the authorization test on subs
			with a $0 initial payment and void the initial
			payment if subscription setup fails.
		*/
		function void(&$order)
		{
			//need a transaction id
			if(empty($order->payment_transaction_id))
				return false;
			
			//code to void an order at the gateway and test results would go here

			//simulate a successful void
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("voided");					
			return true;
		}	
		
		/*
			Make a charge at the gateway.

			Required to charge initial payments.
		*/
		function charge(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//code to charge with gateway and test results would go here
			// application settings
			$account_id    = pmpro_getOption('wepay_account_id');
			$client_id     = pmpro_getOption('wepay_client_id');
			$client_secret = pmpro_getOption('wepay_client_secret');
			$access_token  = pmpro_getOption('wepay_access_token');

			// credit card id to charge
			$credit_card_id = $order->wepay_token;	//TODO: Make sure we're getting this

			// change to useProduction for live environments
			$environment = pmpro_getOption('gateway_environment');
			if($environment == 'live')
				Wepay::useProduction($client_id, $client_secret);
			else
				Wepay::useStaging($client_id, $client_secret);

			$wepay = new WePay($access_token);

			// charge the credit card
			$response = $wepay->request('checkout/create', array(
				'account_id'          => $account_id,
				'amount'              => '25.50',
				'currency'            => 'USD',
				'short_description'   => 'A vacation home rental',
				'type'                => 'goods',
				'payment_method'      => array(
					'type'            => 'credit_card',
					'credit_card'     => array(
						'id'          => $credit_card_id
					)
				)
			));
			
			//test response
			
			//simulate a successful charge
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("success");					
			return true;						
		}
		
		/*
			Setup a subscription at the gateway.

			Required if supporting recurring subscriptions.
		*/
		function subscribe(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);
			
			//code to setup a recurring subscription with the gateway and test results would go here

			//simulate a successful subscription processing
			$order->status = "success";		
			$order->subscription_transaction_id = "TEST" . $order->code;				
			return true;
		}	
		
		/*
			Update billing at the gateway.

			Required if supporting recurring subscriptions and
			processing credit cards on site.
		*/
		function update(&$order)
		{
			//code to update billing info on a recurring subscription at the gateway and test results would go here

			//simulate a successful billing update
			return true;
		}
		
		/*
			Cancel a subscription at the gateway.

			Required if supporting recurring subscriptions.
		*/
		function cancel(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//code to cancel a subscription at the gateway and test results would go here

			//simulate a successful cancel			
			$order->updateStatus("cancelled");					
			return true;
		}	
		
		/*
			Get subscription status at the gateway.

			Optional if you have code that needs this or
			want to support addons that use this.
		*/
		function getSubscriptionStatus(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//code to get subscription status at the gateway and test results would go here

			//this looks different for each gateway, but generally an array of some sort
			return array();
		}

		/*
			Get transaction status at the gateway.

			Optional if you have code that needs this or
			want to support addons that use this.
		*/
		function getTransactionStatus(&$order)
		{			
			//code to get transaction status at the gateway and test results would go here

			//this looks different for each gateway, but generally an array of some sort
			return array();
		}
		
		/*
			Process checkout page form submissions.
		*/
		function pmpro_checkout_preheader()
		{			
			// Register the script
			wp_register_script( 'pmpro-wepay', plugins_url( '/js/pmpro-wepay.js', dirname(__FILE__) ) );

			// Localize the script with new data
			$translation_array = array(
				'enviroment' => pmpro_getOption('gateway_environment'),
				'billingaddress' => pmpro_getOption('wepay_billingaddress'),
				'client_id' => pmpro_getOption('wepay_client_id'),
			);
			wp_localize_script( 'pmpro-wepay', 'pmprowepay', $translation_array );

			// Enqueued script with localized data.
			wp_enqueue_script( 'wepay', 'https://static.wepay.com/min/js/tokenization.v2.js', array(), '2' );
			wp_enqueue_script( 'pmpro-wepay' );
			
			
			
			return;
		}
	}