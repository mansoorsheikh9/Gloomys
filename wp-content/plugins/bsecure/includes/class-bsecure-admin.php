<?php
/**
 * The file that contains bsecure api features.
 *
 * @link       https://www.bsecure.pk
 * @since      1.4.9
 *
 * @package    WC_Bsecure
 * @subpackage WC_Bsecure/includes
 */
/**
 * The core plugin class.
 *
 * bsecure checkout features
 *
 * @since      1.4.9
 * @package    WC_Bsecure
 * @subpackage WC_Bsecure/includes
 * @author     bSecure <info@bsecure.pk>
 */

class Bsecure_Admin extends WC_Bsecure {	

	const BSECURE_PLUGIN_STATUS_NEW = 1;

    const BSECURE_PLUGIN_STATUS_DISBALED = 3;

	public function __construct(){

		$wc_bsecure_is_active = get_option('wc_bsecure_is_active', 'no');              

        $this->base_url = get_option('wc_bsecure_base_url');

        add_action( 'wp_ajax_bsecure_deactivation_popup', array($this, 'bsecure_deactivation_popup'));
        add_action( 'wp_ajax_nopriv_bsecure_deactivation_form_submit', array($this, 'bsecure_deactivation_form_submit' ));
		add_action( 'wp_ajax_bsecure_deactivation_form_submit', array($this, 'bsecure_deactivation_form_submit'));
		add_action( 'wp_loaded',  array($this, 'loadThickBox')); 

	}

	/**
	 * Renders the bSecure Deactivation Survey Form.
	 * Note: only for internal use
	 *
	 * @since 2.2
	*/
	public function bsecure_deactivation_popup() {
		// Bailout.
		if ( ! current_user_can( 'delete_plugins' ) ) {
			wp_die();
		}

		$results = array();

		// Start output buffering.
		ob_start();
		?>

		<div class="wrapper-deactivation-survey">
			<form class="bsecure-deactivation-survey-form" method="POST">
				
				<p class="generalMainText"><?php esc_html_e( 'If you have a moment, please let us know why you are deactivating bSecure. All submissions are anonymous and we only use this feedback to improve this plugin.', 'wc-bsecure' ); ?></p>

				<div class="bSecureoptionsBoxes">
					<label class="bsecure-field-description">
						<input type="radio" name="bsecure-survey-radios" value="1">
						<?php esc_html_e( "I no longer need the plugin", 'wc-bsecure' ); ?>
					</label>
				</div>		

				<div class="bSecureoptionsBoxes">
					<label class="bsecure-field-description">
						<input type="radio" name="bsecure-survey-radios" value="2" data-has-field="true">
						<?php esc_html_e( 'I found a better plugin', 'wc-bsecure' ); ?>
					</label>

					<div class="bsecure-survey-extra-field reason-box2 hidden">
						<p>
						<?php
							printf(
								'%1$s',
								
								__( 'Can you provide the name of plugin?', 'wc-bsecure' )
							);
						?>
						</p>
						<textarea disabled name="user-reason" class="widefat" rows="4" ></textarea>
					</div>
					
				</div>

				<div class="bSecureoptionsBoxes">
					<label class="bsecure-field-description">
						<input type="radio" name="bsecure-survey-radios" value="3" data-has-field="true">
						<?php esc_html_e( 'I couldn\'t get the plugin to work', 'wc-bsecure' ); ?>
						
						
					</label>	
					<div class="bsecure-survey-extra-field reason-box3 hidden">
						<p>
						<?php
							printf(
								'%1$s %2$s %3$s',
								__( "We're sorry to hear that, check", 'wc-bsecure' ),
								'<a href="https://wordpress.org/support/plugin/bsecure">bSecure Support</a>.',
								__( 'Can you describe the issue?', 'wc-bsecure' )
							);
						?>
						</p>
						<textarea disabled name="user-reason" class="widefat" rows="4"></textarea>
					</div>			
				</div>

				<div class="bSecureoptionsBoxes">
					<label class="bsecure-field-description">
						<input type="radio" name="bsecure-survey-radios" value="4">
						<?php esc_html_e( 'It\'s a temporary deactivation', 'wc-bsecure' ); ?>
					</label>
				</div>

				<div class="bSecureoptionsBoxes">
					<label class="bsecure-field-description">
						<input type="radio" name="bsecure-survey-radios" value="5" data-has-field="true">
						<?php esc_html_e( 'The plugin broke my site', 'wc-bsecure' ); ?>
					</label>

					<div class="bsecure-survey-extra-field reason-box5 hidden">
						<p>
						<?php
							printf(
								'%1$s %2$s %3$s',
								__( "We're sorry to hear that, check", 'wc-bsecure' ),
								'<a href="https://wordpress.org/support/plugin/bsecure">bSecure Support</a>.',
								__( 'Can you describe the issue?', 'wc-bsecure' )
							);
						?>
						</p>
						<textarea disabled name="user-reason" class="widefat" rows="4"></textarea>
					</div>
				</div>

				<div class="bSecureoptionsBoxes">
					<label class="bsecure-field-description">
						<input type="radio" name="bsecure-survey-radios" value="6" data-has-field="true">
						<?php esc_html_e( 'The plugin suddenly stopped working', 'wc-bsecure' ); ?>
					</label>

					<div class="bsecure-survey-extra-field reason-box6 hidden">
						<p>
						<?php
							printf(
								'%1$s %2$s %3$s',
								__( "We're sorry to hear that, check", 'wc-bsecure' ),
								'<a href="https://wordpress.org/support/plugin/bsecure">bSecure Support</a>.',
								__( 'Can you describe the issue?', 'wc-bsecure' )
							);
						?>
						</p>
						<textarea disabled name="user-reason" class="widefat" rows="4"></textarea>
					</div>
				</div>

				<div class="bSecureoptionsBoxes">
					<label class="bsecure-field-description">
						<input type="radio" name="bsecure-survey-radios" value="7" data-has-field="true">
						<?php esc_html_e( 'Other', 'wc-bsecure' ); ?>
					</label>

					<div class="bsecure-survey-extra-field reason-box7 hidden">
						<p><?php esc_html_e( "Please describe why you're deactivating bSecure", 'wc-bsecure' ); ?></p>
						<textarea disabled name="user-reason" class="widefat" rows="4"></textarea>
					</div>
				</div>

				
				<?php
					$current_user       = wp_get_current_user();
					$current_user_email = $current_user->user_email;
					$current_user_name = $current_user->display_name;
				?>
				<input type="hidden" name="current-user-email" value="<?php echo $current_user_email; ?>">
				<input type="hidden" name="current-user-name" value="<?php echo $current_user_name; ?>">
				<input type="hidden" name="current-site-url" value="<?php echo esc_url( get_bloginfo( 'url' ) ); ?>">
				
				<input type="hidden" name="action" value="bsecure_deactivation_form_submit">
							
				<?php wp_nonce_field( 'bsecure_ajax_export', 'bsecure_ajax_export' ); ?>

				<div class="bsecure-modal__controls">

					
					<a class="bsecure-skip-deactivate-survey" href="#deactivation-link-show"><?php echo __('Skip and Deactivate'); ?></a>

					<div class="bSecureRightSideBtns">
						<button class="button button-primary bsecure-popup-close-button" type="button" onclick="jQuery('#TB_closeWindowButton').trigger('click');">
							<?php echo __('Cancel'); ?>
						</button>

						<button class="button button-primary bsecure-popup-form-submit-button">

							<?php echo __('Submit and Deactivate'); ?>
						</button>
					</div>
					<div class="spinner"></div>
				</div>
				<div class="mainAjaxBox"><p class="ajax-msg"></p></div>
			</form>
		</div>
		<?php

		// Echo content (deactivation form) from the output buffer.
		$output = ob_get_clean();

		$results['html'] = $output;

		wp_send_json( $results );
	}


