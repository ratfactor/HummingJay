<?php
spl_autoload_register(function($class){
	$class_parts = explode('\\', $class);
	$class = 'src/' . end($class_parts) . '.php';

	//echo "***Trying to load class path $class\n";
	if (file_exists($class)) {
        require $class;
    }

});

