<?php

    /**************************************************************/
    // Define o caminho do arquivo que será usado no XML
    $arrayPath = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));

    $i = count($arrayPath);
    $pathSystem = "";
    For ($i = 0; $i < count($arrayPath) - 3; $i++) {
        $pathSystem .= $arrayPath[$i] . DIRECTORY_SEPARATOR;
    }
    
    define('__PATH_SISTEMA__', $pathSystem);

    $i = count($arrayPath);
    $pathCustom = "";
    For ($i = 0; $i < count($arrayPath) - 1; $i++) {
        $pathCustom .= $arrayPath[$i] . DIRECTORY_SEPARATOR;
    }
    
    define('__PATH_CUSTOM__', $pathCustom);

    $pathLog = __PATH_CUSTOM__ . 'log4php' . DIRECTORY_SEPARATOR . 'log';
    $pathCurrent = $arrayPath[max(array_keys($arrayPath))];

    /**************************************************************/

    if ($_REQUEST["print"] == "1") {
        echo '<pre>';
        echo "DIR: " . __DIR__ . PHP_EOL;
        echo "PATH_SISTEMA: " . __PATH_SISTEMA__ . PHP_EOL;
        echo "PATH_CUSTOM: " . __PATH_CUSTOM__ . PHP_EOL;
        echo "Log: " . $pathLog . PHP_EOL;
        echo "Current: " . $pathCurrent . PHP_EOL;
    } else {
        require_once __PATH_CUSTOM__ . 'configcustom' . DIRECTORY_SEPARATOR . 'dbcustom.php';
        include_once __PATH_CUSTOM__ . 'common' . DIRECTORY_SEPARATOR . 'common.class.php';
    }

?>