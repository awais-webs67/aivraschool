<?php

/*
| Front controller for when the project folder itself is the web root:
| shared hosting (files uploaded into public_html) or XAMPP/WAMP
| (cloned into htdocs, opened as http://localhost/<folder>).
| The root .htaccess sends every request here, except files in public/.
| Servers whose document root is public/ never use this file.
*/

require __DIR__.'/public/index.php';
