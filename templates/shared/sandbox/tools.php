<?php

function dd($var)
{
    dump($var);
    die(1);
}

function dump($var)
{
    echo "\n";
    if (php_sapi_name() !== 'cli') {
        echo '<pre style="background:#222;color:#fff;padding:10px;border-radius:5px;font-size:14px;">';
    }
    $type = gettype($var);
    if (php_sapi_name() === 'cli') {
        echo "\033[33m$type\033[0m\n";
    } else {
        echo '<span style="color:#ffcc00;font-weight:bold;">' . $type . '</span>' . "\n";
    }
    if (is_array($var) || is_object($var)) {
        prettyPrint($var);
    } else {
        var_dump($var);
    }
    if (php_sapi_name() !== 'cli') {
        echo '</pre>';
    }
    echo "\n";
}

function prettyPrint($var, $depth = 0)
{
    $indent = str_repeat('    ', $depth);
    if (is_array($var)) {
        $count = count($var);
        echo "array($count) {\n";
        foreach ($var as $key => $value) {
            echo $indent . '    ';
            if (php_sapi_name() === 'cli') {
                echo "\033[32m$key\033[0m => ";
            } else {
                echo '<span style="color:#88cc88;">' . $key . '</span> => ';
            }
            if (is_array($value) || is_object($value)) {
                prettyPrint($value, $depth + 1);
            } else {
                if (is_string($value)) {
                    if (php_sapi_name() === 'cli') {
                        echo "\033[31m\"$value\"\033[0m\n";
                    } else {
                        echo '<span style="color:#ff8888;">"' . htmlspecialchars($value) . '"</span>' . "\n";
                    }
                } else {
                    var_dump($value);
                }
            }
        }
        echo $indent . "}\n";
    } elseif (is_object($var)) {
        $class = get_class($var);
        echo "object($class) {\n";
        $reflection = new ReflectionObject($var);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $name = $property->getName();
            $value = $property->getValue($var);
            echo $indent . '    ';
            if ($property->isPrivate()) {
                $prefix = 'private';
            } elseif ($property->isProtected()) {
                $prefix = 'protected';
            } else {
                $prefix = 'public';
            }
            if (php_sapi_name() === 'cli') {
                echo "$prefix \033[32m$name\033[0m => ";
            } else {
                echo "$prefix <span style=\"color:#88cc88;\">$name</span> => ";
            }
            if (is_array($value) || is_object($value)) {
                prettyPrint($value, $depth + 1);
            } else {
                if (is_string($value)) {
                    if (php_sapi_name() === 'cli') {
                        echo "\033[31m\"$value\"\033[0m\n";
                    } else {
                        echo '<span style="color:#ff8888;">"' . htmlspecialchars($value) . '"</span>' . "\n";
                    }
                } else {
                    var_dump($value);
                }
            }
        }
        echo $indent . "}\n";
    }
}
