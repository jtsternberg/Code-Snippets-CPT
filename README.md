Code Snippets CPT
=================

A WordPress plugin for managing and displaying code snippets

Adds a custom post type for managing your code snippets with taxonomies for classifying the snippets. Embed to posts or pages via a handy shortcode insert button that allows you to pick from the most recent snippets.

[Now available on wordpress.org](https://wordpress.org/plugins/code-snippets-cpt/).

## ACE Editor

To enable the ACE front-end display instead of Prettify, you can simply do this:
```
add_filters( 'snippets-cpt-ace-frontend', '__return_true' );
```

## Change Log

### 1.0.7
* Adds the [Ace Editor](https://ace.c9.io/#nav=about) in place of the old code editors.
* Allows the showing & Hiding of line numbers and makes the code block collapsible. ( **ACE Only** )
* Code themes, highlighting, multiple languages, more...
* New Filter - `snippets-cpt-ace-frontend` - Enables the ACE Front-end shortcode output.

### 1.0.6
* Add extra dialog structure to allow users to create snippets in-post, fixes [#3](https://github.com/jtsternberg/Code-Snippets-CPT/issues/3)
* Fix rogue text-domain
* Reset form after closing it

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