	/**
	 * Ajax callback after the deactivation survey form has been submitted.
	 * Note: only for internal use
	 *
	 * @since 1.4.9
	 */
	public function bsecure_deactivation_form_submit() {		

		if ( ! check_ajax_referer( 'bsecure_ajax_export', 'bsecure_ajax_export', false ) ) {
			wp_send_json_error();
		}

		$reasons = [
				'1' => __( "I no longer need the plugin", 'wc-bsecure' ),
				'2' => __( "I found a better plugin", 'wc-bsecure' ),
				'3' => __( "I couldn't get the plugin to work", 'wc-bsecure' ),
				'4' => __( "It's a temporary deactivation", 'wc-bsecure' ),
				'5' => __( "The plugin broke my site", 'wc-bsecure' ),
				'6' => __( "The plugin suddenly stopped working", 'wc-bsecure' ),
				'7' => __( "Other", 'wc-bsecure' ),
				];

		

		$form_data = ( wp_parse_args( $_POST ) );
		
		// Get the selected radio value.
		$reason = isset( $form_data['bsecure-survey-radios'] ) ? sanitize_text_field($form_data['bsecure-survey-radios']) : 0;

		// Get the reason if any radio button has an optional text field.
		$reason_message = isset( $form_data['user-reason'] ) ? sanitize_text_field($form_data['user-reason']) : '';

		// Get the email of the user who deactivated the plugin.
		$user_email = isset( $form_data['current-user-email'] ) ? sanitize_text_field($form_data['current-user-email']) : '';

		// Get the name of the user who deactivated the plugin.
		$user_name = isset( $form_data['current-user-name'] ) ? sanitize_text_field($form_data['current-user-name']) : '';

		// Get the URL of the website on which bSecure plugin is being deactivated.
		$site_url = isset( $form_data['current-site-url'] ) ? sanitize_text_field($form_data['current-site-url']) : '';

		if(empty($reason)){

			wp_send_json_error(	__('Please select one of the option from list! ', 'wc-bsecure'));
		}

		$request_data = [
				
				'store_id' => get_option('wc_bsecure_store_id'),
				'status' => Bsecure_Admin::BSECURE_PLUGIN_STATUS_DISBALED,
				'reason' => $reasons[$reason],
				'description' => $reason_message,
				'user_name' => $user_name,
				'user_email' => $user_email,
			];


		$response = $this->bsecureGetOauthToken();	
			
		$validateResponse = $this->validateResponse($response,'token_request');

		if( $validateResponse['error'] ){		
			
			wp_send_json_error(	__('Response Error: ', 'wc-bsecure').$validateResponse['msg']);		

		} else {

			// Get Order //
			$access_token =  $response->access_token;

			//$headers =	'Authorization: Bearer '.$access_token;			
			$headers =  $this->getApiHeaders($access_token);				   			

			$params = 	[
							'method' => 'POST',
							'body' => $request_data,
							'headers' => $headers,					

						];	

			//$config = $this->getBsecureConfig();  	        
	    	$survey_endpoint = get_option('wc_bsecure_base_url') . '/plugin/status';

			$response = $this->bsecureSendCurlRequest( $survey_endpoint, $params);			

			$validateResponse = $this->validateResponse($response);	

			if($validateResponse['error']){			
				
				wp_send_json_error(	__('Response Error: ', 'wc-bsecure').$validateResponse['msg']);			

			}else{

				if (!empty($response->body)) {

					update_option("bsecure_activated", 0);
					wp_send_json_success(
						$response->body
					);
				} else {

					wp_send_json_error(	 __("No response from bSecure server",'wc-bsecure') );

				}			
				
			}
		}
		
	}

