<?php
spl_autoload_register(function ($className) {
    $classPath = str_replace(array('_', '\\'), '/', $className) . '.php';

	  if (@fopen($classPath, 'r', true)) {
        require $classPath;
    }

});

