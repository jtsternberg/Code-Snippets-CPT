Code Snippets CPT
=================
![Code Snippets CPT Banner](https://raw.githubusercontent.com/jtsternberg/Code-Snippets-CPT/master/assets/banner-772x250.png)

A WordPress plugin for managing and displaying code snippets

Adds a custom post type for managing your code snippets with taxonomies for classifying the snippets. Embed to posts or pages via a handy shortcode insert button that allows you to pick from the most recent snippets.

[Now available on wordpress.org](https://wordpress.org/plugins/code-snippets-cpt/).

## Change Log

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
