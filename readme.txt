=== Plugin Name ===
Contributors: paulfp
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=Q2Z9WJ7WKFS6W
Tags: sms, text, text message, wordpress, send, subscribe, sms subscribe, message, register, notification, webservice, sms panel, woocommerce, subscribes sms, twilio, telecoms cloud, bulksms, clockworksms, nexmo
Requires at least: 3.0.1
Tested up to: 4.5
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Using a simple shortcode, allow visitors to your website to enter their mobile number and receive a pre-defined SMS text message (eg. demo text, app download links etc.)

Usage: use the [wpSMS] shortcode and specify your "from" number and your pre-defined message, like so:

[wpSMS fromnumber="03332205000" message="This is an SMS text message sent to you via the Telecoms Cloud API."]

The "from" number is either any service number on your Telecoms Cloud account or if you don't have one, you can use a default from number which you'll find in your account settings. Watch this video for a more in-depth explanation: https://www.youtube.com/watch?v=Rl0HdR0nntY

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `sms-widget` directory and all its contents to the `/wp-content/plugins/` directory (or install via WordPress Plugins menu)
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Sign up for a free Telecoms Cloud API account at https://my.telecomscloud.com/sign-up.html?api and input your API credentials in the plugin's Settings page
4. Ensure you have cURL installed for PHP, which is required for the plugin to work. The Settings page will tell you if you need to install this, and how to do it.
5. Trigger the plugin by using the [wpSMS] shortcode with 2 attributes: your "from" number for the SMS, and the message you want to be delivered.

== Frequently Asked Questions ==

= Is the plugin free to use? =

The plugin itself is free to download, install and use. It uses the Telecoms Cloud API to send SMS messages, which requires you to sign up (which is free) to configure your API access credentials. You get £5 free credit when you sign up for a Telecoms Cloud API account, and text messages are charged on a per-message cost (from 3p to the UK) which is deducted from your pay-as-you-go credit. For full pricing information see www.telecomscloud.com/sms

= How do I top up my account? =

Log in to your Telecoms Cloud account: https://my.telecomscloud.com/topup.html

= I have another question, where can I ask? =

You can either contact me, the plugin author on Twitter (@paulfp) or you can contact Telecoms Cloud for any questions relating to the API or pricing. You can tweet @TelecomsAPI or visit www.telecomscloud.com/contact

Any questions which fit the FAQ will be added here in future releases.

= What other plugins use the Telecoms Cloud API? =

I have another plugin which you can use to format telephone numbers automatically for international visitors to your website. See https://wordpress.org/plugins/international-phone-number-display/


== Changelog ==

= 1.0.1 =
* Minor bugfixes and improvements.


= 1.0 =
* Initial stable release.

== Screenshots ==
1. The widget as it appears on the page where you used the [wpSMS] shortcode
2. The admin screen where you can specify your API credentials and view your up-to-date credit balance for sending SMS messages (to top up, log in to your Telecoms Cloud account.)
