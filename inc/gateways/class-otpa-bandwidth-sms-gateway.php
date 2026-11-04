<?php


if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Otpa_Bandwidth_Sms_Gateway extends Otpa_Abstract_Gateway
{
	protected $identifier_meta = 'phone';
	protected $code_length     = 4;
	protected $code_chars      = '0123456789';
	protected $client;
	protected  $user;
	protected $app_tag = '';

	public $last_error = '';
	public $last_result = '';

	public function __construct($init_hooks = false, $settings_renderer = false, $otpa_settings = false)
	{
		$this->name = __('Bandwidth SMS', 'otpa');

		parent::__construct($init_hooks, $settings_renderer, $otpa_settings);

		if ($init_hooks) {
			add_action('otpa_before_otp_form', array($this, 'print_form_hint'), 10, 1);
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);

			add_filter('otpa_otp_widget_identifier_placeholder', array($this, 'identifier_name'), 10, 1);
			add_filter('otpa_otp_identifier_field_label', array($this, 'identifier_name'), 10, 1);
			add_filter('otpa_wp_error_message', array($this, 'error_message_alter'), 10, 3);
		}

		$domain = '';
		if (defined('DOMAIN_CURRENT_SITE')) {
			$domain = DOMAIN_CURRENT_SITE;
		} else {
			$domain = get_option('siteurl');
			$domain = str_replace(array('https://', 'http://', '/'), '', $domain);
		}


		$this->app_tag = $domain;
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function admin_enqueue_scripts($hook_suffix) {}

	public function validate_settings($valid, $main_settings)
	{
		$errors = is_array($valid) ? $valid : array();

		if (
			isset($main_settings['enable_2fa']) && $main_settings['enable_2fa'] ||
			isset($main_settings['enable_passwordless']) && $main_settings['enable_passwordless'] ||
			isset($main_settings['enable_validation']) && $main_settings['enable_validation']
		) {
			// translators: %s is the name of the required field
			$missing_field_format = __('Authentication Gateway setting value "%s" is missing.', 'otpa');

			if (! isset($this->settings['user_name']) || empty($this->settings['user_name'])) {
				$errors[] = sprintf(
					$missing_field_format,
					__('Username', 'otpa')
				);
			}

			if (! isset($this->settings['user_pass']) || empty($this->settings['user_pass'])) {
				$errors[] = sprintf(
					$missing_field_format,
					__('Password', 'otpa')
				);
			}



			if (
				isset($this->settings['min_phone_length']) &&
				! empty($this->settings['min_phone_length']) &&
				isset($this->settings['max_phone_length']) &&
				! empty($this->settings['max_phone_length']) &&
				$this->settings['max_phone_length'] < $this->settings['min_phone_length']
			) {
				$errors[] = __('The Phone Max. Digits value must be greater than or equal to the Phone Min. Digits value.', 'otpa');
			}
		}

		return empty($errors) ? true : $errors;
	}

	public function init_settings_definition()
	{
		$default_settings      = self::get_default_settings();
		$this->settings_fields = array(
			'main'           => array(

				array(
					'id'    => 'user_name',
					'label' => __('Username', 'otpa') . ' <span class="required">*</span>',
					'type'  => 'input_password',
					'class' => 'regular-text toggle',
					'help'  => '',
				),
				array(
					'id'    => 'user_pass',
					'label' => __('Password', 'otpa') . ' <span class="required">*</span>',
					'type'  => 'input_password',
					'class' => 'regular-text ',
					'help'  => '',
				),
				array(
					'id'    => 'application_id',
					'label' => __('Application ID', 'otpa') . ' <span class="required">*</span>',
					'type'  => 'input_text',
					'class' => 'regular-text toggle',
					'help'  => '',
				),
				array(
					'id'    => 'account_id',
					'label' => __('Account ID', 'otpa') . ' <span class="required">*</span>',
					'type'  => 'input_text',
					'class' => 'regular-text toggle',
					'help'  => '',
				),
				array(
					'id'    => 'send_number',
					'label' => __('Send Number', 'otpa') . ' <span class="required">*</span>',
					'type'  => 'input_text',
					'class' => 'regular-text toggle',
					'help'  => '',
				),

				// translators: %s is the Gateway name
				'title' => sprintf(__('%s Gateway Settings', 'otpa'), $this->name),
			),
			'phone'          => array(

				array(
					'id'      => 'max_phone_length',
					'label'   => __('Phone Max. Digits', 'otpa'),
					'type'    => 'input_number',
					'min'     => 0,
					'step'    => 1,
					'default' => $default_settings['max_phone_length'],
					'class'   => '',
					'help'    => __('The maximum amount of digits of mobile phone numbers (excluding prefix)', 'otpa') . '<br/>' . __('If left empty, "11" (Mainland China) will be used.', 'otpa'),
				),
				array(
					'id'      => 'min_phone_length',
					'label'   => __('Phone Min. Digits', 'otpa'),
					'type'    => 'input_number',
					'min'     => 0,
					'step'    => 1,
					'default' => $default_settings['min_phone_length'],
					'class'   => '',
					'help'    => __('The minimum amount of digits of mobile phone numbers (excluding prefix)', 'otpa') . '<br/>' . __('If left empty, "11" (Mainland China) will be used.', 'otpa'),
				),
				array(
					'id'      => 'allowed_phone_prefixes',
					'label'   => __('Allowed Phone Prefixes', 'otpa'),
					'type'    => 'input_text',
					'min'     => 0,
					'step'    => 1,
					'default' => $default_settings['allowed_phone_prefixes'],
					'class'   => '',
					'help'    => __('A comma-separated list of prefixes allowed at user input (for example: "+86,+1,+33").', 'otpa') . '<br/>' . __('If left empty or none is provided at user input, the Default Phone Prefix will be used for the input phone number.', 'otpa') . '<br/>' . __('Forced to "+86,+1" if the International SMS Product is used and restricted to Chinese and US Phone Numbers.', 'otpa'),
				),
				array(
					'id'      => 'default_phone_prefix',
					'label'   => __('Default Phone Prefix', 'otpa'),
					'type'    => 'input_text',
					'min'     => 0,
					'step'    => 1,
					'default' => $default_settings['default_phone_prefix'],
					'class'   => '',
					'help'    => '',
				),
				'title' => __('Mobile Phone Number Settings', 'otpa'),
			),
			'sms_attributes' => array(
				array(
					'id'    => 'intl_message',
					'label' => __('International SMS Product Message', 'otpa'),
					'type'  => 'input_text',
					'class' => 'regular-text',
					'default' => $default_settings['intl_message'],
					'help'  => '',
				),


				'title' => __('SMS Settings', 'otpa'),
			),
		);

		$this->settings_fields = apply_filters('otpa_settings_fields_' . $this->get_gateway_id(), $this->settings_fields);
	}

	public function sanitize_settings($settings, $old_settings = array())
	{
		$settings         = parent::sanitize_settings($settings, $old_settings);
		$default_settings = self::get_default_settings();

		if (empty($settings['default_phone_prefix'])) {
			$settings['default_phone_prefix'] = $default_settings['default_phone_prefix'];
		}

		if (empty($settings['max_phone_length'])) {
			$settings['max_phone_length'] = $default_settings['max_phone_length'];
		}

		if (empty($settings['min_phone_length'])) {
			$settings['min_phone_length'] = $default_settings['min_phone_length'];
		}



		$settings['allowed_phone_prefixes'] = str_replace(' ', '', $settings['allowed_phone_prefixes']);
		$settings['default_phone_prefix']   = str_replace(' ', '', $settings['default_phone_prefix']);


		return apply_filters($this->get_gateway_id() . '_sanitize_settings', $settings);
	}

	public function print_form_hint($otp_form_type)
	{
		$output = '<p class="message">';
		$phone  = $this->get_user_identifier();

		if (! empty($phone)) {
			$output .= sprintf(
				//Enter your mobile phone number %s to request a Verification Code.
				__('Enter your mobile phone number', 'otpa'),
				'<br/><strong>' . otpa_mask_phone($phone) . '</strong><br/>'
			);
		} else {
			$df_text = __('Access your lab results securely', 'otpa');
			$title                = Otpa_Style_Settings::get_option('login_sub_title');

			$output .= !empty($title) ? $title : $df_text;
		}

		$output .= '</p>';

		echo $output; // @codingStandardsIgnoreLine
	}

	public function identifier_name($placeholder)
	{
		return __('Mobile Phone Number', 'otpa');
	}

	public function sanitize_user_identifier($phone, $user_id = false)
	{
		$phone           = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
		$prefixes_string = $this->get_option('allowed_phone_prefixes');
		$prefixes        = (empty($prefixes_string)) ? '' : array_map('trim', explode(',', $prefixes_string));
		$prefix          = '';
		$phone           = preg_replace('/[\(\)\_\- ]/', '', $phone);

		if (! empty($prefixes)) {

			foreach ($prefixes as $maybe_prefix) {

				if (0 === stripos($phone, $maybe_prefix)) {
					$phone  = str_replace($maybe_prefix, '', $phone);
					$prefix = $maybe_prefix;

					break;
				}
			}
		}

		if (empty($prefix)) {
			$default_prefix = $this->get_option('default_phone_prefix');
			$prefix         = (0 !== stripos($default_prefix, '+')) ? '+' . $default_prefix : $default_prefix;
		}

		$phone = str_replace('+', '', str_replace($prefix, '', $phone));

		return empty($phone) ? '' : $prefix . $phone;
	}

	public function is_valid_identifier($phone)
	{
		$phone           = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
		$prefixes_string = $this->get_option('allowed_phone_prefixes');
		$prefixes        = (empty($prefixes_string)) ? '' : array_map('trim', explode(',', $prefixes_string));
		$min_length      = $this->get_option('min_phone_length');
		$max_length      = $this->get_option('min_phone_length');
		$prefix          = '';
		$phone           = preg_replace('/[\(\)\_\- ]/', '', $phone);

		if (! empty($prefixes)) {

			foreach ($prefixes as $maybe_prefix) {

				if (0 !== stripos($maybe_prefix, '+')) {
					$maybe_prefix = '+' . $maybe_prefix;
				}

				if (0 === stripos($phone, $maybe_prefix)) {
					$phone  = str_replace($maybe_prefix, '', $phone);
					$prefix = $maybe_prefix;

					break;
				}
			}
		}

		if (empty($prefix)) {
			$default_prefix = $this->get_option('default_phone_prefix');
			$prefix         = (0 !== stripos($default_prefix, '+')) ? '+' . $default_prefix : $default_prefix;
		}

		$phone = str_replace($prefix, '', $phone);

		if (0 === stripos($phone, '+')) {

			return false;
		}

		if (strlen($phone) > $max_length || strlen($phone) < $min_length) {

			return false;
		}

		return true;
	}

	public function error_message_alter($message, $code, $data)
	{

		switch ($code) {
			case 'OTPA_INVALID_IDENTIFIER':
				$prefixes_string = $this->get_option('allowed_phone_prefixes');

				if (empty($prefixes_string)) {
					$prefixes = array($this->get_option('default_phone_prefix'));
				} else {
					$prefixes = array_map('trim', explode(',', $prefixes_string));
				}

				foreach ($prefixes as $prefix) {

					if (0 !== stripos($prefix, '+')) {
						$prefix = '+' . $prefix;
					}
				}

				$prefix_string  = implode(', ', $prefixes);
				$min_length     = $this->get_option('min_phone_length');
				$max_length     = $this->get_option('min_phone_length');
				$default_prefix = $this->get_option('default_phone_prefix');

				if ($min_length === $max_length) {
					// translators: %s is the phone length
					$length_string = sprintf(__('%s digits', 'otpa'), $min_length);
				} else {
					// translators: %1$s is the phone min length, %2$s is the phone max length
					$length_string = sprintf(__('between %1$s and %2$s digits', 'otpa'), $min_length);
				}
				// translators: %1$s is the phone length hint, %2$s is the phone prefix hint %3$s is the default prefix hint
				$message = __('The mobile phone number is invalid.', 'otpa') . ' ' . sprintf(__('Format: %1$s, allowed prefixes: %2$s ; default %3$s.', 'otpa'), $length_string, $prefix_string, $default_prefix) . '<br/>' . __('Please enter a valid phone number.');
				break;
			case 'OTPA_DUPLICATE_IDENTIFIER':
				$message = __('The mobile phone number is already registered.', 'otpa') . '<br/>' . __('Please enter another valid mobile phone number and try again.');
				break;
			default:
				break;
		}

		return $message;
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected static function get_default_settings()
	{

		return array(
			'intl'                   => false,
			'china_us'               => false,
			'intl_message'           => 'Verification Code: ###CODE###',
			'max_phone_length'       => 11,
			'min_phone_length'       => 10,
			'allowed_phone_prefixes' => '',
			'default_phone_prefix'   => '+1',
		);
	}

	public function get_user_by_identifier($identifier)
	{
		global $wpdb;
		$identifier = trim($identifier);
		$identifier = str_replace(array(' ', '-', '(', ')', ' ', '.'), '', $identifier);
		$user = parent::get_user_by_identifier($identifier);

		$phone = str_replace('+1', '', $identifier);
		$phone = trim($phone);

		$phones = array();
		$phones[] = $identifier;
		$phones[] = $phone;
		$phones[] = substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
		$phones[] = '(' . substr($phone, 0, 3) . ')' . substr($phone, 3, 3) . '-' . substr($phone, 6);
		$phones[] = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
		$phones[] = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . ' - ' . substr($phone, 6);
		$phones = array_values(array_unique($phones));
		$phone_placeholders = implode(', ', array_fill(0, count($phones), '%s'));
		if (!$user) {
			//fix db
			// The table name is derived from the WordPress database prefix; all lookup values are bound.
			$user_id = $wpdb->get_var($wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value IN ({$phone_placeholders})",
				array_merge(array($this->identifier_meta), $phones)
			));
			if ($user_id > 0) {
				$phone = '+1' . $phone;
				update_user_meta($user_id, $this->identifier_meta, $phone);
				$user = parent::get_user_by_identifier($identifier);
			}
		}

		if (!$user && defined('DEMO_PATIENT_ID') && is_user_logged_in()) {
			global  $current_user;
			$current_user = false;
		}

		if (!$user && !is_user_logged_in() && strlen($identifier) > 8) {
			$is_test_user = false;
			//check patients table
			$sql = $wpdb->prepare(
				"SELECT * FROM ci_patients WHERE pa_phone IN ({$phone_placeholders}) ORDER BY pa_ID DESC LIMIT 1",
				$phones
			);

			if (defined('DEMO_PATIENT_ID') && defined('DEMO_PATIENT_CODE') && $_POST['payload']['code'] == DEMO_PATIENT_CODE) {
				$sql = $wpdb->prepare('SELECT * FROM ci_patients WHERE pa_ID = %d LIMIT 1', absint(DEMO_PATIENT_ID));

				$is_test_user = true;
			}
			if (isset($_REQUEST['tr_debug2'])) {
				//var_dump($sql);exit;
			}
			$patient = $wpdb->get_row($sql);

			if ($patient) {
				$user_email = $patient->pa_email;

				if (empty($user_email)) {
					$user_email = time() . '@' . $_SERVER['HTTP_HOST'];
					$user_name = time();
				} else if (email_exists($user_email)) {
					$user_email = time() . $user_email;
					$user_name = time();
				} else {
					$user_name = $user_email;
				}
				if (username_exists($user_name) || empty($user_name)) {
					$user_name = time();
				}

				if (username_exists($user_name)) {
					$user_name .= rand(1, 100);
				}

				if (email_exists($user_email)) {
					$user_email .= rand(1, 100);
				}

				//create new user
				$userdata = array(
					'user_login' => $user_name,
					'user_email' => $user_email,
					'user_pass' => time(),
					'first_name' => $patient->pa_first,
					'last_name' => $patient->pa_last,
					'role' => 'patient',
				);

				$user_id = wp_insert_user($userdata);
				if ($user_id > 0) {
					update_user_meta($user_id, '_patient_id', $patient->pa_ID);
					update_user_meta($user_id, 'birthday', $patient->pa_dob);
					update_user_meta($user_id, 'address', $patient->pa_address);
					update_user_meta($user_id, 'city', $patient->pa_city);
					update_user_meta($user_id, 'state', $patient->pa_state);
					update_user_meta($user_id, 'zip', $patient->pa_zip);
					update_user_meta($user_id, 'phone', $identifier);

					if ($is_test_user) {
						update_user_meta($user_id, 'sentsmswc', -1);
						wp_schedule_single_event(time(), 'cron_send_demo_sms_mms', array($user_id));
					}

					$wpdb->update($wpdb->users, array('patient_id' => $patient->pa_ID), array('ID' => $user_id));
					$user = get_user_by('id', $user_id);
				}
			}

			if (!$user) {
				$log_file = WP_CONTENT_DIR . '/log/sms_user_not_found.txt';
				$f = fopen($log_file, 'a+');
				if ($f) {
					fwrite($f, date_i18n('Y-m-d H:i:s') . ': ' . $identifier . "\n\n");
					fclose($f);
				}
				if (filesize($log_file) > 1000000) {
					file_put_contents($log_file, substr(file_get_contents($log_file), 900000));
				}
			}
		}

		$this->user = $user;


		return $user;
	}

	protected function validate_input_identifier($phone)
	{

		if (! parent::validate_input_identifier($phone)) {

			return new WP_Error(
				'OTPA_INVALID_PHONE_NUMBER',
				__('Invalid mobile phone number. Please enter your registered phone number and try again.'),
				array(
					'method'     => __METHOD__,
					'identifier' => $phone,
				)
			);
		}

		return true;
	}

	public function load_library()
	{

		try {
			require_once OTPA_PLUGIN_PATH . 'vendor/autoload.php'; // bandwidth/sdk ^13 (Composer; same BandwidthLib API)

			return true;
		} catch (Throwable $e) {

			if (method_exists($e, 'getCode') && method_exists($e, 'getMessage')) {
				otpa_db_log(__METHOD__ . ' - error ' . $e->getCode() . ': ' . $e->getMessage(), 'alert', true);
			} else {
				otpa_db_log(__METHOD__ . ' - error ' . print_r($e, true), 'alert', true); // @codingStandardsIgnoreLine
			}

			return false;
		}
	}

	public function init_api()
	{

		try {
			$config = new BandwidthLib\Configuration(
				array(
					'messagingBasicAuthUserName' => $this->settings['user_name'],
					'messagingBasicAuthPassword' =>  $this->settings['user_pass'],
				)
			);

			$this->client = new BandwidthLib\BandwidthClient($config);

			return true;
		} catch (Throwable $e) {



			if (method_exists($e, 'getCode') && method_exists($e, 'getMessage')) {
				otpa_db_log(__METHOD__ . ' - error ' . $e->getCode() . ': ' . $e->getMessage(), 'alert', true);
			} else {
				otpa_db_log(__METHOD__ . ' - error ' . print_r($e, true), 'alert', true); // @codingStandardsIgnoreLine
			}

			return false;
		}
	}

	protected function send_sandox_request($phone, $otp_code)
	{
		$adding = apply_filters('sms_verify_adding_msg', '', $this->user, $phone, $otp_code);

		return array(
			'status'  => true,
			// translators: %s is the user's OTP identifier
			'message' => sprintf(__('An SMS with a Verification Code was sent to %s (sandbox).', 'otpa'), $phone),
			'code'    => 'OK',
			'adding_html' => $adding,
		);
	}

	public function listmsg(
		$messageId = null,
		$sourceTn = null,
		$destinationTn = null,
		$messageStatus = null,
		$errorCode = null,
		$fromDateTime = null,
		$toDateTime = null,
		$pageToken = null,
		$limit = null
	) {
		$messagingClient = $this->client->getMessaging()->getClient();
		return $messagingClient->getMessages(
			$this->settings['account_id'],
			$messageId,
			$sourceTn,
			$destinationTn,
			$messageStatus,
			$errorCode,
			$fromDateTime,
			$toDateTime,
			$pageToken,
			$limit
		);
	}

	public function send_sms($phone, $msg, $patient_name = '', $assen = '', $sendtype = '', $patient_id = '', $media = array())
	{
		global $wpdb;

		if (isset($_GET['tr_debug2'])) {
			$msg .= "\n\n" . implode("\n", $media);
			wp_mail('pntrinh29@gmail.com', 'send sms', $msg . "\n\n" . ' phone: ' . $phone);
			//adding count
			$logdata = array(
				'phone' => $phone,
				'patient_name' => $patient_name,
				'assen' => $assen,
				'sendtype' => $sendtype,
				'message' => $msg,
				'created' => date_i18n('Y-m-d H:i:s')
			);
			if ($patient_id > 0) {
				$logdata['patient_id'] = $patient_id;
			}
			$wpdb->insert('tr_log_sms', $logdata);
			return true;
		}

		$phone = trim($phone);
		$phone = str_replace(array('-', ' ', '(', ')', '.'), '', $phone);
		if (strpos($phone, '+1') !== 0) {
			$phone = '+1' . $phone;
		}


		$messagingClient = $this->client->getMessaging()->getClient();

		$body = new BandwidthLib\Messaging\Models\MessageRequest();
		$body->from = $this->settings['send_number'];
		$body->to = array($phone);

		$body->tag = $this->app_tag;
		if (!empty($media)) {
			$body->media = $media;
		}
		$body->text = $msg;
		$body->applicationId = $this->settings['application_id'];
		$messagingAccountId = $this->settings['account_id'];
		try {
			$this->last_result = $messagingClient->createMessage($messagingAccountId, $body);

			//adding count
			$logdata = array(
				'phone' => $phone,
				'patient_name' => $patient_name,
				'assen' => $assen,
				'sendtype' => $sendtype,
				'message' => $msg,
				'created' => date_i18n('Y-m-d H:i:s')
			);
			if ($patient_id > 0) {
				$logdata['patient_id'] = $patient_id;
			}
			$wpdb->insert('tr_log_sms', $logdata);
			if (!empty($wpdb->last_error) && isset($_GET['debug'])) {
				echo $wpdb->last_error;
			}
			return true;
		} catch (Exception $e) {
			$logdata = array(
				'phone' => $phone,
				'patient_name' => $patient_name,
				'assen' => $assen,
				'sendtype' => 'f',
				'message' => $msg . ' => ' . $e->getMessage(),
				'created' => date_i18n('Y-m-d H:i:s')
			);
			if ($patient_id > 0) {
				$logdata['patient_id'] = $patient_id;
			}
			$wpdb->insert('tr_log_sms', $logdata);
			$this->last_error = $e->getMessage();
		}
		return false;
	}

	public function send_request($phone, $otp_code, $sendtype = 'v')
	{
		$message = __('The Authentication Gateway has experienced a problem.', 'otpa') . '<br/>' . __('Please contact an administrator.', 'otpa');

		if (empty($this->settings['intl_message']) || stripos($this->settings['intl_message'], '###CODE###') === false) {
			$this->settings['intl_message'] = 'Verification Code: ###CODE###';
		}

		$txt = str_replace('###CODE###', $otp_code, $this->settings['intl_message']);

		try {

			$result = $this->send_sms($phone, $txt, '', $otp_code, $sendtype);

			if (! $result) {

				return array(
					'status'  => false,
					'message' => __('An undefined error occured - please try again or use another phone number.', 'otpa'),
				);
			}
			$adding = apply_filters('sms_verify_adding_msg', '', $this->user, $phone, $otp_code);
			return array(
				'status'  => true,
				// translators: %s is the user's mobile phone number
				'message' => sprintf(__('An SMS with a Verification Code was sent to %s.', 'otpa'), $phone),
				'code'    => 'OK',
				'adding_html' => $adding,
			);
		} catch (Exception $e) {
			otpa_db_log(__METHOD__ . ' - error ' . $e->getErrorCode() . ': ' . $e->getErrorMessage(), 'alert', true);

			return array(
				'status'  => false,
				'message' => $message,
				'code'    => $e->getErrorCode(),
			);
		}
	}
}
