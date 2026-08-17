=== Divi 5 FOUC Patch ===
Contributors: webidextrous
Tags: divi, fouc, flash of unstyled content, performance, css
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stops the flash of unstyled content on Divi 5 sites at the source, without preloaders or hide-the-page hacks.

== Description ==

Divi 5 sites commonly paint an unstyled page for a second or two before the design arrives, and dropdown carets briefly render as the number "3". This plugin fixes the causes rather than covering them:

* Disables Divi's Critical CSS deferral (unstyled paint when the above-the-fold guess misses) through Divi's own `et_pb_critical_css_enabled` hook.
* Disables Divi 5's per-page Dynamic Assets CSS (archives served another template's CSS, missing styles injected by JavaScript after paint) through Divi's own `divi_frontend_assets_dynamic_assets_utils_use_dynamic_assets` hook. Divi 5 removed the Theme Options toggle for this; the hook is the supported off-switch that remains. Module JavaScript is untouched: Divi's script pipeline keeps loading each module's JS exactly as it detects it.
* Preloads the ETmodules icon font and re-declares it with `font-display:block`, so icons are briefly invisible instead of wrong while the font loads.
* Relocates the CSS Divi prints late during its first render after a cache clear into the head, so even that generation pass renders styled and full-page caches never capture a flashing copy.

The trade: Divi loads its full static stylesheet in the head instead of a per-page slice. First visits download more CSS, browsers cache it across the whole site, and pages render styled on the first paint.

Safe to leave installed. Every patch either runs through an Elegant Themes hook (a callback on a removed hook never runs) or verifies the file it targets still exists before printing anything. If Elegant Themes fixes these defects in core, the plugin degrades to a supported conservative configuration or to nothing.

After activating, clear Divi's CSS cache (Divi > Theme Options, "Clear CSS Cache" button) so previously generated deferred stylesheets regenerate, then clear your host's page cache. As of 1.1.0 the first view of each page renders styled too, so pre-visiting pages to warm Divi's cache is optional rather than required.

If you previously added manual FOUC snippets (filters in functions.php, font preloads in Integration), remove them when activating this plugin to avoid duplicate output. Duplicates are harmless but untidy.

Per-feature opt-out for developers:

`add_filter( 'divi5_fouc_patch_enabled', function ( $on, $feature ) {
    return 'icon_font' === $feature ? false : $on; // features: critical_css, dynamic_assets, icon_font, first_pass_css
}, 10, 2 );`

== Changelog ==

= 1.2.0 =
* Fix: restore Divi's module JavaScript. Versions 1.0.0-1.1.0 also filtered `divi_frontend_assets_dynamic_assets_utils_should_generate_dynamic_assets`, which in Divi 5.10 gates the module script enqueue pipeline as well as CSS generation — so every conditional module script (menu search icon and mobile toggle, blog pagination, sliders, toggles, tabs, audio, video overlays) was silently suppressed. The plugin now filters only `..._use_dynamic_assets`, which controls the CSS side alone: the full static stylesheet still loads in the head, per-page CSS generation and late injection stay off, and module JavaScript loads normally. If you added the `dynamic_assets` opt-out snippet as a workaround, remove it after updating.

= 1.1.0 =
* New: first-render fix. Divi's generation pass (the first view of a page after a CSS cache clear) prints its unified stylesheet near the end of the body, which flashed unstyled and could be captured by full-page caches. The plugin now buffers the page and moves those blocks into the head, so warming pages is optional rather than required. Feature key: `first_pass_css`.

= 1.0.0 =
* Initial release: Critical CSS off, Dynamic Assets off, ETmodules preload + font-display:block.
