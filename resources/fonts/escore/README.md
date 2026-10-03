# eScore typography

The bundled static Libre Bodoni Regular and Bold fonts are from
https://github.com/googlefonts/Libre-Bodoni/tree/master/fonts/ttf.
They are distributed under the SIL Open Font License in OFL.txt.
Libre Bodoni is used as a redistributable counterpart to the reference PDF’s Bodoni MT.

The adjacent UFM metrics were generated using the already installed PHP-font-lib:

```php
$font = FontLib\Font::load($path . '.ttf');
$font->parse();
$font->saveAdobeFontMetrics($path . '.ufm');
$font->close();
```

Keep TTF and UFM files together for server rendering. The asset build copies only
TTF fonts and their license into public/fonts/escore for the browser preview.
