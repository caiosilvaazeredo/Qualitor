<?php
	header("Content-Type: text/html; charset=UTF-8");

	$canvas = new canvas();

	$showcanvas = $canvas->tiporelacao(array("cdchamado" => $_REQUEST["cdchamado"]));

	echo $showcanvas;

	class canvas {
		function __construct() {
			require_once '../../configcustom/configPaths.php';
			require_once '../../configcustom/dbcustom.php';
			
			$this->dbcustom = new dbcustom();
		}

		public function tiporelacao ($arrayData) {
			$sqlquery = "Select 
					rel.cdchamado as cdchamado
				from  
					hd_chamadorelacionado rel 
				Where 
					rel.cdchamadorelacionado = " . $arrayData["cdchamado"] ;

			$res = $this->dbcustom->dbquery($sqlquery);
			if ($res["numRows"] > 0) {
				return $this->exiberelacionados ($arrayData);
			}

			$sqlquery = "Select 
					rel.cdchamadorelacionado as cdchamado
				from  
					hd_chamadorelacionado rel 
				Where 
					rel.cdchamado = " . $arrayData["cdchamado"] ;

			$res = $this->dbcustom->dbquery($sqlquery);
			if ($res["numRows"] > 0) {
				return $this->exiberelacao (array("cdchamado" => $res['dataitem'][0]["cdchamado"]));
			}
		}
		
		public function exiberelacionados ($arrayData) {
			$sqlquery = "with chm_rel as ( 
				Select 
					rel.cdchamadorelacionado as cdchamado, 
					rel.cdchamado as cdchamadorelacao, 
					isnull(processo.nmestrutura+'->'+etapa.nmsubsituacao, 'Sem Processo') as nmetapa, 
					sit.nmsituacao, 
					usr.nmusuario nmresponsavel, 
					eq.nmequipe 
				from  
					hd_chamado chm 
					inner join hd_chamadorelacionado rel on chm.cdchamado = rel.cdchamado 
					left join hd_situacao sit on chm.cdsituacao = sit.cdsituacao 
					left join ad_usuario usr on chm.cdresponsavel = usr.cdusuario 
					left join hd_equipe eq on chm.cdequipe = eq.cdequipe 
					left join hd_estruturasubsituacao processo on chm.cdestrutura = processo.cdestrutura 
					left join hd_estruturasubsituacaoitem etapa on chm.cdsubsituacao = etapa.nrsequencia 
				Where 
					rel.cdchamadorelacionado = " . $arrayData["cdchamado"] . "
				), 
				ult_ativ as ( 
					Select 
						rel.cdchamado, 
						max(cdacompanhamento) as cdacompanhamento 
					From 
						hd_acompanhamento rel 
						inner join chm_rel on rel.cdchamado = chm_rel.cdchamadorelacao 
					Where 
						cdtipoacompanhamento is not null 
					Group by 
							rel.cdchamado 
				), 
				ds_ativ as ( 
					Select 
						hd_acompanhamento.cdchamado, 
						hd_acompanhamento.dsacompanhamento 
					from 
						hd_acompanhamento 
						inner join ult_ativ on 
							hd_acompanhamento.cdchamado = ult_ativ.cdchamado and 
							hd_acompanhamento.cdacompanhamento = ult_ativ.cdacompanhamento 
				), 
				infadd as ( 
					select 
						a.cdchamado, 
						b.nmtipoinformacaoadicional, 
						isnull(case b.idtipodado 
						when 'T' then a.nminformacao 
						when 'N' then cast(a.vlinformacao as varchar(30)) 
						when 'D' then convert(varchar(10), a.dtinformacao, 103) 
						when 'M' then cast(a.dsinformacao as varchar(max)) end, '') as vlinf, 
						a.nrsequencia 
					from hd_chamadoinformacaoadicional a inner join hd_tipoinformacaoadicional b 
						on b.cdtipoinformacaoadicional = a.cdtipoinformacaoadicional 
						inner join chm_rel on chm_rel.cdchamadorelacao = a.cdchamado 
				) 
				Select distinct 
					chm_rel.cdchamado, 
					chm_rel.cdchamadorelacao, 
					chm_rel.nmetapa, 
					chm_rel.nmsituacao, 
					chm_rel.nmresponsavel, 
					chm_rel.nmequipe, 
					isnull(cast(dsacompanhamento as varchar(max)), 'Nenhum registro encontrado') dsacompanhamento, 
					nmtipoinformacaoadicional, 
					vlinf 
				from 
					chm_rel 
					left join ds_ativ on chm_rel.cdchamadorelacao = ds_ativ.cdchamado 
					left join infadd on chm_rel.cdchamadorelacao = infadd.cdchamado ";

			$res = $this->dbcustom->dbquery($sqlquery);
			if ($res["numRows"] == 0) {
				return "";
			}

			$dataCavas = array();
			foreach ($res['dataitem'] as $dataitem) {
				array_push($dataCavas, $dataitem);
			}
			
			$dataticket = array();
			$ticket = array();
			$id = 0;
			foreach ($dataCavas as $i) {

				if (!in_array ($i["cdchamadorelacao"], $ticket)) {
					array_push($ticket, $i["cdchamadorelacao"]);
					$dataticket[$i["cdchamadorelacao"]]["cab"] = $this->DataTicket($i);
					$id = 0;
				} 
				
				$dataticket[$i["cdchamadorelacao"]]["infadd"][$id] = $this->DataTicketInfAdd($i);
				$id++;

			}
			
			return $this->renderChamadosRelacionados($dataticket);

		}

		public function exiberelacao ($arrayData) {
			$sqlquery = "with chm_rel as ( 
				Select 
					chm.cdchamado as cdchamadorelacao, 
					" . $_REQUEST["cdchamado"] . " as cdchamado, 
					isnull(processo.nmestrutura+'->'+etapa.nmsubsituacao, 'Sem Processo') as nmetapa, 
					sit.nmsituacao, 
					usr.nmusuario nmresponsavel, 
					eq.nmequipe 
				from  
					hd_chamado chm 
					inner join hd_situacao sit on chm.cdsituacao = sit.cdsituacao 
					left join ad_usuario usr on chm.cdresponsavel = usr.cdusuario 
					left join hd_equipe eq on chm.cdequipe = eq.cdequipe 
					left join hd_estruturasubsituacao processo on chm.cdestrutura = processo.cdestrutura 
					left join hd_estruturasubsituacaoitem etapa on chm.cdsubsituacao = etapa.nrsequencia 
				Where 
					chm.cdchamado = " . $arrayData["cdchamado"] . "
				), 
				ult_ativ as ( 
					Select 
						rel.cdchamado, 
						max(cdacompanhamento) as cdacompanhamento 
					From 
						hd_acompanhamento rel 
						inner join chm_rel on rel.cdchamado = chm_rel.cdchamado 
					Where 
						cdtipoacompanhamento is not null 
					Group by 
							rel.cdchamado 
				), 
				ds_ativ as ( 
					Select 
						hd_acompanhamento.cdchamado, 
						hd_acompanhamento.dsacompanhamento 
					from 
						hd_acompanhamento 
						inner join ult_ativ on 
							hd_acompanhamento.cdchamado = ult_ativ.cdchamado and 
							hd_acompanhamento.cdacompanhamento = ult_ativ.cdacompanhamento 
				), 
				infadd as ( 
					select 
						a.cdchamado, 
						b.nmtipoinformacaoadicional, 
						isnull(case b.idtipodado 
						when 'T' then a.nminformacao 
						when 'N' then cast(a.vlinformacao as varchar(30)) 
						when 'D' then convert(varchar(10), a.dtinformacao, 103) 
						when 'M' then cast(a.dsinformacao as varchar(max)) end, '') as vlinf, 
						a.nrsequencia 
					from hd_chamadoinformacaoadicional a inner join hd_tipoinformacaoadicional b 
						on b.cdtipoinformacaoadicional = a.cdtipoinformacaoadicional 
						inner join chm_rel on chm_rel.cdchamadorelacao = a.cdchamado 
				) 
				Select distinct 
					chm_rel.cdchamado, 
					chm_rel.cdchamadorelacao, 
					chm_rel.nmetapa, 
					chm_rel.nmsituacao, 
					chm_rel.nmresponsavel, 
					chm_rel.nmequipe, 
					isnull(cast(dsacompanhamento as varchar(max)), 'Nenhum registro encontrado') dsacompanhamento, 
					nmtipoinformacaoadicional, 
					vlinf 
				from 
					chm_rel 
					left join ds_ativ on chm_rel.cdchamadorelacao = ds_ativ.cdchamado 
					left join infadd on chm_rel.cdchamadorelacao = infadd.cdchamado ";

			$res = $this->dbcustom->dbquery($sqlquery);
			if ($res["numRows"] == 0) {
				return "";
			}

			$dataCavas = array();
			foreach ($res['dataitem'] as $dataitem) {
				array_push($dataCavas, $dataitem);
			}
			
			$dataticket = array();
			$ticket = array();
			$id = 0;
			foreach ($dataCavas as $i) {

				if (!in_array ($i["cdchamadorelacao"], $ticket)) {
					array_push($ticket, $i["cdchamadorelacao"]);
					$dataticket[$i["cdchamadorelacao"]]["cab"] = $this->DataTicket($i);
					$id = 0;
				} 
				
				$dataticket[$i["cdchamadorelacao"]]["infadd"][$id] = $this->DataTicketInfAdd($i);
				$id++;

			}
			
			return $this->renderChamadosRelacionados($dataticket);

		}

		public function renderChamadosRelacionados(array $dados): string {
			$html = <<<HTML
			<!DOCTYPE html>
			<html lang="pt-br">
			<head>
				<meta charset="UTF-8">
				<title>Chamados Relacionados</title>
				<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
				<style>
					body { font-family: "Open Sans"; font-size: 12px; background-color: #fefeff;}
					.tabs { margin-top: 20px; }
					.tab-buttons { margin-bottom: 10px; }
					.tab-buttons button { padding: 8px; margin-right: 5px; cursor: pointer; }
					.tab-content { display: none; border: 1px solid #ccc; padding: 10px; }
					.tab-content.active { display: block; }
					table { width: 100%; border-collapse: collapse; margin-top: 10px; }
					th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
				</style>
			</head>
			<body>
				<h2>Chamados Relacionados</h2>

				<div class="tabs">
					<div class="tab-buttons">
			HTML;

				foreach ($dados as $id => $chamado) {
					$html .= "<button data-tab=\"tab-{$id}\">Chamado {$id}</button>";
				}

				foreach ($dados as $id => $chamado) {
					$html .= <<<HTML
						<div id="tab-{$id}" class="tab-content">
							<h3>Dados do Chamado {$id}</h3>
							<p><strong>Rela&ccedil;&atilde;o:</strong> {$chamado['cab']['cdchamado']}</p>
							<p><strong>Etapa:</strong> {$chamado['cab']['nmetapa']}</p>
							<p><strong>Situa&ccedil;&atilde;o:</strong> {$chamado['cab']['nmsituacao']}</p>
							<p><strong>Respons&aacute;vel:</strong> {$chamado['cab']['nmresponsavel']}</p>
							<p><strong>Equipe:</strong> {$chamado['cab']['nmequipe']}</p>
							<p><strong>&Uacute;ltima Atividade:</strong> {$chamado['cab']['dsacompanhamento']}</p>

							<h4>Informa&ccedil;&otilde;es Adicionais</h4>
							<table>
								<thead>
									<tr><th>Tipo</th><th>Valor</th></tr>
								</thead>
								<tbody>
			HTML;
					foreach ($chamado['infadd'] as $info) {
						$tipo  = $info['nmtipoinformacaoadicional'];
						$valor = $info['vlinf'];
						$html .= "<tr><td>{$tipo}</td><td>{$valor}</td></tr>";
					}

					$html .= <<<HTML
								</tbody>
							</table>
						</div>
			HTML;
				}

				$html .= <<<HTML
					</div>
				</div>

				<script>
					\$(document).ready(function(){
						\$('.tab-buttons button').on('click', function(){
							var tabId = \$(this).data('tab');
							\$('.tab-content').removeClass('active');
							\$('#' + tabId).addClass('active');
						});
						\$('.tab-buttons button:first').click();
					});
				</script>
			</body>
			</html>
			HTML;

			return $html;
		}

		function DataTicket ($dataticket) {
			$keyticket = array("cdchamado", "cdchamadorelacao", "nmetapa", "nmsituacao", "nmresponsavel", "nmequipe", "dsacompanhamento");

			foreach ($dataticket as $i => $k) {
				if (in_array($i, $keyticket)) {
					$data[$i] = $this->corrigeCharset($k);
				}
			}
			
			return $data;
		}
		
		function DataTicketInfAdd ($dataticket) {
			$keyticket = array("cdchamado", "cdchamadorelacao", "nmetapa", "nmsituacao", "nmresponsavel", "nmequipe", "dsacompanhamento");

			foreach ($dataticket as $i => $k) {
				if (!in_array($i, $keyticket)) {
					$data[$i] = $this->corrigeCharset($k);
				}
			}
			
			return $data;
		}

        function corrigeCharset($texto, $saidaCharset = 'ISO-8859-1') {
            if (empty($texto)) {
                return $texto;
            }

            // Detecta automaticamente o encoding
            $detected = mb_detect_encoding($texto, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

            // Se não conseguiu detectar, tenta forçar UTF-8
            if ($detected === false) {
                $detected = 'UTF-8';
            }

			/*
			if ($detected == 'UTF-8') {
				return $texto;
			}
				*/
            // Converte somente se for necessário
            if (strtoupper($detected) !== strtoupper($saidaCharset)) {
                return mb_convert_encoding($texto, $saidaCharset, $detected);
            }

            return $texto;
        }
		
	}
?>