	/**
	* Load thickbox popup for bSecure plugin decativation servey
	*
	* @since 1.4.9
	*/
	public function loadThickBox(){

		add_thickbox();
	}


	/**
	* Send plugin activation status to bSecure server
	*
	* @since 1.4.9
	*/
	public function plugin_activate_deactivate($type = 'activate'){

		$current_user       = wp_get_current_user();
		$current_user_email = $current_user->user_email;
		$current_user_name = $current_user->display_name;
		$wc_bsecure_store_id = get_option('wc_bsecure_store_id', '');


		// check if store id not saved
		if(empty($wc_bsecure_store_id)){

			return false;
		}

		$status = Bsecure_Admin::BSECURE_PLUGIN_STATUS_NEW;
		$descriptions = __('Plugin activated');
		$isActivate = 1;

		if(($type == 'deactivate')){

			$status =  Bsecure_Admin::BSECURE_PLUGIN_STATUS_DISBALED;
			$descriptions = __('Plugin deactivated and survey skipped');
			$isActivate = 0;
		}
		
		

	   	$request_data = [
				
				'store_id' => $wc_bsecure_store_id,
				'status' => $status,
				'reason' => $descriptions,
				'description' => $descriptions,
				'user_name' => $current_user_name,
				'user_email' => $current_user_email,
			];


		$response = $this->bsecureGetOauthToken();	
			
		$validateResponse = $this->validateResponse($response,'token_request');

		if( $validateResponse['error'] ){		
			
			//error_log(	__('Response Error: ', 'wc-bsecure').$validateResponse['msg']);
			return false;		

		} else {

			// Get Order //
			$access_token =  $response->access_token;

			//$headers =	'Authorization: Bearer '.$access_token;	
			$headers =   $this->getApiHeaders($access_token);						   			

			$params = 	[
							'method' => 'POST',
							'body' => $request_data,
							'headers' => $headers,					

						];	
			
			$config = $this->getBsecureConfig();
	    	$survey_endpoint = !empty($config->pluginStatus) ? $config->pluginStatus :
                              get_option('wc_bsecure_base_url') . '/plugin/status';

			$response = $this->bsecureSendCurlRequest( $survey_endpoint, $params);			

			$validateResponse = $this->validateResponse($response);	

			if($validateResponse['error']){			
				
				//error_log(	__('Response Error: ', 'wc-bsecure').$validateResponse['msg']);	
				return false;		

			}else{

				update_option('bsecure_activated', $isActivate);				

				if (!empty($response->body)) {

					//error_log(json_encode([$response->body]));
					return true;

				} else {

					//error_log(__("No response from bSecure server",'wc-bsecure') );
					return false;

				}			
				
			}
		}
	}

}

