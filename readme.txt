=== BVK Lightning Donate ===
Contributors: tomaskrause
Tags: bitcoin, lightning, donate, lnurl, lightning-address
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.1
License: MIT
License URI: https://opensource.org/licenses/MIT

Simple Lightning Network donate widget that appears below your posts. Accept Lightning donations directly via Lightning Address.

== Description ==

BVK Lightning Donate adds a simple donation widget below each post, letting readers send sats over the Lightning Network. No middleman, no fees, payments go straight to your Lightning wallet.

**Features:**

* Widget appears automatically below posts (or via the `[bvk-lightning-donate]` shortcode)
* Master on/off toggle — the widget is disabled by default until you configure the plugin
* Configurable preset amounts in sats plus a custom amount on click
* Opens a Lightning wallet on mobile, shows a QR code on desktop
* Lightning address always visible with click-to-copy
* Optional link for users without a wallet
* Configurable primary color and background (Bitcoin orange by default)
* Choose which post types display the widget
* No dependencies, no external tracking, no Composer required
* Fully translatable via `.po`/`.mo` files

**How it works:**

The widget uses the open [Lightning Address](https://lightningaddress.com/) standard. You just need a Lightning Address in the format `you@yourwallet.com` — supported by most popular Lightning wallets and self-hosted nodes (BTCPay Server, LNbits, etc.).

When a visitor clicks "Send", their browser requests an invoice directly from your wallet server. The plugin does not proxy any payments and does not take a cut.

== Installation ==

1. Upload the `bvk-lightning-donate` folder to `/wp-content/plugins/` (or install through the WordPress admin).
2. Activate the plugin from the "Plugins" menu.
3. Go to "Settings → BVK Lightning Donate".
4. Enter your Lightning Address and tick "Show widget on site".
5. Done — the widget appears automatically below posts.

== Frequently Asked Questions ==

= What is a Lightning Address? =

A Lightning Address is a simple identifier in the format `you@yourwallet.com` that lets you receive Lightning payments. Many Lightning wallet providers offer one, or you can run your own via BTCPay Server or LNbits.

= Does the plugin take a percentage of donations? =

No. The plugin only renders the widget and passes the invoice from your wallet directly to the visitor. Payments go straight to your Lightning wallet — the plugin does not proxy or store anything.

= Does it work without JavaScript? =

No — the payment flow requires JavaScript (fetching LNURL, generating the QR code). Without JS the widget will render but the buttons will not respond.

= Does the plugin send data anywhere? =

Not from the PHP side. Only when a visitor clicks "Send", their browser connects directly to your Lightning wallet server (based on the domain in your Lightning Address) to request an invoice. See the "External Services" section for details.

= Can I display the widget only on specific posts? =

Yes. In settings, select which post types show the widget, or insert the `[bvk-lightning-donate]` shortcode in a specific post. If the shortcode is present in the content, automatic insertion is disabled for that post.

== Screenshots ==

1. Widget below a post — default layout with preset amounts.
2. QR code display after clicking "Send via Lightning" on desktop.
3. Plugin settings page in the WordPress admin.

== External Services ==

This plugin connects to an external Lightning wallet server configured in the plugin settings to generate a BOLT11 payment invoice.

**Who it talks to:** The domain is derived from the Lightning Address set by the site administrator. The plugin itself does not hardcode any specific endpoint — it works with any LNURL-pay compatible wallet provider or self-hosted node (BTCPay Server, LNbits, etc.).

**When:** Only when a visitor actively clicks the "Send via Lightning" button. No data is sent without that interaction.

**What is sent:** An HTTPS GET request with an `amount` parameter (amount in millisats). The request originates from the visitor's browser, not from the WordPress server. The wallet provider sees the visitor's IP address, User-Agent, and Referer header — standard browser behavior for any outbound request.

**Terms of service and privacy:** Depend on the chosen wallet. Site administrators are responsible for reviewing the terms of service and privacy policy of their chosen Lightning wallet provider on that provider's official website. For self-hosted nodes (BTCPay, LNbits, Phoenix, etc.), the node operator's own terms apply.

**Important:** No data is sent until the site administrator explicitly configures a Lightning Address and enables the widget. After installation, the plugin is disabled by default.

== Changelog ==

= 1.1.1 =
* Added Czech (cs_CZ) translation.
* Fixed translation loading to use absolute path for reliability.

= 1.1.0 =
* Pass post slug as LNURL-pay `comment` so recipients can attribute donations to a specific post (only when the recipient's LNURL service advertises `commentAllowed`).

= 1.0.1 =
* Inline "what is this?" link next to the Lightning Address.
* Default color scheme switched to Bitcoin logo orange.
* Bumped internal asset version to force cache refresh.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.1 =
Adds Czech translation and fixes translation loading. Recommended update.

= 1.1.0 =
New per-post attribution via LNURL comment. Backward compatible — wallets without comment support behave as before.

= 1.0.1 =
Minor UI refinements. No breaking changes — safe to upgrade.

= 1.0.0 =
Initial release — no upgrade steps required.
