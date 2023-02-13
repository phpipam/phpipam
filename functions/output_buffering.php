<?php

function outputbuf_send() {
    ob_end_flush();
}

// Don't enable output buffering for cli
if (php_sapi_name()!=="cli") {

    // Buffer output and send gzcompressed at script completion.
    // Improves page loading times on low bandwith/high latency connections.
    if(!function_exists('ob_gzhandler') || !ob_start('ob_gzhandler'))
        ob_start();

    // send buffer on program completion.
    register_shutdown_function('outputbuf_send');
}