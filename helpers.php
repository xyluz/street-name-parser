<?php

if (!function_exists('dd')) {

    function dd($var = null,  $die = true, $pretty = true) {
        if($pretty) echo "<pre>";
        var_dump(func_get_args());
        if($pretty) echo "</pre>";
        $die ? die() : null;
    }

}