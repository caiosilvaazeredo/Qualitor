<?php
	require_once 'configPaths.php';
	
	#Cria o diretorio de Log
    if(!is_dir($pathLog)) {
        mkdir($pathLog);
    }
	
	$filePath = $pathLog  . DIRECTORY_SEPARATOR . 'log.txt';
    $fileName = __PATH_CUSTOM__ . $pathCurrent . DIRECTORY_SEPARATOR . 'config.log.xml';

    if (!file_exists($fileName)){
        // Cria uma instância de DOMDocument
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Elemento raiz
        $configuration = $dom->createElement('configuration');
        $configuration->setAttribute('xmlns', 'http://logging.apache.org/log4php/');
        $dom->appendChild($configuration);

        // Elemento <appender>
        $appender = $dom->createElement('appender');
        $appender->setAttribute('name', 'config.log');
        $appender->setAttribute('class', 'LoggerAppenderRollingFile');
        $configuration->appendChild($appender);

        // <param name="file" ...>
        $paramFile = $dom->createElement('param');
        $paramFile->setAttribute('name', 'file');
        $paramFile->setAttribute('value', $filePath);
        $appender->appendChild($paramFile);

        // <param name="maxFileSize" ...>
        $paramMaxFileSize = $dom->createElement('param');
        $paramMaxFileSize->setAttribute('name', 'maxFileSize');
        $paramMaxFileSize->setAttribute('value', '10MB');
        $appender->appendChild($paramMaxFileSize);

        // <param name="maxBackupIndex" ...>
        $paramMaxBackupIndex = $dom->createElement('param');
        $paramMaxBackupIndex->setAttribute('name', 'maxBackupIndex');
        $paramMaxBackupIndex->setAttribute('value', '20');
        $appender->appendChild($paramMaxBackupIndex);

        // <param name="compress" ...>
        $paramCompress = $dom->createElement('param');
        $paramCompress->setAttribute('name', 'compress');
        $paramCompress->setAttribute('value', 'false');
        $appender->appendChild($paramCompress);

        // <layout>
        $layout = $dom->createElement('layout');
        $layout->setAttribute('class', 'LoggerLayoutPattern');
        $appender->appendChild($layout);

        // <param name="conversionPattern" ...>
        $paramConversionPattern = $dom->createElement('param');
        $paramConversionPattern->setAttribute('name', 'conversionPattern');
        $paramConversionPattern->setAttribute('value', '%date{Y-m-d H:i:s,u} %p %C.%M:%L - %m%n');
        $layout->appendChild($paramConversionPattern);

        // <root>
        $root = $dom->createElement('root');
        $configuration->appendChild($root);

        // <level>
        $level = $dom->createElement('level');
        $level->setAttribute('value', 'DEBUG');
        $root->appendChild($level);

        // <appender-ref>
        $appenderRef = $dom->createElement('appender-ref');
        $appenderRef->setAttribute('ref', 'config.log');
        $root->appendChild($appenderRef);

        // Salva o XML em um arquivo
        $dom->save($fileName);
    } 
    
    $fileLogCustom = __PATH_CUSTOM__ . 'log4php' . DIRECTORY_SEPARATOR . 'Logger.php';
	
    if(file_exists($fileLogCustom )) {
        require_once $fileLogCustom;

        $XMLLogConfig = $fileName;

        if(file_exists($XMLLogConfig) ){
            //log4php
            Logger::configure($XMLLogConfig);	
            
            //Bloco do XML das configuracoes
            $this->logger = Logger::getLogger('config.log');

            $this->logger->info(print_r(array('status' => 0, 'error_code' => $this->error_code . __LINE__, 'msg' => "Config_Log carregado com sucesso."), TRUE));
            define('__LOGGER__', TRUE);
        } else {
            #apilog::fcApiLog(array('status' => 0, 'error_code' => $this->error_code . __LINE__, 'msg' => "Não foi possível localizar as configurações do log."), TRUE);
            define('__LOGGER__', FALSE);
        }
    } else {
        #apilog::fcApiLog(array('status' => 0, 'error_code' => $this->error_code . __LINE__, 'msg' => "Não foi possível encontrar o plugins log4php."), TRUE);
        define('__LOGGER__', FALSE);
    }

?>
