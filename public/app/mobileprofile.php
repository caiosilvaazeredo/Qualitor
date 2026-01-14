<?php

    error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

    include_once '../../configcustom/log.class.php';

	$date = date('Y-m-d H:i:s');

    http_response_code(200);

    $apiLog = new apilog();

    /**************************************************************/
    /* Inicializacao de configuracoes do Qualitor                       */
    $response = $apiLog->fcApiLog(array('status' => 1, 'error_code' => NULL, 'line' => __LINE__, 'msg' => __PATH_SISTEMA__), TRUE);
    require_once __PATH_SISTEMA__ . "configLingua.php";
    header("Content-type: text/html; charset=" . $_SESSION["A_appEncoding"]);
    header('Expires: Thu, 01 Jan 1990 00:00:00 GMT');
    /**************************************************************/
    $response = $apiLog->fcApiLog(array('status' => 1, 'error_code' => NULL, 'line' => __LINE__, 'msg' => __PATH_CUSTOM__), TRUE);
    /**************************************************************/
    /* Carregando classes do Qualitor                             */
    $QLabel = $QLabel;

    importClass('dao/ComumDao', true);
    $dao = new ComumDao();

    importClass('date/Datas'.$_SESSION['A_dbtype'], true);
    $beanDate = new Datas();

    importClass('AdContato');
    $bean = new AdContatoBean();
    $vo = new AdContatoVo();

    importClass('AdContatoPerfilMobile');
    $beanM = new AdContatoPerfilMobileBean();
    $voM = new AdContatoPerfilMobileVo();

    /* Carregando classes da Custom                               */
    include_once __PATH_CUSTOM__ . "configcustom/dbcustom.php";
    $dbcustom = new dbcustom();

    require_once __PATH_CUSTOM__ . 'common/common.class.php';
    $sqlQuery = new sqlquery;

    /**************************************************************/
    /**************************************************************/
    /** Carregando variareis de sessao para execucao do WS        */
    $_SESSION['A_idwebservice'] = TRUE;
    #$_SESSION['A_cdcliente'] = $getTicketData['cdcliente'];
    #$_SESSION['A_cdcontato'] = $getTicketData['cdcontato'];
    $_SESSION['A_idauthlocal'] = 'atendente';
    $_SESSION['A_idlingua'] = 'pt-br';
    $_SESSION['A_cdlingua'] = "1";
    $_SESSION['A_ididioma'] = "pt-br";
    
    /**************************************************************/
    $query = "Select
            cdempresa
        From
            ad_empresa";
            
    $apiLog->fcApiLog(array('status' => 1, 'error_code' => NULL, 'line' => __LINE__, 'msg' => __FUNCTION__ . ':: '. PHP_EOL . $query), TRUE);
        
    $resultSet = $dao->execute($query);

    $apiLog->fcApiLog(array('status' => 1, 'error_code' => NULL, 'line' => __LINE__, 'msg' => __FUNCTION__ . ':: '. PHP_EOL . print_r($resultSet, true)), TRUE);
    
    if($resultSet->numRows > 0) {
        foreach ($resultSet->rows as $adEmpresa) {
            $WSUser = getRecordFromTable(
                'hd_parametro', 
                array('vlparametro'), 
                array(
                    'cdparametro' => 22,
                    'cdempresa' => $adEmpresa["cdempresa"]
                )
            );

            $_SESSION['A_cdempresa'] = $adEmpresa['cdempresa'];
            $_SESSION['A_cdmultiempresa'] = $adEmpresa['cdempresa'];
            $_SESSION['A_cdusuario'] = $WSUser;

            $query = "Select
                    cdcliente
                From
                    ad_cliente
                Where
                    cdempresa = " . $adEmpresa['cdempresa'];
                    
            $apiLog->fcApiLog(array('status' => 1, 'error_code' => NULL, 'line' => __LINE__, 'msg' => __FUNCTION__ . ':: '. PHP_EOL . $query), TRUE);
                
            $resultSet1 = $dao->execute($query);

            $apiLog->fcApiLog(array('status' => 1, 'error_code' => NULL, 'line' => __LINE__, 'msg' => __FUNCTION__ . ':: '. PHP_EOL . print_r($resultSet1, true)), TRUE);
            
            if($resultSet1->numRows > 0) {
                foreach ($resultSet1->rows as $adCliente) {
                    $query = "Select 
                            cdcliente,
                            cdcontato
                        From 
                            ad_contato
                        Where
                            isnull(idmobile, 'N') = 'N'
                            and idativo = 'Y'
                            and cdcliente = '" . $adCliente["cdcliente"] . "'";
                    
                    $apiLog->fcApiLog(array('status' => 1, 'error_code' => NULL, 'line' => __LINE__, 'msg' => __FUNCTION__ . ':: '. PHP_EOL . $query), TRUE);
                
                    $resultSet2 = $dao->execute($query);

                    $apiLog->fcApiLog(array('status' => 1, 'error_code' => NULL, 'line' => __LINE__, 'msg' => __FUNCTION__ . ':: '. PHP_EOL . print_r($resultSet2, true)), TRUE);

                    if($resultSet2->numRows > 0) {
                        foreach ($resultSet2->rows as $adContato) {
                            /******************* Altera Contato  *******************/
                            $adContato['idmobile'] = 'Y';
                            $adContato['idmobileurl'] = 'N';
                            $adContato['idmobilesenha'] = 'N';
                            $adContato['idmobilenrreg'] = 'Y';
                            $adContato['nrregmobile'] = 50;
                            $adContato['idmobilerestricao'] = 'N';
                            $adContato['idmobilenovoat'] = 'N';
                            $adContato['idmobilemeusat'] = 'N';
                            $adContato['idmobilemintarefas'] = 'N';
                            $adContato['idmobilependencia'] = 'N';
                            $adContato['idmobilecatalogo'] = 'N';

                            $apiLog->fcApiLog(array('status' => 0, 'error_code' => $error_code . __LINE__, 'msg' => print_r($adContato, true)), TRUE);

                            $vo = $bean->povoaVoComArray($adContato);

                            $apiLog->fcApiLog(array('status' => 0, 'error_code' => $error_code . __LINE__, 'msg' => print_r($vo, true)), TRUE);

                            $arrayIns = $bean->alteraRegistro($vo);
                            $apiLog->fcApiLog(array('status' => 0, 'error_code' => $error_code . __LINE__, 'msg' => print_r($arrayIns, true)), TRUE);

                            if (is_object($arrayIns)) {
                                $PerfilMobile = array("cdcliente" => $adContato['cdcliente'], "cdcontato" => $adContato['cdcontato'], "cdperfil" => 1);

                                $apiLog->fcApiLog(array('status' => 0, 'error_code' => $error_code . __LINE__, 'msg' => print_r($PerfilMobile, true)), TRUE);

                                $voM = $beanM->povoaVoComArray($PerfilMobile);
                                $voM->setCdCliente($adContato['cdcliente']);
                                $voM->setCdContato($adContato['cdcontato']);
                                $voM->setCdPerfil(1);

                                $arrayMolile = $beanM->insereRegistro($voM);
                                $apiLog->fcApiLog(array('status' => 0, 'error_code' => $error_code . __LINE__, 'msg' => print_r($arrayMolile, true)), TRUE);
                            }

                        }
                    }
                }
            }
        }
    } 

?>