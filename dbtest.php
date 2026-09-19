<?php
$h = 'mariadb-kowhvrad.internal';
echo 'resolves to: ' . var_export(gethostbyname($h), true) . '<br>';
echo 'env DB_HOST: ' . var_export(getenv('DB_HOST'), true);
