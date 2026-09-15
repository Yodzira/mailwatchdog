=== Mail Watchdog ===
Contributors: yodzira
Tags: email, wp_mail, log, notification, smtp
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track every WordPress email: who sent it, did it leave, why it failed. Alert when mail breaks, resend failed messages.

== Description ==

wp_mail fails silently. Orders, password resets and even WordPress core alerts just disappear. Mail Watchdog is the black box for your site's mail:

* journal of every outgoing email: when, to whom, from which plugin, sent or failed
* plain-language failure reasons ("mail server rejected the login/password")
* alert (Telegram optional + admin email) the moment mail breaks — 3 failures in a row
* one-click resend of failed messages
* works on top of any SMTP setup — Mail Watchdog doesn't send, it watches
* automatic 30-day retention, clean uninstall

== Pro Version ==

Pro adds automation, reports and integrations on top of the free version
(one license = one site, 12 months of updates):

https://yodsira.com/buy/mail-watchdog

== Installation ==

1. Install and activate.
2. Open Mail Watchdog — the journal fills automatically.
3. Optionally add a Telegram bot for broken-mail alerts.

== Frequently Asked Questions ==

= Is this an SMTP plugin? =
No. It works on top of any mail transport and only watches.

= Does it store message bodies? =
Only for failed messages (so it can resend them). Successful mail is logged without content.

== Changelog ==

= 0.1.0 =
* First release: journal, failure classification, broken-mail alert, one-click resend, clean uninstall.
