<?php
    //ini_set("display_errors", 1 );
    //ini_set("display_startup_errors", 1 );
    //ini_set("log_errors", 1 );

    ini_set('max_execution_time', '0');
    ini_set('memory_limit', '-1');
   
    class dbcustom {
        private $conn, $dberror, $_path;
        
        function __construct() {
            $this->_path = __PATH_SISTEMA__;
        }
        
        function usr_getpass($encript_pass){
            if (!function_exists('array_merge_keys')) {
                include_once $this->_path . 'config/basic/arr_basic.php';
            }
            include_once $this->_path . "framework/qcrypt/QCrypt.php";
            $QCrypt = new QCrypt();
            $arrayCrypt = $QCrypt->decryptDados($encript_pass, true, 5);
            
            return $arrayCrypt[0];
        }
        
        public function usrDBConnect () {
            require_once $this->_path . "framework/conexao/Config.php";

            $config = new Config();
            $decript_pass = $this->usr_getpass($config->getConfigSenha());

            $nmHost = $config->getConfigHost();
            $nmUsuario = $config->getConfigUsuario();
            $NmBanco = $config->getConfigNmBanco();
            $dsSenha = $decript_pass;

            #$connectionInfo = array( "Database"=>$NmBanco, "UID"=>$nmUsuario, "PWD"=>$dsSenha, "CharacterSet" => "UTF-8");
            $connectionInfo = array (
                "Database" => $NmBanco, 
                "ReturnDatesAsStrings" => true, 
                "CharacterSet" => "UTF-8", 
                "PWD" => $dsSenha, 
                "UID" => $nmUsuario 
            );

            $this->usr_conn = sqlsrv_connect( $nmHost, $connectionInfo) or die ( 'Erro ao tentar conectar o DB' );

            if($this->usr_conn) {
                $this->dberror = FALSE;
            } else {
                $this->dberror = TRUE;
            }
            
            return ($this->dberror === TRUE) ? $this->dberror : $this->usr_conn;
        }

        public function dbexec($sqlcommand, $params = array()) {
            $conn = $this->usrDBConnect();
            if ($conn) {
                $stmt = sqlsrv_prepare($conn, $sqlcommand, $params);
                if (!$stmt) {
                    return array("cl_status" => "0", "msg" => json_encode(sqlsrv_errors()), "command" => $sqlcommand);
                } else {
                    if (sqlsrv_execute($stmt) === FALSE) {
                        return array("cl_status" => "0", "msg" => json_encode(sqlsrv_errors()), "command" => $sqlcommand);
                    }
                    return array("cl_status" => "1", "msg" => 'Executado com sucesso!', "command" => $sqlcommand);
                }
            }
            return array("cl_status" => "0", "msg" => json_encode(sqlsrv_errors()), "command" => $sqlcommand);
        }
        
        public function dbquery($sqlcommand, $params = array()) {
            #Executa consulta direto no Banco de Dados
            $conn = $this->usrDBConnect();
            if ($conn) {
                $result = sqlsrv_query($conn, $sqlcommand, $params);
                if ($result === FALSE) {
                    return array("cl_status" => "0", "msg" => json_encode(sqlsrv_errors()), "command" => $sqlcommand);
                }
                
                $dataitem = array();
                $i = 0;
                while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                    $dataitem[$i] = $row;
                    $i++;
                }
    
                return array("cl_status" => "1", "msg" => 'Executado com sucesso!', "command" => $sqlcommand, "dataitem" => $dataitem, "numRows" => $i);
            }
            return array("cl_status" => "0", "msg" => "Erro de conexao", "command" => $sqlcommand);
        }
    }
