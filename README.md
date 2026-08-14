# Divi 5 FOUC Patch

Stops the flash of unstyled content (FOUC) on Divi 5 sites at the source, without preloaders or hide-the-page hacks.

Divi 5 sites commonly paint an unstyled page for a second or two before the design arrives, and dropdown carets briefly render as the number "3". This plugin fixes the causes rather than covering them:

- **Disables Divi's Critical CSS deferral.** With Critical CSS on, Divi inlines a guessed above-the-fold subset and loads the rest via `rel="preload"` with an onload swap, which never blocks rendering. Where the guess misses (Theme Builder archive templates especially), the whole page paints bare. Patched through Divi's own `et_pb_critical_css_enabled` hook.
- **Disables Divi 5's per-page Dynamic Assets system.** All archive pages share one `et-cache/archive/` bucket, so archives can be served a stylesheet generated for a different template, with the missing styles appended by JavaScript after first paint. Divi 5 removed the Theme Options toggle for this feature and force-enables it; the `divi_frontend_assets_dynamic_assets_utils_*` hooks this plugin uses are the supported off-switch that remains.
- **Hardens the ETmodules icon font.** Divi declares it `font-display:swap`, so icons render as fallback characters (the caret's codepoint is "3" in a text font) until the font loads. The plugin preloads the font and re-declares the face with `font-display:block`.

The trade: Divi loads its full static stylesheet in the head instead of a per-page slice. First visits download more CSS, browsers cache it across the whole site, and pages render styled on the first paint.

## Installation

1. Download the zip from the [latest release](../../releases/latest) and install it via Plugins → Add New → Upload, or drop the `divi5-fouc-patch` folder into `wp-content/plugins/`.
2. Activate.
3. In Divi → Theme Options, press **Clear CSS Cache** so previously generated deferred stylesheets regenerate.
4. Browse a few of your key pages (including an archive page), **then** clear your host's page cache or CDN. The order matters: the first render after a Divi cache clear prints its CSS late in the page while writing the cached file, and a full-page cache will store that flashing copy if it gets there first.

If you previously added manual FOUC snippets (filters in `functions.php`, font preloads in the Integration tab), remove them when activating this plugin so you're patching once.

## Safe to leave installed

Every patch either runs through an Elegant Themes hook or verifies its target exists before printing anything:

- A callback attached to a hook that a future Divi removes never runs, so the filter patches go inert on their own.
- If Elegant Themes fixes the underlying defects but keeps the hooks, the site stays in the conservative full-stylesheet mode, which remains correct and flash-free.
- The font patch bails unless the active theme is Divi (or a Divi child) and `modules.woff` still exists at the path it targets.

No phoning home, no settings screen, one file you can read in a sitting.

## For developers

Switch off individual features from a theme or plugin:

```php
add_filter( 'divi5_fouc_patch_enabled', function ( $on, $feature ) {
    // Features: 'critical_css', 'dynamic_assets', 'icon_font'.
    return 'icon_font' === $feature ? false : $on;
}, 10, 2 );
```

## Background

The full investigation, including file-level references into Divi 5.10's source and the bug report filed with Elegant Themes, is written up on the [Webidextrous blog](https://www.webidextrous.com/).

Bug reports and requests: use the [Issues tab](../../issues).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
