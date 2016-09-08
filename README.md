Code Snippets CPT - 2.0.8
=================

A WordPress plugin for elegantly managing and displaying code snippets

Adds a custom post type for managing your code snippets with taxonomies for classifying the snippets. Embed snippets with syntax highlighting to posts or pages via a handy shortcode insert button that allows you to pick from the most recent snippets. Syntax highlighting provided by the [Prettify javascript library](https://github.com/google/code-prettify) and the [Ace Editor](https://ace.c9.io/).

Features:

* Host your own snippet library.
* Button for easy-copying of snippet (disable with: `add_filter( 'dsgnwrks_snippet_do_click_to_copy', '__return_false' )`).
* Button to enable full-screen snippet view (disable with: `add_filter( 'dsgnwrks_snippet_enable_full_screen_view', '__return_false' )`).
* Two frontend render engines, prettify (with 2 themes) or Ace (with 32 themes).
* WordPress editor shortcode button for embedding snippets in your content, and creating/editing those snippets on the fly.
* Live (tinymce) previews of the snippets in your content editor.
* Programming language picker (for syntax).
* Snippet tags and categories.

[Available on wordpress.org](https://wordpress.org/plugins/code-snippets-cpt/).

## Change Log

### 2.0.8
* Fix ACE frontend to honor the 'Display Line Numbers' setting. Fixes [#29](https://github.com/jtsternberg/Code-Snippets-CPT/issues/29).

### 2.0.7
* Fix "Uncaught Error: Call to undefined function post_categories_meta_box()" occurring when not on post-pages. Fixes [#28](https://github.com/jtsternberg/Code-Snippets-CPT/issues/28).

### 2.0.6
* Update snippet-copy URL so that it doesn't 404 when nonce is expired, and also `noindex,nofollow` the snippet-copy pages when the nonce has expired.

### 2.0.5
* Enable native copy functionality available in newer browsers. Props [ramiabraham](https://github.com/ramiabraham), [#27](https://github.com/jtsternberg/Code-Snippets-CPT/pull/27).
* Clean up styles a bit for full-screen view.
* Clean up Ace front-end view.

### 2.0.4
* Fix bug causing the shortcode button not to insert the snippet when in visual mode.

### 2.0.3
* The front-end script needs to load in the footer so that `wp_localize_script()` works as expected.
* Better styling for the full-width view and the buttons in the full-width view

### 2.0.2
* Better Ace editor support for inline php snippets (i.e. no opening `<?php` tag).
* Minify css files.

### 2.0.1
* Use Ace editor for the snippet add/edit shortcode modal.

### 2.0.0
* Button for opening modal for easy-copying of snippet.
* Button to enable full-screen snippet view.
* (When using Ace frontend) Button to toggle line-numbers.
* (When using Ace frontend) Button to collapse/minify the snippet.
* (When logged-in) Button to edit Snippet.
* View individual snippet pages, and link to full-screen snippets.
* Live (tinymce) previews of the snippets in your content editor, and edit them in place.
* Option to choose the front-end display theme and render engine (prettify or Ace).
* Ace editor on the snippet-edit page, and option to use the Ace render engine on the front-end. Props [JayWood](https://github.com/JayWood) ([#22](https://github.com/jtsternberg/Code-Snippets-CPT/pull/22)).
* Add new snippets on the fly via the shortcode button (vs having to leave your post and to create them). Props [JayWood](https://github.com/JayWood) ([#22](https://github.com/jtsternberg/Code-Snippets-CPT/pull/22)).

### 1.0.5
* Add C# as available language.

### 1.0.4
* BUG FIX: Remove 'html_entity_decode' around snippet output, as it will cause the page display to break under certain circumstances.

### 1.0.3
* Replace shortcode button's usage of ids with slugs because ids can change during a migration.
* Added filter, 'dsgnwrks_snippet_display'.
* Better handling of WordPress-converted html entities.
* By default, convert tabs to spaces for better readability. Can be disabled with: `remove_filter( 'dsgnwrks_snippet_content', 'dsgnwrks_snippet_content_replace_tabs' );`
* Added title attribute to `pre` element to display title of snippet on hover.

### 1.0.2
* Add more languages
* Add lang parameter to shortcode attributes.
* Use selected snippet language to set the shortcode lang parameter.
* Allow shortcode to specify line number to start with

### 1.0.1
* WP editor buttons for inserting snippet shortcodes

### 1.0.0
* First Release
