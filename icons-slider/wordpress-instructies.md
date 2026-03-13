# WordPress Installatie Instructies

## Methode 1: Custom HTML Block (Eenvoudigste)

1. Open je WordPress pagina in de editor
2. Voeg een "Custom HTML" block toe
3. Kopieer de volledige inhoud van `wordpress-slider.html` (alles tussen `<style>` en `</script>`)
4. Plak het in het Custom HTML block
5. Publiceer de pagina

## Methode 2: Theme Files (Voor gevorderden)

### Stap 1: Maak de bestanden aan

**In je theme map maak aan: `/wp-content/themes/jouw-theme/tool-slider/`**

#### tool-slider.css
```css
/* Kopieer alle CSS tussen de <style> tags uit wordpress-slider.html */
```

#### tool-slider.js
```javascript
/* Kopieer alle JavaScript tussen de <script> tags uit wordpress-slider.html */
```

#### tool-slider.html
```html
<!-- Kopieer alleen de <div class="tool-slider-container"> sectie -->
```

### Stap 2: Voeg toe aan functions.php

```php
function enqueue_tool_slider_scripts() {
    // Slick Carousel CSS
    wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
    wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
    
    // Custom CSS
    wp_enqueue_style('tool-slider-css', get_template_directory_uri() . '/tool-slider/tool-slider.css');
    
    // jQuery (WordPress heeft al jQuery)
    // Slick Carousel JS
    wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), null, true);
    
    // Lucide Icons
    wp_enqueue_script('lucide-js', 'https://unpkg.com/lucide@latest', array(), null, true);
    
    // Custom JS
    wp_enqueue_script('tool-slider-js', get_template_directory_uri() . '/tool-slider/tool-slider.js', array('jquery', 'slick-js'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_tool_slider_scripts');
```

### Stap 3: Maak een Shortcode (Optioneel)

Voeg toe aan functions.php:

```php
function tool_slider_shortcode() {
    ob_start();
    include get_template_directory() . '/tool-slider/tool-slider.html';
    return ob_get_clean();
}
add_shortcode('tool_slider', 'tool_slider_shortcode');
```

Gebruik dan `[tool_slider]` in je pagina.

## Methode 3: Elementor/Divi/Andere Page Builder

1. Voeg een HTML widget/module toe
2. Plak de volledige HTML uit `wordpress-slider.html`
3. Sla op en bekijk

## Tips

- Test eerst de HTML standalone in een browser om te zien of alles werkt
- Als iconen niet laden, controleer of Lucide correct is geladen
- Voor betere performance kun je de CDN links vervangen door lokale bestanden
- Pas kleuren en teksten aan in de HTML naar jouw wensen

## Aanpassingen maken

### Andere iconen gebruiken
Ga naar https://lucide.dev/icons en vervang `data-lucide="icon-name"` met het gewenste icoon.

### Kleuren aanpassen
Verander de `style="color: #hexcode;"` in de HTML.

### Snelheid aanpassen
Verander `autoplaySpeed: 3000` (in milliseconden) in de JavaScript.

### Aantal zichtbare items
Pas `slidesToShow: 8` aan in de JavaScript.
