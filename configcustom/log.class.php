<?php
    ini_set("display_errors", 1 );

    class apilog {
        private $logger;
        function __construct() {
            require_once 'config.log.php';
        }

        public function fcApiLog ($arrayResponse, $infile = FALSE) {
            $response = array('response_status' => $arrayResponse);

            //Verificar no arquivo activeLog.xml se o parametro idactivelog é verdadeiro ou falso.
            $filelog = __PATH_CUSTOM__ . "configcustom" . DIRECTORY_SEPARATOR . "activeLog.xml";

            $idactiveLog = FALSE;

            if (file_exists($filelog)) {
                $activelog = json_decode(json_encode(simplexml_load_file($filelog)) , TRUE);

                $idactiveLog = filter_var(strtoupper($activelog["param"]["@attributes"]["value"]), FILTER_VALIDATE_BOOLEAN);
            }
				
            if ($infile && $idactiveLog) {
                if (__LOGGER__) {
                    $this->logger->info(print_r($response, TRUE));
                } else {
                    $date = date('Y-m-d H:i:s');
                    $log = sprintf("[%s] [%s]: %s%s", $date, '1', print_r($response, TRUE), PHP_EOL);
                    file_put_contents(__PATH_CUSTOM__ . 'log' . DIRECTORY_SEPARATOR . 'log.txt', $log, FILE_APPEND);
                }
            }

            return $response;
        }
    }
	
?>