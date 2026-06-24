=== Acrossai Core Abilities ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: https://github.com/WPBoilerplate/acrossai-core-abilities
Tags: comments, spam
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `acrossai-core-abilities.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 0.0.7 =
* New ability category: Cron — 15 abilities for inspecting and managing WP-Cron.
* Read (8): list events, get by hook, next-run lookup, existence check, list/get schedules, cron status (DISABLE_WP_CRON / ALTERNATE_WP_CRON / wp-cron URL / timezone), and overdue events with grace window.
* Write (4): create event (one-off via wp_schedule_single_event() or recurring via wp_schedule_event()), update (unschedule + reschedule), run-hook-now (guarded — only fires hooks already registered in the cron array), and create custom schedule.
* Delete (3): delete one event, delete all by hook (wp_unschedule_hook), delete custom schedule.
* New utility `Cron_Helpers` — flattens _get_cron_array() into one row per event, and persists custom schedules via the "acrossai_custom_cron_schedules" option. The cron_schedules filter is hooked on every load so custom schedules survive across requests.

= 0.0.6 =
* New ability category: Fonts — Font Family + Font Face CRUD via the core Font Library REST endpoints (8 abilities).
* New ability category: Content — Posts, Pages, custom post types, Multilanguage (Polylang + WPML), and Jet Engine Options Pages (23 abilities, `acrossai-core-abilities/` + `je-` prefixes).
* New ability category: Taxonomies — taxonomy + term CRUD across any taxonomy, plus per-post term assignment (9 abilities).
* New ability category: Media — Media Library CRUD + meta access (7 abilities).
* New ability category: Comments — CRUD, moderation (approve / hold / spam), and meta (10 abilities).
* New ability category: Menus — nav menus + menu items CRUD (10 abilities).
* New ability category: Options — wp_options get/update/delete/list/search (5 abilities).
* New ability category: Settings — Permalinks, Site Title, Tagline, Site Icon (11 abilities).
* New utilities: `Multilang_Helpers` (Polylang/WPML detection), `Jet_Engine_Helpers` (Options Pages discovery), `Taxonomy_Routes` (taxonomy → REST base resolver), `Moderation` (shared comment status setter).

= 0.0.5 =
* Block category build-out: Templates, Template Parts, Global Styles, theme.json Settings, Block Style Variations, and Block Info (24 abilities).
* New utility: `File_Mods_Guard` — single chokepoint for `DISALLOW_FILE_MODS` / `DISALLOW_FILE_EDIT`, consulted by every file-writing helper.
* Per-category sub-group taxonomy adopted across the Library admin UI.

= 0.0.2 =
* Added FileManager, Themes, Users, Roles, and Sessions ability suites.
* GitHub updater for auto-updates from the release ZIP attached to GitHub releases.

= 0.0.1 =
* Initial release: Plugin, Cache, and Database ability suites.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

== A brief Markdown Example ==

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`
