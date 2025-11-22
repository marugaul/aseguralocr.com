<?php
chdir('/home/asegural/public_html/composer');
exec('composer require setasign/fpdi-fpdf 2>&1', $output);
echo '<pre>' . implode("\n", $output) . '</pre>';