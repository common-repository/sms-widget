<?php 
    /*
    Plugin Name: SMS Widget
    Plugin URI: https://wordpress.org/plugins/sms-widget/
    Description: Using a simple shortcode, allow visitors to your website to enter their mobile number and receive a pre-defined SMS text message.
    Author: Paul Freeman-Powell
    Version: 1.0.1
    Author URI: https://twitter.com/paulfp
    */

/*  Copyright 2015  Paul Freeman-Powell

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(!defined('TC_API_BASE_URL')) {
	define("TC_API_BASE_URL", "https://api.telecomscloud.com");
}
$ARGS = array(
    'timeout'     => 30,
    'redirection' => 5,
    'httpversion' => '1.0',
    'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
    'blocking'    => true,
    'headers'     => array(),
    'cookies'     => array(),
    'body'        => null,
    'compress'    => false,
    'decompress'  => true,
    'sslverify'   => true,
    'stream'      => false,
    'filename'    => null
);

// Start handling of Ajax request to send SMS
add_action('wp_ajax_wpsms', 'handle_ajax');
add_action('wp_ajax_nopriv_wpsms', 'handle_ajax');
add_action('wp_enqueue_scripts', 'enqueue_scripts');

function handle_ajax(){
	if(!wp_verify_nonce($_REQUEST['nonce'], 'wpsms')) {
		wp_send_json_error();
	}

	parse_str($_REQUEST['serialized'], $parsedString);
	
	$to_number = str_replace(" ", "", $parsedString['to_number']);
	$firstChar = substr($to_number, 0, 1);
	if(($firstChar != '+') AND ($firstChar != '0')) {
		// possible invalid formatting of E.164 number - attempt to fix by adding plus sign
		$to_number = '+' . $to_number;
	}
	
	if(preg_match('/^\+?\d+$/', $to_number)) {
		if(wpSMS_sendSMS($to_number, $parsedString['from_number'], $parsedString['message'])) { 
			$sendSMSresponse = 'SMS Message sent!';
		} else {
			$sendSMSresponse = 'Sorry, we couldn\'t send the SMS.';
		}
	} else {
		$sendSMSresponse = 'Please enter a valid phone number.';
	}

	wp_send_json_success( array(
		'script_response' => '<p>'.$sendSMSresponse.'</p>',
		'nonce'           => wp_create_nonce( 'wpsms' ),
	) );
}
function enqueue_scripts() {
	wp_enqueue_script('tcsms_js', plugins_url('/js/ajax.js', __FILE__ ), array('jquery'), '1.0', true);
	wp_localize_script('tcsms_js', 'wpsms', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'	   => wp_create_nonce('wpsms'),
	) );
}
// End handling of Ajax request to send SMS

function wpSMS_admin_actions() {
	add_options_page("SMS Widget - Provider API settings", "SMS Widget", "manage_options", "SMSWidget", "wpSMS_admin");
}
 
add_action('admin_menu', 'wpSMS_admin_actions');

function wpSMS_tag_func( $atts ) {
    $a = shortcode_atts( array(
        'fromnumber' => 'something',
		'message' => 'This is an SMS text message sent to you via the Telecoms Cloud API @ www.telecomscloud.com/sms'
    ), $atts );
	
	$html = '<div id="wp_sms_div">';
	$html .= '<img src="' . plugins_url( 'images/ajax-loader-big.gif', __FILE__ ) . '" id="loadingSpinner" style="display: none;"> ';
	$html .= '<form action="" method="POST" class="telecomscloud_sms_form" id="tcSMSform">';
	$html .= '<p>Enter your mobile number, then press Send SMS.</p>';
	$html .= '<p><input type="text" placeholder="eg. +447700900440" name="to_number"/></p>';
	$html .= '<p><input type="submit" value="Send SMS" /></p>';
	$html .= '<input type="hidden" name="from_number" value="'.$a['fromnumber'].'"/>';
	$html .= '<input type="hidden" name="message" value="'.$a['message'].'"/>';
	$html .= '</form>';
	$html .= '</div>';
	
	return $html;	
}
add_shortcode( 'wpSMS', 'wpSMS_tag_func' );

function wpSMS_getTcApiAccountDetails() {
	global $ARGS;

	$endPoint = '/v1/account/info';
	$url = TC_API_BASE_URL . $endPoint;

	$apiToken = wpSMS_token();
	if(!$apiToken) {
		return false;
	}

	$url .= '?access_token=' . $apiToken;
	$result = wp_remote_get( $url, $ARGS );

	if(count($result->errors) > 0) {
		return false;
	}
	$responseBody = json_decode($result['body']);

	if($result['response']['code'] === 200) {
		return $responseBody;
	} else {
		return false;
	}
}

function wpSMS_sendSMS($to, $from, $message) {

	$endPoint = '/v1/sms/outbound';
	$url = TC_API_BASE_URL . $endPoint;

	$apiToken = wpSMS_token();
	if(!$apiToken) {
		return false;
	}

	$url .= '?access_token=' . $apiToken;
	
	//open connection
	$ch = curl_init();

	$fields = array(
		'to' => $to,
		'from' => $from,
		'message' => stripslashes($message)
		);
	$fields_string = json_encode($fields, true);

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($fields_string))
	);
	curl_setopt($ch,CURLOPT_POST, count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);

	$result = json_decode($result, true);

	if(array_key_exists('error', $result)) {
		return false;
	} else {
		return true;
	}
}

function wpSMS_token() {
	// Get last saved Token & Expiry from WP Database
	$wpSMS_wpSMS_TelecomsCloudAPI_accessToken = get_option('wpSMS_wpSMS_TelecomsCloudAPI_accessToken');
	$wpSMS_wpSMS_TelecomsCloudAPI_accessToken_expiry = get_option('wpSMS_wpSMS_TelecomsCloudAPI_accessToken_expiry');
	
	$currentDate = new DateTime('now');
	$now = $currentDate->format('Y-m-d H:i:s');
	
	if( (!$wpSMS_wpSMS_TelecomsCloudAPI_accessToken) OR (!$wpSMS_wpSMS_TelecomsCloudAPI_accessToken_expiry) OR ($wpSMS_wpSMS_TelecomsCloudAPI_accessToken_expiry < $now) ) {
		// Token expired, get a new one and save it etc.
		$client_id = get_option('wpSMS_clientID');
		$client_secret = get_option('wpSMS_clientSecret');
		
		if( (!$client_id) OR (!$client_secret) ) {
			return false;
		}
		return wpSMS_getNewToken($client_id, $client_secret);
	} else {
		// Current token still valid for use
		return $wpSMS_wpSMS_TelecomsCloudAPI_accessToken;
	}
}

function wpSMS_getNewToken($client_id, $client_secret) {
	// Validate API Credentials provided
	$endPoint = "/v1/authorization/oauth2/grant-client";
	$url = TC_API_BASE_URL . $endPoint;

	//open connection
	$ch = curl_init();

	$fields = array(
		'client_id' => urlencode($client_id),
		'client_secret' => urlencode($client_secret)
		);
	$fields_string = json_encode($fields, true);

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($fields_string))
	);
	curl_setopt($ch,CURLOPT_POST, count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);

	$result = json_decode($result, true);
	
	if(array_key_exists('error', $result)) {
		return false;
	} else {
		
		$expiresDate = new DateTime('now');
		$expiresDate->add(new DateInterval('PT' . $result['expires_in'] . 'S'));
		$expiresDate = $expiresDate->format('Y-m-d H:i:s');
		update_option('wpSMS_wpSMS_TelecomsCloudAPI_accessToken', $result['access_token']);
		update_option('wpSMS_wpSMS_TelecomsCloudAPI_accessToken_expiry', $expiresDate);
			
		return $result['access_token'];
	}
}

function wpSMS_admin() {
    ?>
	<style type="text/css">
		label {
				display: block;
				width: 400px;
				text-align: right;
			}
	</style>
	<div class="wrap">
    <h1>SMS Widget</h1>
	<?php
	
	if(!function_exists('curl_init')) {
		?>
		<div class="error">
			<p>This plugin requires the Client URL Library for PHP (cURL) to be installed in order to function.</p>
		</div>
		<p>Please speak to your web host to get cURL installed. Alternatively, if you run your server yourself you can install it like this:</p>
		
		<pre>sudo apt-get install php5-curl<br />sudo service apache2 restart</pre>
		
		<p>If you're using <strong>php-fpm</strong>, you'll need to run this command instead after installing cURL:</p>
		
		<pre>sudo service php5-fpm restart</pre>
		
		<p>Once you've installed cURL, return to this config page and all will magically work!</p>
		<?php
		return;
	}
	
	if($_POST['wpSMS_hidden'] == 'Y') {
        //Form data sent
        
		$wpSMS_clientID = trim($_POST['wpSMS_clientID']);
		$wpSMS_clientSecret = trim($_POST['wpSMS_clientSecret']);
		
		$result = wpSMS_getNewToken($wpSMS_clientID, $wpSMS_clientSecret);
		
		if(!$result) {
			?>
			<div class="error"><p><strong>The Credentials you entered for the Telecoms Cloud API were not valid. Please check them and try again.</strong></p></div>
			<?php
		} else {
			update_option('wpSMS_clientID', $wpSMS_clientID);
			update_option('wpSMS_clientSecret', $wpSMS_clientSecret);
			?>
			<div class="updated"><p><strong>Options saved.</strong></p></div>
			<?php
		}
    } else {
		$wpSMS_clientID = get_option('wpSMS_clientID');
		$wpSMS_clientSecret = get_option('wpSMS_clientSecret');
	}
?>
	
	<p>The plugin lets you create a widget to appear on any page on your website, which allows visitors to enter their mobile number and receive an SMS, predefined by you.<br />Messages are sent worldwide within 60 seconds using the <a href="https://www.telecomscloud.com/sms" target="_blank">Telecoms Cloud API</a> which charges from 3p (0.03GBP) per message and gives you &pound;5 free credit when you first sign up.</p>
	
	<p><strong>Usage:</strong> use the <code>[wpSMS]</code> shortcode and specify your &quot;from&quot; number and your pre-defined message, like so:</p>
	
	<pre>[wpSMS fromnumber="03332205000" message="This is an SMS text message sent to you via the Telecoms Cloud API."]</pre>
	
	<p>The &quot;from&quot; number is either any service number on your Telecoms Cloud account or if you don't have one, you can use a default from number which you'll find in your account settings. <a href="https://www.youtube.com/watch?v=Rl0HdR0nntY" target="_blank">Watch this video</a> for a more in-depth explanation.</p>
	
	<form name="wpSMS_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
        <input type="hidden" name="wpSMS_hidden" value="Y">
        <h3>Enter Your Telecoms Cloud API Credentials</h3>
		<p>In order to send SMS messages, you must enter your API access keys below.<br />If you don't have them yet, <a href="https://my.telecomscloud.com/sign-up.html?api">sign up</a> to get your free keys and &pound;5 free credit which is enough to send over 150 SMS messages to the UK (at 3p per message).</p>
        <p><label>Client ID: <input type="text" name="wpSMS_clientID" value="<?php echo $wpSMS_clientID; ?>" size="30" required="required" /></label></p>
        <p><label>Client Secret: <input type="text" name="wpSMS_clientSecret" value="<?php echo $wpSMS_clientSecret; ?>" size="30" required="required" /></label></p>
		<p class="submit">
			<input type="submit" name="Submit" value="Save Credentials" />
		</p>
    </form>
		<p>Get your API access keys for free, along with &pound;5 free API credit, at <a href="https://my.telecomscloud.com/sign-up.html?api">https://my.telecomscloud.com/sign-up.html?api</a></p>	

		<?php
		$wpSMS_wpSMS_TelecomsCloudAPI_accessToken = get_option('wpSMS_wpSMS_TelecomsCloudAPI_accessToken');
		$wpSMS_wpSMS_TelecomsCloudAPI_accessToken_expiry = get_option('wpSMS_wpSMS_TelecomsCloudAPI_accessToken_expiry');
		if( ($wpSMS_wpSMS_TelecomsCloudAPI_accessToken !== FALSE) AND ($wpSMS_wpSMS_TelecomsCloudAPI_accessToken_expiry !== FALSE) ) {
		
			// Display credit balance etc.
			$tcApiAccountDetails = wpSMS_getTcApiAccountDetails();
			if(!$tcApiAccountDetails) {
				echo "<p>We had trouble authenticating your details with the Telecoms Cloud API. Please check your details above.";
			} else {
				?>
				<h3>Your Telecoms Cloud API Account Details</h3>
				<p><strong>Username:</strong> <?=$tcApiAccountDetails->username;?></p>
				<p><strong>Email Address:</strong> <?=$tcApiAccountDetails->primary_email;?></p>
				<p><strong>Credit Balance:</strong> &pound;<?=number_format($tcApiAccountDetails->credit_balance, 2);?> (<a href="https://my.telecomscloud.com/topup.html" target="_blank">Top Up</a>)</p>
				<?php
			}
		?>
		<p>Your current OAuth access token is <strong><?=$wpSMS_wpSMS_TelecomsCloudAPI_accessToken;?></strong> which expires on <strong><?=$wpSMS_wpSMS_TelecomsCloudAPI_accessToken_expiry;?></strong>.<br />The plugin will automatically request a new access token when this expires.</p>
		<?php 
		} ?>
		
</div>
	<?php
}
?>
