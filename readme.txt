=== Divi 5 FOUC Patch ===
Contributors: webidextrous
Tags: divi, fouc, flash of unstyled content, performance, css
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stops the flash of unstyled content on Divi 5 sites at the source, without preloaders or hide-the-page hacks.

== Description ==

Divi 5 sites commonly paint an unstyled page for a second or two before the design arrives, and dropdown carets briefly render as the number "3". This plugin fixes the causes rather than covering them:

* Disables Divi's Critical CSS deferral (unstyled paint when the above-the-fold guess misses) through Divi's own `et_pb_critical_css_enabled` hook.
* Disables Divi 5's per-page Dynamic Assets system (archives served another template's CSS, missing styles injected by JavaScript after paint) through Divi's own `divi_frontend_assets_dynamic_assets_utils_*` hooks. Divi 5 removed the Theme Options toggle for this; these hooks are the supported off-switch that remains.
* Preloads the ETmodules icon font and re-declares it with `font-display:block`, so icons are briefly invisible instead of wrong while the font loads.

The trade: Divi loads its full static stylesheet in the head instead of a per-page slice. First visits download more CSS, browsers cache it across the whole site, and pages render styled on the first paint.

Safe to leave installed. Every patch either runs through an Elegant Themes hook (a callback on a removed hook never runs) or verifies the file it targets still exists before printing anything. If Elegant Themes fixes these defects in core, the plugin degrades to a supported conservative configuration or to nothing.

After activating, clear Divi's CSS cache (Divi > Theme Options, "Clear CSS Cache" button) so previously generated deferred stylesheets regenerate. If your host runs full-page caching or a CDN, visit your key pages once, then clear the page cache last.

If you previously added manual FOUC snippets (filters in functions.php, font preloads in Integration), remove them when activating this plugin to avoid duplicate output. Duplicates are harmless but untidy.

Per-feature opt-out for developers:

`add_filter( 'divi5_fouc_patch_enabled', function ( $on, $feature ) {
    return 'icon_font' === $feature ? false : $on; // features: critical_css, dynamic_assets, icon_font
}, 10, 2 );`

== Changelog ==

= 1.0.0 =
* Initial release: Critical CSS off, Dynamic Assets off, ETmodules preload + font-display:block.
