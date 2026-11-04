<?php

// print all domains or show records
if ($GET->sPage == 'page') {
    include __DIR__.'/domains-print.php';
} elseif ($GET->sPage == 'search') {
    include __DIR__.'/domains-print.php';
} elseif (isset($GET->sPage)) {
    include __DIR__.'/domain-records.php';
} else {
    include __DIR__.'/domains-print.php';
}
