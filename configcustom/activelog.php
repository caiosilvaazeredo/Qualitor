<?php
//Serve para habilitar o armazenamento do Log .
//Para ativar:
//[urlQualitor]/customsim/configcustom/activelog.php?idactive=true
//Para desativar:
//[urlQualitor]/customsim/configcustom/activelog.php?idactive=true

// Nome do arquivo XML
$xmlFile = 'activeLog.xml';

// Função para criar o arquivo XML
function createXML($filePath, $value) {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><activelog></activelog>');
    $param = $xml->addChild('param');
    $param->addAttribute('name', 'idactive');
    $param->addAttribute('value', $value);

    // Salvar o arquivo
    $xml->asXML($filePath);
    echo "Arquivo XML criado com sucesso com value='$value'.\n";
}

// Função para atualizar o valor do param no XML
function updateXML($filePath, $value) {
    if (file_exists($filePath)) {
        $xml = simplexml_load_file($filePath);

        // Atualizar o valor do atributo
        $xml->param['value'] = $value;

        // Salvar o arquivo
        $xml->asXML($filePath);
        echo "Arquivo XML atualizado com value='$value'.\n";
    } else {
        echo "O arquivo XML não existe. Criando um novo arquivo.\n";
        createXML($filePath, $value);
    }
}

// Definir o valor desejado (TRUE ou FALSE)
$newValue = $_REQUEST["idactive"]; // Altere para 'FALSE' conforme necessário

// Verificar se o arquivo existe e agir adequadamente
if (file_exists($xmlFile)) {
    updateXML($xmlFile, $newValue);
} else {
    createXML($xmlFile, $newValue);
}

?>
