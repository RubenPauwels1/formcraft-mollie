<?php
/*
	Plugin Name: FormCraft Mollie Add-On
	Plugin URI: https://flux.be
	Description: Mollie Add-On for FormCraft
	Author: Ruben Pauwels, Flux
	Author URI: https://flux.be
	Version: 0.5.0
	Text Domain: formcraft-mollie
*/

global $fc_meta, $fc_forms_table, $fc_submissions_table, $fc_views_table, $fc_files_table, $wpdb;
$fc_forms_table = $wpdb->prefix . "formcraft_3_forms";
$fc_submissions_table = $wpdb->prefix . "formcraft_3_submissions";
$fc_views_table = $wpdb->prefix . "formcraft_3_views";
$fc_files_table = $wpdb->prefix . "formcraft_3_files";

add_action('formcraft_addon_init', 'formcraft_mollie_addon');
add_action('formcraft_addon_scripts', 'formcraft_mollie_builder_scripts');


function formcraft_mollie_addon()
{
	if(!class_exists('Mollie_API_Autoloader')){
		include_once('Mollie/API/Autoloader.php');
	}

    //register_formcraft_addon( $function, $id, $plugin_name, $controller_name, $logo_url, $templates_url, $conditional_trigger);
	register_formcraft_addon( 'formcraft_mollie_content', 0, 'Mollie', 'MollieController',  plugins_url('assets/images/mollie.png', __FILE__ ), false, false);

}

function formcraft_mollie_builder_scripts()
	{
		
		wp_enqueue_style('fcmollie-main-css', plugins_url( 'assets/css/builder.css', __FILE__ ));
		wp_enqueue_script('fcmollie-addon-js', plugins_url( 'assets/js/mollie_builder.js', __FILE__ ), array('fc-builder-js'));
		wp_localize_script( 'fcmollie-main-js', 'FCMollie',
			array( 
				'defaultData' => $defaultData,
				)
			);	
	}

