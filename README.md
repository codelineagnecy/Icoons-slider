# Icons Slider

WordPress plugin to display a clean horizontal icon/logo slider with title and description support.

## Features

- Custom post type for slides: `icon_slide`
- Slide categories via taxonomy: `icon_slide_category`
- Shortcode output for pages and posts
- Optional Elementor widget integration
- Manual slide ordering
- Per-logo size control per category
- Admin image normalization tools

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Elementor (optional)

## Installation

1. Copy the plugin folder to `wp-content/plugins/icons-slider`.
2. Activate **Icons Slider** in WordPress admin.
3. Create slides via **Icons Slider** in the admin menu.
4. Add the shortcode to a page.

## Quick Usage

Show all slides:

```text
[icons_slider]
```

Show only one category:

```text
[icons_slider category="your-category-slug"]
```

## Content Setup

For each slide, set:

- Title
- Featured image (logo/icon)
- Description
- Order value
- Category (optional)

## Elementor

If Elementor is active, the widget is registered automatically and available in the editor.

## Image Tools

Go to **Icons Slider > Edit Images** to:

- Set logo sizes per category
- Apply size changes in bulk
- Normalize generated image dimensions

## Project Structure

- `wp-content/plugins/icons-slider/icons-slider.php` - main plugin bootstrap
- `wp-content/plugins/icons-slider/elementor-widget.php` - Elementor widget
- `wp-content/plugins/icons-slider/css/slider.css` - frontend styles
- `wp-content/plugins/icons-slider/js/slider.js` - frontend script

## Changelog

### 1.0

- Initial release
- CPT + taxonomy
- Shortcode rendering
- Elementor integration
- Admin image tools

## License

Add your preferred license here (for example MIT or GPL-2.0-or-later).
