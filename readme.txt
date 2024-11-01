=== Shopp + SalesBinder ===
Contributors: salesbinder
Tags: inventory management, e-commerce, shopping cart, billing, invoicing, crm 
Requires at least: 3.1
Tested up to: 4.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

SalesBinder's official plugin for Shopp. Allows you to automatically synchronize all of your SalesBinder data with Shopp’s e-commerce plugin.

== Description ==

SalesBinder is our awesome cloud based online inventory management system. Shopp is an infinitely flexible WordPress ecommerce plugin and secure shopping cart.

Using our official Shopp + SalesBinder Plugin, you can integrate all of your inventory data (in real-time) directly into your website and have all your internet sales automatically entered into your SalesBinder account. This plugin will automatically sync your SalesBinder data into the Shopp Plugin and place your inventory data into the “Catalog” -> “Products” section. No custom theming required.

* Sync your SalesBinder inventory into Shopp’s Plugin in real-time
* Data synchronization includes, item details, photos, and even custom fields
* Automatically save website orders into SalesBinder as either invoices or estimates
* Save Shopp’s customer data into SalesBinder (with built-in duplicate checking)

For more information on SalesBinder integrations, please visit: http://www.salesbinder.com/tour/api-integrations/

== Installation ==

1. Install and activate the Shopp Plugin
2. Upload the entire shopp-salesbinder folder to your /wp-content/plugins/ directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. You will find a new 'SalesBinder' tab under “Shopp” -> “Setup” -> “SalesBinder.
5. Enter your Web Address, API Key, change any options if you like, and Save Changes.

Optional Settings:

* Set your Account Context and Document Context for where you’d like Shopp’s completed orders to be saved (ie. Customer and Invoice)
* Set the Sync Interval rate for polling your SalesBinder account looking for inventory changes

== Frequently Asked Questions ==

= Do I need a SalesBinder Account for this plug to work? =

Yes. This plugin connects to your SalesBinder account so you will need to register an account at www.salesbinder.com first.

= Where do I find more information about Shopp? =

You can visit their official website found here: http://shopplugin.net or visit their plugin page on WordPress.org here: https://wordpress.org/plugins/shopp/

= How do I get my inventory to display on my WordPress site? =

This plugin works seamlessly with Shopp’s E-Commerce Plugin, which works with any theme and attempts to follow existing styles in your theme. Changing your theme isn’t a requirement but there’s plenty of options to customize how it looks by using all of Shopp’s great theming tools.

= Do I need an SSL certificate? =

If you plan to take credit card numbers on your website, you must install and activate an SSL certificate to secure communication between your website visitors and your web server. If you plan to take payments through an offsite payment system, such as PayPal Payments Standard, you do not need an SSL certificate. Even if you don't need one, an SSL certificate can boost your storefront's credibility and does provide protection for other sensitive customer information.

== Screenshots ==

Screenshots are posted online [here](http://www.salesbinder.com/tour/api-integrations/ "SalesBinder + Shopp screenshots")

== Changelog ==

= 1.1.2 =
* Ability to sync product "weight" (from custom field) for shipping estimates in Shopp

= 1.1.1 =
* Image sync bug fix

= 1.1 =
* Synchronization performance improvements
* Minor bug fixes

= 1.0.8 =
* Minor bug fixes

= 1.0.7 =
* Improved handling of large photos synchronization

= 1.0.6 =
* Initial stable release

== Upgrade Notice ==

Replace the entire “shopp-salesbinder” folder in your plugins directory.