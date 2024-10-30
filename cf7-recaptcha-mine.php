<?php
	
	/**
	 * 
	 * Plugin Name: CF7 ReCaptcha
	 * Plugin URI: https://programmiere.de/
	 * Description: This plugin protects Contact Form 7 forms against spam and brute-force attacks. Invisible, GDPR compliant and user input isn't required.
	 * Version: 2.0.1
	 * Requires at least: 4.8+
	 * Requires PHP: PHP-Version 5.6+
	 * Author: Matthias Nordwig
	 * Author URI: https://programmiere.de
	 * License: GPLv2
	 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
	 * 
	 */

	namespace VENDOR\CF7_RECAPTCHA_MINE_FREE;

	defined( 'ABSPATH' ) or die( 'Are you ok?' );

	/** Class Core
	 * 
	 */
	class RCM_Main
	{

		/** Holding the instance of this class */
		public static $instance;

		/** String that represents the name of the plugin */
		private $plugin_name;

		/** An array of options in order to control the plugin */
		private $options;

		/** Option based action */
		const RCM_ACTION = Option::PREFIX . 'action';

		/** What to do with the action */
		const UPDATE = 'update';

		/** Constructor of the class
		 */
		private function __construct()
		{
			add_action( 'init', [ $this, 'run' ] );
			add_action( 'wp_head', [ $this, 'add_script_to_header' ], 10);
			add_action( 'wpcf7_init', [ $this, 'cf7_recaptcha_mine_init'], 10 );
			add_action( 'activated_plugin', [ $this, 'activation'] );
		}

		/** Adding a script to the header of each page. 
		 * It is not eqeued as within the script domain related variables 
		 * from backend have to be set dynamically
		*/
		public function add_script_to_header(){
			$fields_oneoff = "";
			$fields_oneoff .= '<style type="text/css">';
			$fields_oneoff .= '	.rcm-loading {';
			$fields_oneoff .= '		position: absolute;';
			$fields_oneoff .= '		display: none;';
			$fields_oneoff .= '		align-items: center;';
			$fields_oneoff .= '		justify-content: center;';
			$fields_oneoff .= '		border:1px;';
			$fields_oneoff .= '		height: 100%;';
			$fields_oneoff .= '		width: 100%;';
			$fields_oneoff .= '		background: rgba(0, 0, 0, 0.4);';
			$fields_oneoff .= '		font-size: 20;';
			$fields_oneoff .= '		position: fixed;';
			$fields_oneoff .= '		bottom: 0;';
			$fields_oneoff .= '		left: 0;';
			$fields_oneoff .= '		color: #fff;';
			$fields_oneoff .= '		padding: 10px !important;';
			$fields_oneoff .= '		margin: 0 auto !important;';
			$fields_oneoff .= '		-webkit-box-sizing: border-box;';
			$fields_oneoff .= '		-moz-box-sizing: border-box;';
			$fields_oneoff .= '		box-sizing: border-box;';
			$fields_oneoff .= '		z-index:9999;';
			$fields_oneoff .= '	}';
			$fields_oneoff .= '</style>';
			$fields_oneoff .= '<div class="rcm-loading">';
			$fields_oneoff .= '<span id="countdown" class="countdown" ></span>';
			$fields_oneoff .= '</div>';

			$fields = "";
			$fields .= '<input type="hidden" name="action" value="check_nonce">';
			$fields .= '<input type="hidden" class="hashStamp" name="hashStamp" id="hashStamp" value="" />';
			$fields .= '<input type="hidden" name="hashDifficulty" id="hashDifficulty" value="'. get_option(Option::POW_DIFFICULTY) .'" />';
			$fields .= '<input type="hidden" class="hashNonce" name="hashNonce" id="hashNonce" value="" />';

			$html = "
				<script>
					var stampLoaded = false;
					function initCaptcha(){
						var xhr = new XMLHttpRequest();
						xhr.open('POST', '".admin_url( 'admin-ajax.php' )."');
						xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
						xhr.onreadystatechange = function() {
						  if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
							var response = JSON.parse(xhr.responseText);
							document.querySelector('.hashStamp').value = response.stamp;
							findHash();
						  }
						};
						xhr.send('action=get_stamp');

					}

					function addStamp(e){
						if( ! stampLoaded){
							stampLoaded = true;
							
							var forms = document.querySelectorAll('form');
							for (var i = 0; i < forms.length; i++) {
							  forms[i].insertAdjacentHTML('afterbegin', '". $fields ."');
							}
							
							var firstForm = document.querySelector('form');
							firstForm.insertAdjacentHTML('afterbegin', '". $fields_oneoff ."');

							initCaptcha();
							setInterval( initCaptcha, ".get_option( Option::POW_TIME_WINDOW)." * 60000 );
						}
					}

					window.addEventListener( 'load', function () {
						document.addEventListener( 'keydown', addStamp, { once : true } );
						document.addEventListener( 'mousemove', addStamp, { once : true } );
						document.addEventListener( 'scroll', addStamp, { once : true } );
						document.addEventListener( 'click', addStamp, { once : true } );
					} );
				</script>
			";

			echo $html;
		}

		/** On activation go to settings menu*/
		public function activation( string $plugin ){
			/** On activation */
			if ( $plugin === plugin_basename( __FILE__ ) ) {

				$admin_Url = admin_url('options-general.php' . Option::PAGE_QUERY);

				exit( wp_redirect( $admin_Url ) );
			}
		}

		/** Get an instance of the class
		 * 
		 */
		public static function getInstance(): RCM_Main
		{
			require_once dirname( __FILE__ ) . '/class-option.php';

			if ( ! self::$instance instanceof self ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/** Wenn the plugin is run
		 */
		public function run()
		{
			//Name the plugin
			$this->plugin_name = "CF7 ReCaptcha Mine";

			$this->options = [
				Option::POW_SALT => new Option( __( 'Salt', 'cf7_recaptcha_mine' ),
													Option::STRING,
													hash( 'sha256', date( "Y-m-d H:i:s.u" ) ),
													"<br>Set this to a random string in order to give some unknown salt into the puzzle. It increases security, as it can't be guessed from client-side.
														<br>By default, this salt is generated as a hash from the point in time of your installation.
													"
											),
				Option::POW_STAMP_LOG => new Option( __( 'Directory for used puzzles', 'cf7_recaptcha_mine' ),
														Option::STRING,
														'puzzles.txt',
														"<br>
															<ol>
																<li>Set this to a path and file name to store recently submitted puzzles. </li>
																<li>The current dir is the root directory of your Wordpress installation.</li>
																<li>Used hash-puzzles are saved and will not be re-used anymore.</li> 
																<li>This mechanism is important to avoid spamming during the period of validity of a puzzle, after it has been solved</li>
																<li>Every 1000 entries the file is reseted</li>
															</ol>
														"
													),
				Option::POW_TIME_WINDOW => new Option( __( 'Time Window', 'cf7_recaptcha_mine' ),
															Option::INT,
															10,
															"<br>The time a hash-puzzle is valid and has to be computed and solved anew." ),
				Option::POW_DIFFICULTY => new Option( __( 'Difficulty', 'cf7_recaptcha_mine' ),
														Option::INT,
														12,
														"<br>Set this to control the amount of computing power required to solve the hash-puzzle.<br>
															If you don't know about the concept of proof-of-work, don't change it.<br>
															Approximate number of hash guesses needed for difficulty target of:<br>
															<ol>
																<li>Difficulty 1-4: 10</li>
																<li>Difficulty 5-8: 100</li>
																<li>Difficulty 9-12: 1,000</li>
																<li>Difficulty 13-16: 10,000</li>
																<li>Difficulty 17-20: 100,000</li>
																<li>Difficulty 21-24: 1,000,000</li>
																<li>Difficulty 25-28: 10,000,000</li>
																<li>Difficulty 29-32: 100,000,000</li>
															</ol>
														"
													),
			];

			$this->update_settings();

			/**
			 * @var string $id
			 * @var Option $option
			 */
			foreach ( $this->options as $id => $option ) {
				$type = $option->getType();
				$filter = $this->get_option_filter( $type );
				$filtered_value = filter_var( get_option( $id ), $filter );

				if ( ! empty( $filtered_value ) ){
					$option->setValue( $type === Option::INT ? intval( $filtered_value ) : strval( $filtered_value ) );
				} else {
					update_option( $id, $option->getDefault() );
				}
			}

			add_filter( sprintf( 'plugin_action_links_%s', plugin_basename( __FILE__ ) ), [ $this, 'get_action_links'] );
			add_action( 'admin_menu', [ $this, 'admin_menu'] );
		}

		/**  Get links for settings page
		 * 
		 */
		public function get_action_links( array $links ): array
		{
			return array_merge( ['settings' => sprintf( '<a href="options-general.php%s">%s</a>', Option::PAGE_QUERY, __( 'Settings', 'cf7_recaptcha_mine' ) )], $links );
		}

		/** Add the admin menu for the plugin
		 *
		 */
		public function admin_menu()
		{
			add_submenu_page( 'options-general.php'
							, $this->plugin_name
							, 'CF7 ReCaptcha Mine'
							, 'manage_options'
							, Option::PREFIX . 'options'
							, [ $this, 'options_page']
			);
			add_action( 'admin_init', [ $this, 'display_options'] );
		}

		/** Iterates through each option for the settings page of the plugin in order to show the input fields
		 * 
		 */
		public function display_options()
		{
			add_settings_section( Option::PREFIX . 'header_section', __( 'Congratualations! If you see this page, the installation is finished and the plugin should block all spam right now!
																			<ol>
																			<li>These parameters are not necessarily required, as they are good balanced by default.</li>
																			<li>If you face any problems or bugs, please give me a note in the support forum and I will fix it asap.</li>
																			<li>If you are happy with this plugin, please rate it <a href="https://wordpress.org/support/plugin/cf7-recaptcha-mine/reviews/#new-post">here</a>.</li>
																			</ol>
																			',
																			'cf7_recaptcha_mine' ),
																			[],
																			Option::PREFIX .
																			'options'
																		);

			foreach ( $this->options as $key => $option ) {
				$args = ['key'  => $key,
						'type' => $option->getType(),
						];
				add_settings_field( $key, $option->getName(), [ $this, 'display_input'], Option::PREFIX . 'options', Option::PREFIX . 'header_section', $args );
				register_setting( Option::PREFIX . 'header_section', $key );
			}
		}

		/** Retrieving the value for each option on the settings page for the plugin
		 *
		 */
		private function get_option_value( string $id )
		{
			$value = $this->options[ $id ]->getValue() ?? '';
			if (empty($value)){
				return $this->options[ $id ]->getDefault() ?? '';
			} else {
				return $this->options[ $id ]->getValue() ?? '';
			}
		}

		/** Retrieving the hint for each option on the settings page for the plugin
		 *
		 */
		private function get_option_hint( string $id )
		{
			return $this->options[ $id ]->getHint() ?? '';
		}

		/** Insert the input fields on the admins page of the plugin
		 *
		 */
		public function display_input( array $atts )
		{
			$key = $atts['key'];
			$type = $atts['type'];
			$val = $this->get_option_value( $key );
			$hint = $this->get_option_hint( $key );

			$allowed_html = array(
				'br' => array(),
				'ol' => array(),
				'li' => array(),
			);

			if ( $type === Option::INT ) {
			
				echo sprintf( '<input type="number" name="%1$s" class="regular-text" id="%1$s" value="%2$s" />%3$s'
							, esc_attr( $key )
							, esc_attr( $val )
							, wp_kses( $hint, $allowed_html )
				);
				
			} else {
				echo sprintf( '<input type="text" name="%1$s" class="regular-text" id="%1$s" value="%2$s" />%3$s'
							, esc_attr( $key )
							, esc_attr( $val )
							, wp_kses( $hint, $allowed_html )
				);
			}
		}

		/** Updating the values for the options
		 * 
		 */
		public function update_settings()
		{
			$postAction = strval( filter_input( INPUT_POST, self::RCM_ACTION, FILTER_SANITIZE_SPECIAL_CHARS ) );
			// If update and current user is allowed to manage options
			if ( $postAction === self::UPDATE && current_user_can( 'manage_options' ) ) {
				$hash = null;

				foreach ( $this->options as $key => $option ) {
					$postValue = filter_input( INPUT_POST, $key, $this->get_option_filter( $option->getType() ) );

					if ( $postValue ) {
						update_option( $key, $postValue );

						if ( substr( $key, -strlen( '_key' ) ) === '_key' ) {
							$hash .= $postValue;
						}
					} else {
						delete_option( $key );
					}
				}

				echo sprintf( '<div class="notice notice-success"><p><strong>%s</strong></p></div>', __( 'Settings saved!', 'cf7_recaptcha_mine' ) );
			}
		}

		/** Filter special chars if not int
		 * 
		 */
		private function get_option_filter( int $type ): int
		{
			return $type === Option::INT ? FILTER_SANITIZE_NUMBER_INT : FILTER_SANITIZE_FULL_SPECIAL_CHARS;
		}

		/**
		 * Drawing the options page for the plugin
		 */
		public function options_page()
		{
			echo sprintf( '<div class="wrap"><h1>%s - %s</h1><form method="post" action="%s">', $this->plugin_name, __( 'Settings', 'cf7_recaptcha_mine' ), Option::PAGE_QUERY );

			settings_fields( Option::PREFIX . 'header_section' );
			do_settings_sections( Option::PREFIX . 'options' );

			echo sprintf( '<input type="hidden" name="%s" value="%s">', self::RCM_ACTION, self::UPDATE );

			submit_button();

			echo '</form></div>';
		}

		/** Initialize the shortcode to let CF7 know about Captcha.
		 * 
		 */
		public function cf7_recaptcha_mine_init() {
			add_action( 'wp_enqueue_scripts', [ $this, 'my_plugin_assets'] );
			add_filter( 'wpcf7_spam', [ $this, 'cf7_recaptcha_mine_spam_check'], 10, 2 );
			add_action( 'wp_ajax_nopriv_get_stamp', [ $this, 'get_stamp'] );
			add_action( 'wp_ajax_get_stamp', [ $this, 'get_stamp'] );
		}

		/** Include the Javascript for proof of work calculation on the client-side
		 */
		public function my_plugin_assets() {
			wp_enqueue_script( 'cf7_recaptcha_mine-script',
							plugin_dir_url( __FILE__ ).'/scripts/cf7-recaptcha-mine.js' );
		}

		/** Spam Check
		 * 
		 */
		public function cf7_recaptcha_mine_spam_check( $spam, $submission = null ) {

			if ( $spam ) {
				return $spam;
			}

			$cf7form = \WPCF7_ContactForm::get_current();
			
			//The validation of the hashStamp is what this whole function is about.
			//The stamp is used to determine whether a valid hash and a valid nonce is given.
			//If either or are crap, we know that the input was manipulated.

			if ( ! isset($_POST['hashStamp']) ){
				$spam = true;
				return $spam;
			}

			$hash_stamp = filter_var( $_POST['hashStamp'], FILTER_SANITIZE_STRING );

			// SPAM CHECK #2: Now we check the stamp!
			if ( ! $this->check_stamp() ) {
				// Chatty Bots!
				$spam = true;

				if ( $submission ) {
					$submission->add_spam_log( array(
						'agent' => 'hashcash',
						'reason' => 'The hashkey is wrong.',
					) );
				}

				return $spam; // There's no need to go on, we've got flies in the honey.
			}

			// log that this puzzle has been used. 
			// If the puzzle is not valid, it won't be be logged, as the function will return earlier
			file_put_contents( get_option( Option::POW_STAMP_LOG ), $hash_stamp . "\n", FILE_APPEND | LOCK_EX );

			return $spam;
		}

		/** Function to generate a stamp that can be invoked via ajax
		 * 
		 */
		public function get_stamp() {
			$ip = $this->get_client_ip();
			$now = intval( time() / 60 );

			// stamp = hash of time (in minutes) . user ip . salt value
			$stamp = $this->hash_Values( $now . $ip . get_option( Option::POW_SALT ) );

			$array_result = array(
				'stamp' => $stamp,
			);

			// Make your array as json
			wp_send_json( $array_result );

			// Don't forget to stop execution afterward.
			wp_die();

		}

		/** Attempt to determine the client's IP address
		 * 
		 */
		private function get_client_ip() {
			$client = "";
			if ( getenv( 'HTTP_CLIENT_IP' ) )
				$client = getenv( 'HTTP_CLIENT_IP' );
			elseif ( getenv( 'HTTP_X_FORWARDED_FOR' ) )
				$client =  getenv( 'HTTP_X_FORWARDED_FOR' );
			elseif ( getenv( 'HTTP_X_FORWARDED' ) )
				$client =  getenv( 'HTTP_X_FORWARDED' );
			elseif ( getenv( 'HTTP_FORWARDED_FOR' ) )
				$client =  getenv( 'HTTP_FORWARDED_FOR' );
			elseif ( getenv( 'HTTP_FORWARDED' ) )
				$client =  getenv( 'HTTP_FORWARDED' );
			elseif ( getenv( 'REMOTE_ADDR' ) )
				$client =  getenv( 'REMOTE_ADDR' );

			$client.= getenv( 'HTTP_USER_AGENT' );
			return $client;
		}

		/** Drop in your desired hash function here
		 * 
		 */
		private function hash_Values( $x ) {
			return hash( 'sha256', $x );
		}

		/** checks validity, expiration, and difficulty target for a stamp
		 * 
		 */
		private function check_stamp() {
			
			//The validation of the hashStamp and the nonce is what this whole function is about.
			//The stamp is used to determine whether a valid hash and a valid nonce is given.
			//If either or are crap, we know that the input was manipulated.
			$stamp = filter_var( $_POST['hashStamp'], FILTER_SANITIZE_STRING );
			$nonce = $client_difficulty = '';

			// If the difficulty level is not of type int, it has been manipulated and thus remains empty.
			// This will cause the input to be classified as spam
			if( ctype_digit( $_POST['hashDifficulty'] ) ){
				$client_difficulty = filter_var( $_POST['hashDifficulty'], FILTER_SANITIZE_NUMBER_INT );
			}

			// The same holds for the nonce
			if( ctype_digit( $_POST['hashNonce'] ) ){
				$nonce = filter_var( $_POST['hashNonce'], FILTER_SANITIZE_NUMBER_INT );
			}
			
			$this->print_debug_information( "stamp: $stamp" );
			$this->print_debug_information( "difficulty: $client_difficulty" );
			$this->print_debug_information( "nonce: $nonce" );

			$this->print_debug_information( "difficulty comparison: $client_difficulty vs " . get_option( Option::POW_DIFFICULTY ) );
			if ( $client_difficulty != get_option( Option::POW_DIFFICULTY ) ) return false;

			$expectedLength = strlen( $this->hash_Values( uniqid() ) );
			$this->print_debug_information( "stamp size: " . strlen( $stamp ) . " expected: $expectedLength" );
			if ( strlen( $stamp ) != $expectedLength ) return false;

			if ( $this->check_expiration( $stamp ) ) {
				$this->print_debug_information( "PoW puzzle has not expired" );
			} else {
				$this->print_debug_information( "PoW puzzle expired" );
				return false;
			}

			// check the actual PoW
			if ( $this->check_proof_of_work( get_option( Option::POW_DIFFICULTY ), $stamp, $nonce ) ) {
				$this->print_debug_information( "Difficulty target met." );
			} else {
				$this->print_debug_information( "Difficulty target was not met." );
				return false;
			}

			// check if this puzzle has already been used to submit a message
			$savedStamps = 0;
			if ( ( $handle = fopen( get_option( Option::POW_STAMP_LOG ), "r" ) ) !== FALSE ) {
				while ( ( $data = fgetcsv( $handle, 1000, "\n" ) ) !== FALSE ) {
					if ( $data === $stamp ){
						return false;
					}
					++$savedStamps;
				}
				fclose( $handle );
			}

			// truncate the log if it starts getting long
			if ( $savedStamps > 1000 ) {
				file_put_contents( get_option( Option::POW_STAMP_LOG ), "$stamp\n" );
			}

			return true;
		}

		/** check that the stamp is within our allowed time window
		 *  this function also implicitly validates that the IP address and salt match
		 */
		private function check_expiration( $a_stamp ) {
			$tempnow = intval( time() / 60 );
			$ip = $this->get_client_ip();

			// gen hashes for $tempnow - $tolerance to $tempnow + $tolerance
			for( $i = -1*get_option(Option::POW_TIME_WINDOW); $i < get_option(Option::POW_TIME_WINDOW); $i++) {
				$this->print_debug_information( "checking $a_stamp versus " . $this->hash_Values( ( $tempnow - $i ) . $ip . get_option(Option::POW_SALT ) ) );
				if ( $a_stamp === $this->hash_Values( ( $tempnow + $i ) . $ip . get_option( Option::POW_SALT ) ) ) {
					$this->print_debug_information( "stamp matched at " . $i . " minutes from now" );
				return true;
				}
			}

			$this->print_debug_information( "stamp expired" );
			return false;
		}

		/** check that the hash of the stamp + nonce meets the difficulty target
		 *  
		 */
		private function check_proof_of_work( $difficulty, $stamp, $nonce ) {
			// get hash of $stamp & $nonce
			$this->print_debug_information( "checking $difficulty bits of work" );
			$work = $this->hash_Values( $stamp . $nonce );

			$leadingBits = $this->hc_ExtractBits( $work, $difficulty );

			$this->print_debug_information( "checking $leadingBits leading bits of $work for difficulty $difficulty match" );

			// if the leading bits are all 0, the difficulty target was met
			return ( strlen( $leadingBits ) > 0 && intval( $leadingBits ) === 0 );
		}

		/** Uncomment the echo statement to get debug info printed to the browser
		 *  
		 */
		private function print_debug_information( $x ) {
			//echo "<pre>$x</pre>\n";
		}

		/** Get the first num_bits of data from this string
		 *  
		 */
		private function hc_ExtractBits( $hex_string, $num_bits ) {
			$bit_string = "";
			$num_chars = ceil( $num_bits / 4 );
			for( $i = 0; $i < $num_chars; $i++ )
				$bit_string .= str_pad( base_convert( $hex_string[ $i ], 16, 2 ), 4, "0", STR_PAD_LEFT ); // convert hex to binary and left pad with 0s

			$this->print_debug_information( "requested $num_bits bits from $hex_string, returned $bit_string as " . substr( $bit_string, 0, $num_bits ) );
			return substr( $bit_string, 0, $num_bits );
		}
	}

	RCM_Main::getInstance();

?>