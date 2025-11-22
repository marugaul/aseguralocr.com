# AseguraloCR PHP Migration (Hogar)
1) Apunta tu DocumentRoot a `/public`.
2) Copia tus librerías en `/vendor/fpdf` y `/vendor/phpmailer` (ya existen carpetas).
3) Configura MySQL en `app/config/config.php` (usuario/clave y nombre de DB).
4) Sube todo vía cPanel/FTP. La tabla `submissions` se crea sola al primer uso.
5) SMTP ya está precargado con tus datos.

## Tailwind
El proyecto incluye `/assets/css/tailwind.css` como destino de compilación. Mientras compilas, se usa el CDN en la página para mantener el diseño. Cuando tengas el CSS compilado, **elimina** la línea del CDN en `public/hogar-comprensivo2.php`.

## Endpoint
`/enviarformularios/hogar_procesar.php` acepta JSON (fetch) y form POST tradicional.
