<?php
    ini_set("display_errors", 1 );
    require_once __DIR__ . '/../configcustom/log.class.php';
    
    class request {
        public $response;
        public $error_code;
        //log4php
        public $logger;
        private $apiLog;
        private $RelativePath;

        function __construct() {
            
            $this->apiLog = new apilog;
            $this->response = $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => null, 'msg' => 'Class ' . __CLASS__ . ' carregado com sucesso.'), TRUE);

            //ID do erro
            $arrayFile = explode(DIRECTORY_SEPARATOR,  __FILE__);
            $this->error_code = strtoupper(preg_replace('/\.|php/', '', end($arrayFile))) . "::";

            $this->RelativePath = implode("/", array($arrayFile[array_key_last($arrayFile) - 2], $arrayFile[array_key_last($arrayFile) - 1]));

	        /**************************************************************/
			/* Inicializacao de configuracoes do Qualitor                       */
			require_once("../../../configLingua.php");
			header("Content-type: text/html; charset=" . $_SESSION["A_appEncoding"]);
			header('Expires: Thu, 01 Jan 1990 00:00:00 GMT');
			/**************************************************************/

            /**************************************************************/
            /* Carregando classes do Qualitor                             */
			$this->QLabel = $QLabel;

			importClass('dao/ComumDao', true);
			$this->dao = new ComumDao();

			importClass('date/Datas'.$_SESSION['A_dbtype'], true);
			$this->beanDate = new Datas();

			importClass("HdAnexoChamado");
			$this->beanAtt = new HdAnexoChamadoBean();
			$this->voAtt = new HdAnexoChamadoVo();

            importClass("HdChamadoInformacaoAdicional");
			$this->beanInf = new HdChamadoInformacaoAdicionalBean();
			$this->voInf = new HdChamadoInformacaoAdicionalVo();

            importClass("HdAcompanhamento");
			$this->beanHist = new HdAcompanhamentoBean();
			$this->voHist = new HdAcompanhamentoVo();

            importClass("HdChamadoContato");
			$this->beanCtt = new HdChamadoContatoBean();
			$this->voCtt = new HdChamadoContatoVo();

			//webservice WSTicket
			define('WS_PATH', __PATH_SISTEMA__.'/ws');
			require_once __PATH_SISTEMA__.'/ws/config/error/WSTicket.define.php';
			require_once __PATH_SISTEMA__.'/ws/services/Ticket/WSTicket.class.php';
			$this->WSTicket = new WSTicket();
            /**************************************************************/

			/**************************************************************/
			include_once __PATH_CUSTOM__ . "configcustom/dbcustom.php";
			$this->dbcustom = new dbcustom();

            require_once __PATH_CUSTOM__ . 'common/common.class.php';
            $this->sqlQuery = new sqlquery;

			/**************************************************************/
        }

        public function requestprocess($arrayData) {
            $this->error_code .= __FUNCTION__ . "::";

			$event = $arrayData['Event'] ?? "After";

            if ($event == "After") {
				//Recupera informacoes do chamado
				$getTicketData = getRecordFromTable(
					'hd_chamado', 
					array('cdestrutura', 'cdsubsituacao', 'cdcliente', 'cdcontato', 'cdlocalidade', 'cdusuario', 'cdempresa', 'dschamado'), 
					array(
						'cdchamado' => $arrayData["cdchamado"]
					)
				);
				if (!isset($getTicketData["cdempresa"])) {
					$this->response = $this->apiLog->fcApiLog(array('status' => 0, 'error_code' => $this->error_code . __LINE__, 'msg' => 'Erro ao buscar dados do chamado'), TRUE);
					return $this->response;
				} else {
					$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($getTicketData, TRUE)), TRUE);
				}
				
				/**************************************************************/
				/** Carregando variareis de sessao para execucao do WS        */
				$_SESSION['A_idwebservice'] = TRUE;
				$_SESSION['A_cdusuario'] = $getTicketData["cdusuario"];
				$_SESSION['A_cdcliente'] = $getTicketData['cdcliente'];
				$_SESSION['A_cdcontato'] = $getTicketData['cdcontato'];
				$_SESSION['A_idauthlocal'] = 'atendente';
				$_SESSION['A_idlingua'] = 'pt-br';
				$_SESSION['A_cdlingua'] = "1";
				$_SESSION['A_ididioma'] = "pt-br";
				$_SESSION['A_cdempresa'] = $getTicketData['cdempresa'];
				$_SESSION['A_cdmultiempresa'] = $getTicketData['cdempresa'];
				/**************************************************************/

				//Recupera informacoes do Contato
				$getContactData = getRecordFromTable(
					'ad_contato', 
					array('nmcontato', 'dsemail', 'nrtelefone', 'nrpaistelefone', 'nrareatelefone'), 
					array(
						'cdcliente' => $getTicketData["cdcliente"],
						'cdcontato' => $getTicketData["cdcontato"]
					)
				);
				if (!isset($getContactData["nmcontato"])) {
					$idaction = ' (#cdchamado#cdcliente#cdcontato' . '#' . $arrayData["cdchamado"] . '#' . $getTicketData["cdcliente"] . '#' . $getTicketData["cdcontato"] . ').';
					$this->response = $this->apiLog->fcApiLog(array('status' => 0, 'error_code' => $this->error_code . __LINE__, 'msg' => 'Erro ao buscar dados do chamado' . $idaction), TRUE);
					return $this->response;
				} else {
					$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($getTicketData, TRUE)), TRUE);
				}
			}

            switch ($arrayData['ClassSource']) {
				//Se origem da requisicao for a classe GTWNextSubStatus (gateway de avanco de etapa)
                case "GTWNextSubStatus":
                    $getTicketData["cdchamado"] = $arrayData["cdchamado"];
					//Recupera o idprocesso
					$getIdProcess = getRecordFromTable(
						'hd_estruturasubsituacao', 
						array('idprocesso'), 
						array(
							'cdestrutura' => $getTicketData["cdestrutura"]
						)
					);
					if (!isset($getIdProcess["idprocesso"])) {
						$idaction = ' (#cdchamado#cdestrutura' . '#' . $arrayData["cdchamado"] . '#' . $getTicketData["cdestrutura"] . ').';
						$this->response = $this->apiLog->fcApiLog(array('status' => 0, 'error_code' => $this->error_code . __LINE__, 'msg' => 'Erro ao buscar Processo' . $idaction), TRUE);
						return $this->response;
					} else {
						$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($getIdProcess, TRUE)), TRUE);
					}

					//Recupera identificador da etapa
					$getNmIdentificador = getRecordFromTable(
						'hd_estruturasubsituacaoitem', 
						array('nmidentificador'), 
						array(
							'cdestrutura' => $getTicketData["cdestrutura"], 
							'nrsequencia' => $getTicketData['cdsubsituacao']
						)
					);
					if (!isset($getNmIdentificador["nmidentificador"])) {
						$idaction = ' (#cdchamado#cdestrutura#cdsubsituacao' . '#' . $arrayData["cdchamado"] . '#' . $getTicketData["cdestrutura"] . '#' . $getTicketData['cdsubsituacao'] . ').';
						$this->response = $this->apiLog->fcApiLog(array('status' => 0, 'error_code' => $this->error_code . __LINE__, 'msg' => 'Erro ao buscar identificador da etapa' . $idaction), TRUE);
						return $this->response;
					} else {
						$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($getNmIdentificador, TRUE)), TRUE);
					}

					// Capturar o conteúdo entre os colchetes usando expressão regular
					if (preg_match('/\[(.*?)\]/', strtoupper($getNmIdentificador["nmidentificador"]), $idAPIProcesso)) {
						// Remover os colchetes e o conteúdo dentro deles
						$IdentificadorQualitor = preg_replace('/\[[^\]]*\]/', '', strtoupper($getNmIdentificador["nmidentificador"]));
					} else {
						$IdentificadorQualitor = strtoupper($getNmIdentificador["nmidentificador"]);
					}
					break;
				case "GTWNewTicket":
                case "GTWAddTicket":
                    switch ($arrayData['Event']) {
                        case "Before":
                            $txt = "";
							if ($arrayData["cdcategoria"] == "1505") {
								$GetQuery = file_get_contents(__PATH_CUSTOM__ . '/SQL/ContatoMatricula.sql');

								if (preg_match('/\{(vlinformacaoadicional\d+)\}/', $GetQuery, $match)) {

									// Nome da variável encontrada dentro do SQL (ex: vlinformacaoadicional3)
									$var = $match[1];

									// Valor correspondente no array
									$valor = $arrayData[$var] ?? '';

									// Substitui no SQL
									$query = str_replace("{{$var}}", $valor, $GetQuery);

									$resultSet = $this->dao->execute($query);

									$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($resultSet, TRUE)), TRUE);

									if ($resultSet->numRows == 0) {
										$txt = "Nenhum registro encontrado";
									}
								}
							}

                            $arrayReturn['txt'] = $txt;
			                $arrayReturn['flow'] = 'ok';

                            break;
                        case "After":
							if ($arrayData["cdcategoria"] == "1505") {
								importClass("HdChamadoInformacaoAdicional");
								$beanInf = new HdChamadoInformacaoAdicionalBean();
								$voInf = new HdChamadoInformacaoAdicionalVo();

								$GetQuery = file_get_contents(__PATH_CUSTOM__ . '/SQL/ContatoMatricula.sql');

								if (preg_match('/\{(vlinformacaoadicional\d+)\}/', $GetQuery, $match)) {

									// Nome da variável encontrada dentro do SQL (ex: vlinformacaoadicional3)
									$var = $match[1];

									// Valor correspondente no array
									$valor = $arrayData[$var] ?? '';

									// Substitui no SQL
									$query = str_replace("{{$var}}", $valor, $GetQuery);

									$resultSet = $this->dao->execute($query);
									$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($resultSet, TRUE)), TRUE);

									$arrayConfig  = parse_ini_file(__PATH_CUSTOM__ . '/configcustom/customform.ini');
									$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($arrayConfig, TRUE)), TRUE);

									if ($resultSet->numRows > 0) {
										foreach ($resultSet->rows as $dataitem) {
											foreach ($arrayConfig as $infAdd => $fieldValue) {
												$arrayTicketInf[str_replace('vlinformacaoadicional', '', $infAdd)] = $dataitem[$fieldValue] ?? '';
											}
										}
									}
									$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($arrayTicketInf, TRUE)), TRUE);

									$voInf->setCdChamado($arrayData["cdchamado"]);

									foreach($arrayTicketInf as $cdInfAdd => $vlInfAdd) {
										$voInf->setCdTipoInformacaoAdicional($cdInfAdd);
										$voInf->setIdScript("N");
										$voInf->setNmInformacao($vlInfAdd);
										$voInf->setDsInformacao($vlInfAdd);

										$query = "Select 
											cdtipoinformacaoadicional 
											from 
											hd_chamadoinformacaoadicional 
											where cdchamado = " . $arrayData["cdchamado"] . "
											and cdtipoinformacaoadicional = " . $cdInfAdd;

										$resultSet = $this->dao->execute($query);
										$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($resultSet, TRUE)), TRUE);

										if ($resultSet->numRows > 0) {
											$SetInf = $beanInf->alteraRegistro($voInf);
										} else {
											$SetInf = $beanInf->insereRegistro($voInf);
										}
									}
								}
							}

                            $arrayReturn['txt'] = '';
			                $arrayReturn['flow'] = 'ok';

                            break;
                        default:
                            $arrayReturn['txt'] = '';
			                $arrayReturn['flow'] = 'ok';

                            break;
                    }

                    $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($arrayReturn, TRUE)), TRUE);

                    return json_encode($arrayReturn);

				default:
					//Se for outra origem, obrigatorio um valor para nmidentificador possibilitando identificar a acao que deve ser executada.
					$IdentificadorQualitor = strtoupper($arrayData["nmidentificador"]);
            }

			//Recupera Localidade do chamado
            $getLocationData = getRecordFromTable(
                'hd_localidade', 
                array('cdlocalidade', 'nmlocalidade'), 
                array(
                    'cdlocalidade' => $getTicketData["cdlocalidade"]
                )
            );
            if (!isset($getLocationData["cdlocalidade"])) {
                $this->response = $this->apiLog->fcApiLog(array('status' => 0, 'error_code' => $this->error_code . __LINE__, 'msg' => 'Erro ao buscar dados da Localidade'), TRUE);
                return $this->response;
            } else {
                $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($getLocationData, TRUE)), TRUE);
            }

            switch ($IdentificadorQualitor) {
				 case "WS_UN_01":
					 $arrayInf = array(
						'vlinformacaoadicional1740'
					);

                    $arrayField = array(
                        'aprovador1'
                    );
					
					$arrayApiFields = array_combine($arrayInf, $arrayField);

                    $query = $this->sqlQuery->getTicketInfAdd($getTicketData);

					$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => $query), TRUE);

                    $resultSet = $this->dao->execute($query);

                    if($resultSet->numRows > 0) {
                        $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($resultSet, TRUE)), TRUE);
                        foreach ($resultSet->rows as $dataitem) {
							$arrayApiData[$arrayApiFields[$dataitem["nmalias"]]] = $dataitem["vlinf"];
						}
					}

                    $ArrayPapelAprovador = explode("-", $arrayApiData["aprovador1"]);
                    $CDPapel = $ArrayPapelAprovador[0];

                    $query = "Select 
                            cdcliente
                            , cdcontato
                            , cdpapel
                        From 
                            ad_contatopapel
                        Where
                            cdpapel = " . $CDPapel;


                    $resultSet = $this->dao->execute($query);

                    if($resultSet->numRows > 0) {
                        $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($resultSet, TRUE)), TRUE);

                        $del = "Delete hd_chamadocontato where cdchamado = " . $arrayData["cdchamado"];
                        $exec = $this->dao->execute($del);

                        foreach ($resultSet->rows as $dataitem) {
                            $NrSequencia = $this->beanCtt->retornaProximaSequencia($arrayData["cdchamado"]);
                            $this->voCtt->setCdChamado($arrayData['cdchamado']);
                            $this->voCtt->setCdCliente($dataitem['cdcliente']);
                            $this->voCtt->setCdContato($dataitem['cdcontato']);
                            $this->voCtt->setCdUsuario($_SESSION['A_cdusuario']);
                            $this->voCtt->setNrSequencia($NrSequencia);
                            $this->beanCtt->insereRegistro($this->voCtt);

						}
					} else {
						$Followup = array (
							"cdchamado" => $arrayData["cdchamado"], 
							"msg" => "No responsible contact configured for the role " . $CDPapel);
						$this->addTicketFollowup($Followup);
					}

                    $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($getTicketData, TRUE)), TRUE);

					$setNextStep = $this->WSTicket->setTicketNextStep($getTicketData);
					$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($setNextStep, TRUE)), TRUE);

                    $sendRequest['response_status']['status'] = "1";
					$sendRequest['response_status']['response'] = "Request executed successfully";
					
                    return $sendRequest;
                    break;

                case "WS_UN_02":
					 $arrayInf = array(
						'vlinformacaoadicional1741'
					);

                    $arrayField = array(
                        'aprovador2'
                    );
					
					$arrayApiFields = array_combine($arrayInf, $arrayField);

                    $query = $this->sqlQuery->getTicketInfAdd($getTicketData);

					$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => $query), TRUE);

                    $resultSet = $this->dao->execute($query);

                    if($resultSet->numRows > 0) {
                        $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($resultSet, TRUE)), TRUE);
                        foreach ($resultSet->rows as $dataitem) {
							$arrayApiData[$arrayApiFields[$dataitem["nmalias"]]] = $dataitem["vlinf"];
						}
					}

                    $ArrayPapelAprovador = explode("-", $arrayApiData["aprovador2"]);
                    $CDPapel = $ArrayPapelAprovador[0];

                    $query = "Select 
                            cdcliente
                            , cdcontato
                            , cdpapel
                        From 
                            ad_contatopapel
                        Where
                            cdpapel = " . $CDPapel;


                    $resultSet = $this->dao->execute($query);

                    if($resultSet->numRows > 0) {
                        $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($resultSet, TRUE)), TRUE);
                        
                        $del = "Delete hd_chamadocontato where cdchamado = " . $arrayData["cdchamado"];
                        $exec = $this->dao->execute($del);
                        
                        foreach ($resultSet->rows as $dataitem) {
                            $NrSequencia = $this->beanCtt->retornaProximaSequencia($arrayData["cdchamado"]);
                            $this->voCtt->setCdChamado($arrayData['cdchamado']);
                            $this->voCtt->setCdCliente($dataitem['cdcliente']);
                            $this->voCtt->setCdContato($dataitem['cdcontato']);
                            $this->voCtt->setCdUsuario($_SESSION['A_cdusuario']);
                            $this->voCtt->setNrSequencia($NrSequencia);
                            $this->beanCtt->insereRegistro($this->voCtt);

						}
					} else {
						$Followup = array (
							"cdchamado" => $arrayData["cdchamado"], 
							"msg" => "No responsible contact configured for the role " . $CDPapel);
						$this->addTicketFollowup($Followup);
					}

                    $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($getTicketData, TRUE)), TRUE);

					$setNextStep = $this->WSTicket->setTicketNextStep($getTicketData);
					$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($setNextStep, TRUE)), TRUE);
					
                    $sendRequest['response_status']['status'] = "1";
					$sendRequest['response_status']['response'] = "Request executed successfully";

                    return $sendRequest;
                    break;

                case "WS_UN_DEL":
                    $del = "Delete hd_chamadocontato where cdchamado = " . $arrayData["cdchamado"];
                    $exec = $this->dao->execute($del);

                    $sendRequest['response_status']['status'] = "1";
					$sendRequest['response_status']['response'] = "Request executed successfully";
					
                    return $sendRequest;
                    break;

                case "WS_PARALLEL_CONTACTADD":
                    $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($arrayData, TRUE)), TRUE);

                    $ChamadoOrigem = explode("_",  $arrayData["idreplicar"]);

                    $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($ChamadoOrigem, TRUE)), TRUE);

                    /* Busca o contato com o mesmo usuario de rede do responsavel do chamado que gerou o chamado paralelo*/
                    $query = "Select 
                            chm.cdchamado
                            , chm.cdresponsavel 
                            , usr.nmusuariorede
                            , ctt.cdcliente
                            , ctt.cdcontato
                        from  
                            hd_chamado chm 
                            inner join ad_usuario usr 
                                on chm.cdresponsavel = usr.cdusuario 
                            inner join ad_contato ctt 
                                on chm.cdcliente = ctt.cdcliente and usr.nmusuariorede = ctt.cdloginweb
                        Where 
                            chm.cdchamado = " . $ChamadoOrigem[0];
                    
                    $resultSet = $this->dao->execute($query);

                    $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($resultSet, TRUE)), TRUE);

                    if($resultSet->numRows > 0) {
                        foreach ($resultSet->rows as $dataitem) {
                            $NrSequencia = $this->beanCtt->retornaProximaSequencia($arrayData["cdchamado"]);
                            $this->voCtt->setCdChamado($arrayData['cdchamado']);
                            $this->voCtt->setCdCliente($dataitem['cdcliente']);
                            $this->voCtt->setCdContato($dataitem['cdcontato']);
                            $this->voCtt->setCdUsuario($_SESSION['A_cdusuario']);
                            $this->voCtt->setNrSequencia($NrSequencia);
                            $this->beanCtt->insereRegistro($this->voCtt);

						}
					}

                    $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($getTicketData, TRUE)), TRUE);

					#$setNextStep = $this->WSTicket->setTicketNextStep($getTicketData);
					#$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($setNextStep, TRUE)), TRUE);

                    $sendRequest['response_status']['status'] = "1";
					$sendRequest['response_status']['response'] = "Request executed successfully";
					
                    return $sendRequest;
                    break;

            }

            $this->response = $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => 'Concluído com sucesso'), TRUE);
            return $this->response;
        }

        private function addTicketFollowup($arrayData) {
            // GRAVA OS ACOMPANHAMENTOS - ERRO
            $this->voHist->setCdChamado($arrayData["cdchamado"]);
            $this->voHist->setCdTipoAcompanhamento(1);
            $this->voHist->setDsAcompanhamento($arrayData["msg"]);
            $hist = $this->beanHist->insereRegistro($this->voHist);

            $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($hist, TRUE)), TRUE);
        }
				
		private function updInfadd($arrayData) {
			$query = "Select 
				isnull(count(*), 0) idexists 
				from 
				hd_chamadoinformacaoadicional 
				where cdchamado = " . $arrayData["cdchamado"] . 
				" and cdtipoinformacaoadicional = " . $arrayData['cdtipoinformacaoadicional'];

			//Verifica se existe Informacao Adicional gravada no chamado
			$getExistsInfadd = $this->dao->execute($query);

			$this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($getExistsInfadd, TRUE)), TRUE);

			$this->voInf->setCdChamado($arrayData["cdchamado"]);
			$this->voInf->setCdTipoInformacaoAdicional($arrayData['cdtipoinformacaoadicional']);
			$this->voInf->setIdScript("N");
			$this->voInf->setNmInformacao($arrayData["vlinf"]);
			$this->voInf->setDsInformacao($arrayData["vlinf"]);
			if (is_numeric($getExistsInfadd->rows[0]["idexists"])) {
				if ($getExistsInfadd->rows[0]["idexists"] > 0){
					$SetInf = $this->beanInf->alteraRegistro($this->voInf);
				} else {
					$SetInf = $this->beanInf->insereRegistro($this->voInf);
				}
			}
			
			return $this->apiLog->fcApiLog(array('status' => 1, 'error_code' => $this->error_code . __LINE__, 'msg' => print_r($SetInf, TRUE)), TRUE);
		}
    }
?>