<?php
/**
 * Plugin Name:       Divi 5 FOUC Patch
 * Plugin URI:        https://www.webidextrous.com/divi5-fouc-patch
 * Description:       Stops the flash of unstyled content (FOUC) on Divi 5 sites at the source. Disables Divi's Critical CSS deferral and per-page Dynamic Assets through Elegant Themes' own filter hooks, and hardens the ETmodules icon font so dropdown carets never render as the number "3". Designed to be left installed: every patch either runs through a Divi-provided hook (inert if Elegant Themes removes or reworks it) or checks that the file it targets still exists before printing anything.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Rob Watson, Webidextrous
 * Author URI:        https://www.webidextrous.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       divi5-fouc-patch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feature gate. Everything this plugin does can be switched off per feature
 * from a theme or another plugin:
 *
 *   add_filter( 'divi5_fouc_patch_enabled', function ( $on, $feature ) {
 *       return 'icon_font' === $feature ? false : $on;
 *   }, 10, 2 );
 *
 * Features: 'critical_css', 'dynamic_assets', 'icon_font'.
 *
 * @param string $feature Feature key.
 * @return bool
 */
function d5fp_enabled( $feature ) {
	return (bool) apply_filters( 'divi5_fouc_patch_enabled', true, $feature );
}

/**
 * True when the active theme is Divi or a Divi child theme.
 *
 * @return bool
 */
function d5fp_is_divi_theme() {
	return 'Divi' === get_template();
}

/*
 * Patch 1: disable Critical CSS.
 *
 * With Critical CSS on, Divi inlines a guessed above-the-fold subset and
 * loads the rest of the page's CSS via rel="preload" with an onload swap,
 * which does not block rendering. Wherever the guess misses (Theme Builder
 * archive templates especially), the page paints unstyled and restyles when
 * the deferred file arrives. Forcing the filter off makes Divi's stylesheet
 * a normal render-blocking resource again.
 *
 * Left-installed safety: this attaches to Elegant Themes' own hook
 * (`et_pb_critical_css_enabled`, evaluated in et_builder_is_critical_enabled()).
 * If a future Divi removes the hook, the callback never runs.
 */
add_filter( 'et_pb_critical_css_enabled', function ( $enabled ) {
	return d5fp_enabled( 'critical_css' ) ? false : $enabled;
} );

/*
 * Patch 2: disable Dynamic Assets (per-page CSS generation).
 *
 * Divi 5 removed the Theme Options "Dynamic CSS" toggle and force-enables
 * per-page CSS generation (see DynamicAssetsUtils::use_dynamic_assets()).
 * All archive pages share one et-cache "archive" bucket, so archives can be
 * served a stylesheet generated for a different Theme Builder template, and
 * the styles that bucket misses are appended by JavaScript after first
 * paint (maybe_inject_late_dynamic_assets()), which guarantees a flash.
 * With both filters false, Divi enqueues its full static stylesheet in the
 * head instead: larger on first load, cached across the site, and unable
 * to paint unstyled.
 *
 * Left-installed safety: both are Elegant Themes' own hooks, documented in
 * DynamicAssetsUtils as the supported way to disable the feature. If a
 * future Divi removes them, the callbacks never run. If Elegant Themes
 * fixes the underlying defects but keeps the hooks, the site simply stays
 * in the conservative full-stylesheet mode, which remains correct.
 */
add_filter( 'divi_frontend_assets_dynamic_assets_utils_should_generate_dynamic_assets', function ( $enabled ) {
	return d5fp_enabled( 'dynamic_assets' ) ? false : $enabled;
} );
add_filter( 'divi_frontend_assets_dynamic_assets_utils_use_dynamic_assets', function ( $enabled ) {
	return d5fp_enabled( 'dynamic_assets' ) ? false : $enabled;
} );

/*
 * Patch 3: keep icon glyphs from rendering as fallback text.
 *
 * Divi declares the ETmodules icon font with font-display:swap, so until
 * the font file arrives the browser draws the fallback character for each
 * icon's codepoint: the dropdown caret's codepoint is "3" in a text font.
 * Two-part fix: preload the font early so it usually beats first paint,
 * and re-declare the @font-face with font-display:block (a later
 * declaration with the same family, weight, and style wins the cascade;
 * identical src URLs mean no extra download), so a not-yet-loaded icon is
 * briefly invisible instead of wrong.
 *
 * Left-installed safety: both printers bail unless the active theme is
 * Divi (or a Divi child) AND the font file still exists at the path this
 * patch targets. If a future Divi relocates or retires ETmodules, nothing
 * is printed.
 */

/**
 * Path and URL for the ETmodules woff, or null when unavailable.
 *
 * @return array{path:string,url:string}|null
 */
function d5fp_icon_font() {
	if ( ! d5fp_is_divi_theme() ) {
		return null;
	}

	$rel  = '/core/admin/fonts/modules/all/modules.woff';
	$path = get_template_directory() . $rel;

	if ( ! file_exists( $path ) ) {
		return null;
	}

	return array(
		'path' => $path,
		'url'  => get_template_directory_uri() . $rel,
	);
}

// Early so the font request starts before the stylesheets finish parsing.
add_action( 'wp_head', function () {
	if ( ! d5fp_enabled( 'icon_font' ) ) {
		return;
	}

	$font = d5fp_icon_font();
	if ( ! $font ) {
		return;
	}

	// Font preloads require crossorigin even for same-origin requests.
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff" crossorigin>' . "\n",
		esc_url( $font['url'] )
	);
}, 2 );

// Late so this @font-face lands after every stylesheet Divi prints in the head.
add_action( 'wp_head', function () {
	if ( ! d5fp_enabled( 'icon_font' ) ) {
		return;
	}

	$font = d5fp_icon_font();
	if ( ! $font ) {
		return;
	}

	$base = preg_replace( '/\.woff$/', '', $font['url'] );
	printf(
		'<style id="d5fp-etmodules-font-display">@font-face{font-family:ETmodules;font-display:block;src:url(%1$s.woff) format("woff"),url(%1$s.ttf) format("truetype");font-weight:400;font-style:normal}</style>' . "\n",
		esc_url( $base )
	);
}, 9999 );
