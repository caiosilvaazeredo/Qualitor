<?php
/**************************************************************/
    header('Content-Type: text/html; charset=utf-8');
    mb_internal_encoding("UTF-8");
    header('Expires: Thu, 01 Jan 1990 00:00:00 GMT');
    /**************************************************************/

    /**************************************************************/
    /* Inicializacao de funcoes do Qualitor                       */
    require_once(__DIR__ . "/../../configLingua.php");

    $getURL = getRecordFromTable(
        'ad_parametrogeral', 
        array('vlparametro'), 
        array(
            'cdparametro' => 84
        )
    );
    
    $url = "";

    if (isset($getURL["vlparametro"]) && !empty($getURL["vlparametro"])) {
        $url = $getURL["vlparametro"];
    }

    /**************************************************************/
    $arrayPath = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));

    $pathCurrent = $arrayPath[max(array_keys($arrayPath))];
    /**************************************************************/

    /**
     * Função de PLACEHOLDER para verificar credenciais.
     * SUBSTITUA ESTA FUNÇÃO PELO SEU CÓDIGO REAL DE BUSCA DE USUÁRIO (API, DB, etc.)
     * * @param string $username O nome de usuário fornecido.
     * @param string $password A senha fornecida.
     * @return bool Retorna TRUE se as credenciais forem válidas.
     */
    function verificarCredenciais($username, $password) {
        if (file_exists(__DIR__ . '/../../config/basic/arr_basic.php')) {
            if (!function_exists('array_merge_keys')) {
                include_once __DIR__ . '/../../config/basic/arr_basic.php';
            }
        }

        include_once __DIR__ . "/../../framework/qcrypt/QCrypt.php";
        $QCrypt = new QCrypt();

        $pass = $QCrypt->cryptMD5($password);

        importClass('dao/ComumDao', true);
        $dao = new ComumDao();

        $getUser = $dao->execute("Select cdusuario, cdsenha from ad_usuario where nmusuariorede = '" . $username . "' and cdsenha = '" . $pass . "'");

        
        return ($getUser->numRows == 1);
        // -----------------------------------------------------------------
    }

    // --- LÓGICA DE LOGIN/LOGOUT ---

    // 1. Lógica de Logout
    if (isset($_GET['logout'])) {
        session_unset(); // Remove todas as variáveis de sessão
        session_destroy(); // Destroi a sessão
        // Redireciona para evitar reenvio do logout
        header('Location: setup.php'); 
        exit;
    }

    // 2. Lógica de Processamento de Login (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (verificarCredenciais($username, $password)) {
            // Se as credenciais estiverem OK
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            // Redireciona para evitar reenvio do formulário de login
            header('Location: setup.php'); 
            exit;
        } else {
            $message = 'Usuário ou senha inválidos.';
            $message_type = 'error';
        }
    }

    // 3. Verifica se o usuário está logado
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // SE NÃO ESTIVER LOGADO, EXIBE APENAS A TELA DE LOGIN
        include 'setup.html'; // Usaremos um include para o formulário de login
        exit;
    }

    // 1. Definições Iniciais
    $arquivo_ini = __DIR__ . '/configcustom/customform.ini';
    $status_mensagem = '';

    // Colunas fixas do seu banco de dados. Essas NÃO devem ser alteradas pelo usuário.
    $colunas_db_fixas = [
        'nmcontato',
        'nmcontatosuperior',
        'nmcargo',
    ];


    // 2. Lógica de Processamento do Formulário (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mapeamento'])) {
        
        $dados_do_form = $_POST['mapeamento'];
        $conteudo_ini = '';
        
        // Constrói o conteúdo do arquivo INI linha por linha
        foreach ($colunas_db_fixas as $coluna_db) {
            // Pega o nome do campo do formulário enviado pelo usuário
            $campo_form = trim($dados_do_form[$coluna_db] ?? ''); 
            
            if (!empty($campo_form)) {
                // Formato INI: campo_do_formulario = "coluna_do_banco"
                // O valor da string precisa estar entre aspas no INI para garantir a compatibilidade.
                $conteudo_ini .= "{$campo_form} = \"{$coluna_db}\"\n";
            }
        }

        // Tenta salvar o conteúdo no arquivo, sobrescrevendo o anterior.
        // O FILE_APPEND foi OMITIDO, então ele SOBRESCREVE (ignora o arquivo anterior).
        if (file_put_contents($arquivo_ini, $conteudo_ini) !== false) {
            $status_mensagem = '<div style="color: green; border: 1px solid green; padding: 10px; margin-bottom: 20px;">✅ Sucesso! O arquivo <strong>' . $arquivo_ini . '</strong> foi criado/atualizado.</div>';
        } else {
            $status_mensagem = '<div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 20px;">❌ Erro ao escrever no arquivo. Verifique as permissões de escrita do diretório.</div>';
        }
    }


    // 3. Lógica para pré-popular o formulário (leitura do INI existente)
    $mapeamento_atual = [];
    if (file_exists($arquivo_ini)) {
        // Carrega o INI para popular os campos do formulário
        $mapeamento_carregado = parse_ini_file($arquivo_ini);
        
        // Inverte a ordem para facilitar a pré-população: [coluna_db] => campo_form
        if ($mapeamento_carregado) {
            foreach ($mapeamento_carregado as $campo_form => $coluna_db) {
                $mapeamento_atual[$coluna_db] = $campo_form;
            }
        }
    }

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configuração de Mapeamento</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        label { display: block; font-weight: bold; margin-top: 10px; }
        input[type="text"] { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        .row { display: flex; align-items: center; margin-bottom: 15px; }
        .row > div { flex: 1; padding: 0 10px; }
        .coluna { font-weight: bold; color: #555; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 3px; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>

    <div class="container">
        <h2>🔗 Mapeamento de Campos do Formulário</h2>
        <p>Informe o **Nome da Variável/Campo do Formulário** que corresponde a cada **Coluna do Banco de Dados**.</p>
        
        <?php echo $status_mensagem; ?>

        <form method="POST">
            <div class="row" style="border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <div style="flex: 2;">**Campo do Formulário** (Variável do Usuário)</div>
                <div style="flex: 1;">**Coluna do Banco de Dados**</div>
            </div>
            
            <?php foreach ($colunas_db_fixas as $coluna_db): ?>
                <div class="row">
                    <div style="flex: 2;">
                        <input 
                            type="text" 
                            name="mapeamento[<?php echo $coluna_db; ?>]" 
                            placeholder="Ex: vlinformacaoadicional1"
                            value="<?php echo htmlspecialchars($mapeamento_atual[$coluna_db] ?? ''); ?>"
                            required
                        >
                    </div>
                    <div style="flex: 1;" class="coluna">
                        => <?php echo $coluna_db; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <button type="submit">Salvar Mapeamento (Criar/Sobrescrever INI)</button>
        </form>
    </div>

</body>
</html>