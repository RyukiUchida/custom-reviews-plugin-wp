# WordPress Custom Reviews Plugin

A lightweight and secure system for publishing and outputting user reviews for WordPress. The plugin was originally designed taking into account modern development standards: the complete abandonment of jQuery in favor of Vanilla JS, secure processing of AJAX requests and optimized database work.

## Key Features

* **Native JavaScript (Vanilla JS):** the modal window, form validation, and data submission are implemented in pure JS (Fetch API). The plugin does not pull unnecessary libraries and does not slow down the loading of the site.
* **Data Security:** strict server-side data validation and cleaning has been implemented (`sanitize_text_field', `sanitize_email', `wp_kses_post'). WordPress Nonce mechanisms are used to protect against CSRF attacks.
* **WordPress AJAX API:** asynchronous sending of reviews without reloading the page with correct processing of sessions of authorized and unauthorized users.
* **Custom taxonomy:** reviews are logically isolated into a separate category, which is automatically created when the plugin is activated (`register_activation_hook`), which eliminates unnecessary queries to the database.
* **Flexible Output (Shortcodes):** convenient shortcode `[recent_reviews]` for integrating the latest reviews block anywhere on the site (pages, widgets, basement).

## Technology stack

* **Backend:** PHP 8+, WordPress Plugin API, WordPress AJAX
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6)
* **Architecture:** Custom Post Types (partially using the built-in `post` type with meta fields), Custom Taxonomies, and Shortcuts API.

## Installation and use

1. Download the repository archive or clone it to the `/wp-content/plugins/` folder.
2. Activate the plugin **System for publishing reviews and their output** in the WordPress admin panel.
3. The "Leave a review" button will automatically appear in the header of the website.
4. To display a list of recent reviews, use the shortcode `[recent_reviews]` on any page or widget.
