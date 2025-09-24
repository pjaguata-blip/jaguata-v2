
<?php
if (!function_exists('random_bytes')) {
    function random_bytes($length) {
        return openssl_random_pseudo_bytes($length);
    }
}