function formcraft_mollie_content()
	{
		?>
		<div id='mollie-cover'>
			<div class='loader'>
				<div class="fc-spinner small">
					<div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div>
				</div>
			</div>
			<div style='text-align: center; border-bottom: 1px solid #ddd; padding: 14px 0 14px 0; margin-bottom: 14px;'>
				<?php _e('Only one set of API-keys accepted per Wordpress installation.'); ?>
			</div>
			<div id='mollie-options'>
				<label>
					<span><?php _e('Live API-Key','formcraft-mollie'); ?></span> <input type='text' ng-model='Addons.Mollie.live_publishable_key'/>
				</label>
				<label>
					<span><?php _e('Test API-key','formcraft-mollie'); ?></span> <input type='text' ng-model='Addons.Mollie.test_secret_key'/>
				</label>
				<div class='hide-checkbox'>
					<span></span> 
					<label class='button toggle'><?php _e('Live Mode','formcraft-mollie'); ?><input update-label name='mollie_connect_mode' type='radio' value='live' ng-model='Addons.Mollie.mode'/></label>
					<label style='margin-left: 10px' class='button toggle'><?php _e('Test Mode','formcraft-mollie'); ?><input update-label name='mollie_connect_mode' type='radio' value='test' ng-model='Addons.Mollie.mode'/></label>
				</div>
			</div>
			<div style='text-align: center; border-top: 1px solid #ddd; padding: 14px 0 14px 0'>
				<?php _e('Go to Add Field → Payments → Mollie','formcraft-mollie'); ?>
			</div>
		</div>
		<?php
	}

	add_action('formcraft_form_scripts', 'formcraft_mollie_form_scripts');

	function formcraft_mollie_form_scripts()
	{
		wp_enqueue_style('fcmollie-form-css', plugins_url( 'assets/css/form-mollie.css', __FILE__ ));	
		wp_enqueue_script('fcmollie-form-js', plugins_url( 'assets/js/form-mollie_front.js', __FILE__ ), array('jquery'));
        wp_localize_script( 'fcs-form-js', 'FCS',
          array(
            'ajaxurl' => admin_url( 'admin-ajax.php' )
            )
          );
	}	

	add_action('formcraft_after_save', 'formcraft_mollie_trigger', 10, 2);

	function formcraft_mollie_trigger($content, $meta){
		global $fc_final_response;

		$mollie_data = formcraft_get_addon_data('Mollie', $content['Form ID']);


		if ( !isset($mollie_data['mode']) ) {
			$fc_final_response['failed'] = "Sorry, something went wrong [Admin has forgotten to select a mode]";
			echo json_encode($fc_final_response);
			die();
		}
		if ( $mollie_data['mode']=='live' && empty($mollie_data['live_publishable_key']) ) {
			$fc_final_response['failed'] = "Sorry, something went wrong [Admin has forgotten to fill in live key]";
			echo json_encode($fc_final_response);
			die();
		}
		if ( $mollie_data['mode']=='test' && empty($mollie_data['test_secret_key']) ){
			$fc_final_response['failed'] = "Sorry, something went wrong [Admin has forgotten to fill in test key]";
			echo json_encode($fc_final_response);
			die();
		}

		foreach ($meta['fields'] as $key => $field) {
			if ($field['type']=='Mollie')
			{
				$mollie_field = $field;
			}
		}

		//GET EMAIL:
		$mollie_email = empty($mollie_field['elementDefaults']['mollie_email']) ? false : $mollie_field['elementDefaults']['mollie_email'];
		$final_mollie_email = null;
		if ( filter_var($mollie_email, FILTER_VALIDATE_EMAIL) )
		{
			$final_mollie_email = $mollie_email;
		}
		else
		{
			$mollie_email = preg_replace("/[^a-zA-Z0-9]+/", '', $mollie_email);
			if ( ( isset($_POST[$mollie_email]) || isset($_POST[$mollie_email.'[]']) ) && filter_var($_POST[$mollie_email], FILTER_VALIDATE_EMAIL) )
			{
				$final_mollie_email = isset($_POST[$mollie_email]) ? $_POST[$mollie_email] : $_POST[$mollie_email.'[]'];
			}
		}

		//GET FIRST NAME:
		$mollie_first_name = empty($mollie_field['elementDefaults']['mollie_firstname']) ? false : $mollie_field['elementDefaults']['mollie_firstname'];
		
		$mollie_first_name = preg_replace("/[^a-zA-Z0-9]+/", '', $mollie_first_name);
		if ( ( isset($_POST[$mollie_first_name]) ) )
		{
			$final_mollie_first_name = $_POST[$mollie_first_name][0];
		}

		//GET LAST NAME:
		$mollie_last_name = empty($mollie_field['elementDefaults']['mollie_lastname']) ? false : $mollie_field['elementDefaults']['mollie_lastname'];

		$mollie_last_name = preg_replace("/[^a-zA-Z0-9]+/", '', $mollie_last_name);
		if ( ( isset($_POST[$mollie_last_name]) ) )
		{
			$final_mollie_last_name = $_POST[$mollie_last_name][0];
		}

		//GET AMOUNT:
		$currency = 'EUR';			
		$mollie_field['elementDefaults']['mollie_amount'] = strtolower($mollie_field['elementDefaults']['mollie_amount']);
		$clean_amount = preg_replace("/[^a-zA-Z0-9.*()\-+\/]+/", '', $mollie_field['elementDefaults']['mollie_amount']);
		if (empty($clean_amount)) {
			echo json_encode(array('failed'=>'Invalid / empty amount is not accepted'));
			die();
		}
		$amount_fields = preg_split("/[*()\-+\/]/", $clean_amount);
		arsort($amount_fields, SORT_NUMERIC);
		if ( is_array($amount_fields) && count($amount_fields)>0 )
		{
			foreach ($amount_fields as $key => $value) {
				if ( !is_numeric($value) )
				{
					$this_field_value = 0;
					if ( isset($_POST[$value]) )
					{
						if ( is_array($_POST[$value]) )
						{
							foreach ($_POST[$value] as $key2 => $value2) {
								$this_field_value = is_numeric($value2) ? $this_field_value + $value2 : $this_field_value;
							}
						}
						else
						{
							$this_field_value = is_numeric($_POST[$value]) ? $this_field_value + $_POST[$value] : $this_field_value;
						}
					}
					$clean_amount = str_replace($value, $this_field_value, $clean_amount);
				}
			}
		}
		/* Keep it safe! */
		$clean_amount = str_replace('--', '+', preg_replace("/[^0-9.*()\-+\/]+/", '', $clean_amount));
		eval('$final_amount = ('.$clean_amount.');');
		$final_amount = floor( $final_amount*100 ) / 100;
		//format to Mollie format: 12.34
		$final_amount = number_format($final_amount, 2, '.', ',');

		//GET THE APIKEY
		$secret_key = $mollie_data['mode']=='live' ? $mollie_data['live_secret_key'] : $mollie_data['test_secret_key'];
		
		//GET NUMBER OF SUBMISSIONS + 1
		global $wpdb;
		$latest_sub =  $wpdb->get_var( "SELECT MAX(id) FROM {$fc_submissions_table}" );
		$sub_id = intval($latest_sub) + 1;
		
		
		$mollie = new Mollie_API_Client;
		$mollie->setApiKey($secret_key);

		try
		{
			$payment = $mollie->payments->create(
				array(
					'amount'      => $final_amount,
					'description' => 'ESCRH',
					// 'redirectUrl' => home_url().'/experimenten?registered&user-email=' . $fields['user_email'],
					'redirectUrl' => $content['URL'] . '?paymentprocessed',
					'webhookUrl'  => plugins_url( 'assets/php/webhook.php', __FILE__ ),
					'metadata'    => array(
						'user_email' => $final_mollie_email,
						"first_name" => $final_mollie_first_name, 
						"last_name" => $final_mollie_last_name,
						'id' => $content['Form ID'] . '-' . time(),
						'form_id' => $content['Form ID'],
						'form_name' => $content['Form Name'],
						'date' => $content['Date'],
						'time' => $content['Time'],
						'ip' => $content['IP'],
						'submission_id' => $fc_final_response['submission_id']
					)
				)
			);
			$paymentURL = $payment->links->paymentUrl;
			// echo json_encode(array('paymenturl'=>$paymentURL));

			$fc_final_response['paymentURL'] = $paymentURL;
			echo json_encode($fc_final_response);

			die();

			//REDIRECT WITH JS


			/*
			* Send the customer off to complete the payment.
			* This request should always be a GET, thus we enforce 303 http response code
			*/
			// header("Location: " . $paymentURL, true, 303);
			// exit;
		}
		catch (Mollie_API_Exception $e)
		{
			echo "API call failed: " . htmlspecialchars($e->getMessage());
			echo " on field " . htmlspecialchars($e->getField());
		}

	}