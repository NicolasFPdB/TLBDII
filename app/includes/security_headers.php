<?php
/* Inclua no topo de layout.php e login.php, antes de qualquer saída. */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');                 // anti-clickjacking: o painel não deve ser embutido em iframe
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');               // páginas do painel não devem ficar em cache de navegador/proxy
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    header('Strict-Transport-Security: max-age=31536000');
