=== Bonus for Woo ===
Contributors: calliko
Donate link: https://yoomoney.ru/to/410011302808683
Tags: loyalty, cashback, points, reward, referral
Requires at least:  5.0
Tested up to:  6.6
WC requires at least: 6.0
WC tested up to: 9.3.3
Stable tag: 6.5.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin is designed to create a bonus system with cashback.

== Description ==

This plugin is designed to create a bonus system with cashback.
The cashback percentage is calculated based on the users' status in the form of bonus points.
Each user status has a corresponding cashback percentage.
The users' status depends on the total amount of the users orders.
Cashback is accumulated in the client's virtual wallet.

[youtube https://youtu.be/6u4mHdHVhkE]

== Free plugin features ==
* Points for product reviews.
* Integer and decimal points
* Hide the ability to spend points for discounted items.
* Show the history of bonus points.
* Email notifications.
* Export and import points.
* Shortcodes.

== Additional settings for the PRO version ==
* Points on your birthday.
* Daily points for the first login.
* Points for registration.
* Exclude categories of products that cannot be purchased with cashback points.
* Cashback is not accrued for discounted items.
* Exclude payment methods.
* Exclude items that cannot be purchased with Cashback Points.
* Minimum order amount to redeem points.
* Withdrawal of bonus points for inactivity.
* Referral system.
* Coupons.

== Testing ==
You can test the plugin on [**this page**](https://demo.tastewp.com/bonus-for-woo)

== SUPPORT ==
If you need support or have questions, please write to our [**support**](https://wordpress.org/support/plugin/bonus-for-woo/) or [**blog**](https://computy.ru/blog/bonus-for-woo-wordpress/#reply-title).

== Installation ==

1. Upload dir `bonus-for-woo-computy` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Options in adminpanel
2. View plugin on userpage
3. View the number of points received on the product page.
4. Calculation by points in the basket and checkout.
5. History of accrual of points in the client's account
6. User edit page.
7. E-mail notification settings page.
8. Coupon management page.
9. Statistics page

== Frequently Asked Questions ==

 
= When are points awarded? =
When the administrator marks the order as "Completed"

= Why are points not awarded immediately after the order is paid? =
This is done on purpose to exclude the case when the client has spent the earned
points, but decided to return the last product. In this case, the bonus balance goes negative.

= When are points deducted? =
When the customer confirms the order in checkout.

= Possible cashback is not displayed in the shopping cart and checkout. =
You are most likely logged in with an administrator account. Administrators are not involved
in the loyalty system.

= Does the WooCommerce plugin support High-Performance Order Storage? =
Yes, the plugin supports WooCommerce High-Performance Order Storage.


== Changelog ==


= 6.5.0 - 2024-10-28 =
* Added shortcode [bfw_get_sum_orders] - output of the user's order amount.
* Fixed erroneous cashback accrual for a product that was purchased at a discount.
* Added a setting that excludes delivery when calculating the order amount.


= 6.4.8 - 2024-10-22 =
* Fixed a bug with calculating points for excluded categories.


= 6.4.7 - 2024-10-21 =
* Fixed a bug where the order totals for users were not calculated if the "From what date should the order totals be calculated?" setting was enabled in the new High-Performance Order Storage.


= 6.4.6 - 2024-10-06 =
* Fixed broken notification when points were written off by the admin.
* Fixed offline ordering.
* Fixed a status calculation error.


= 6.4.3 - 2024-10-04 =
* Moved the method for adding, updating and deleting a status to its own class.
* Removed the ability to add the same order amount when adding statuses.
* Bringing the code to the PSR-12 standard.
* Fixed an error when calculating the order amount for old versions of woocommerce.


= 6.4.2 - 2024-09-07 =
* Added a notification for the admin in the personal account.
* Added new data to the statistics.
* Added the Tools page.
* Added the ability to subtract points from users (previously this option was only available in the PRO version).
* Optimized statistics.
* Moved user status updates to the Tools page.
* Moved the rules and conditions generator to the Tools page.
* Moved point import and export to the Tools page.


= 6.4.1 - 2024-08-27 =
* Removed notification about status change during user registration.
* Removed notification about status change during mass status update by admin.
* Fixed date display in points accrual history.


= 6.4.0 - 2024-08-22 =
* Fixed the declination of burning days in the client account.
* Typing of variables.
* Removed code duplication.
* Added comments to the code.
* Updated the bonus system rules generator.


== Upgrade Notice ==

 = 6.5.0 =
* Super update.