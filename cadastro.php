<?php
// Inicia a sessão para manter os dados preenchidos no formulário, conforme a Agenda 16
if (!isset($_SESSION)) {
    session_start();
}

// Função 1: Busca o endereço completo a partir do CEP (Padrão da Agenda 16)
function get_endereco_por_cep($cep) {
    $cep = preg_replace("/[^0-9]/", "", $cep);
    if (strlen($cep) !== 8) {
        return null;
    }
    $url = "https://viacep.com.br/ws/{$cep}/xml/";
    return @simplexml_load_file($url);
}

// Função 2: Busca o(s) CEP(s) a partir de Estado (UF), Cidade e Logradouro
function get_cep_por_endereco($uf, $cidade, $logradouro) {
    $uf = urlencode(trim($uf));
    // rawurlencode garante que espaços virem %20 (exigência da API ViaCEP para endereços)
    $cidade = rawurlencode(trim($cidade));
    $logradouro = rawurlencode(trim($logradouro));
    
    $url = "https://viacep.com.br/ws/{$uf}/{$cidade}/{$logradouro}/xml/";
    return @simplexml_load_file($url);
}

// Inicializa variáveis do formulário
$nome       = $_POST["txtNome"] ?? "";
$cep        = $_POST["txtCEP"] ?? "";
$logradouro = $_POST["txtLogradouro"] ?? "";
$numero     = $_POST["txtNumero"] ?? "";
$bairro     = $_POST["txtBairro"] ?? "";
$cidade     = $_POST["txtCidade"] ?? "";
$estado     = $_POST["txtEstado"] ?? "";

$mensagem = "";
$lista_ceps_encontrados = [];

// AÇÃO 1: Clicou em "Buscar Endereço" (CEP -> Endereço)
if (isset($_POST["btnBuscarEndereco"])) {
    $resultado = get_endereco_por_cep($cep);
    
    if ($resultado && !isset($resultado->erro)) {
        $cep        = (string)$resultado->cep;
        $logradouro = (string)$resultado->logradouro;
        $bairro     = (string)$resultado->bairro;
        $cidade     = (string)$resultado->localidade;
        $estado     = (string)$resultado->uf;
        $mensagem   = "<div class='w3-panel w3-green w3-round-large w3-padding'>Endereço localizado com sucesso!</div>";
    } else {
        $mensagem   = "<div class='w3-panel w3-red w3-round-large w3-padding'>CEP não encontrado ou inválido. Digite 8 números.</div>";
    }
}

// AÇÃO 2: Clicou em "Buscar CEP" (Endereço -> CEP)
if (isset($_POST["btnBuscarCEP"])) {
    if (strlen(trim($estado)) == 2 && strlen(trim($cidade)) >= 3 && strlen(trim($logradouro)) >= 3) {
        $resultado = get_cep_por_endereco($estado, $cidade, $logradouro);
        
        if ($resultado && isset($resultado->enderecos->endereco)) {
            $enderecos = $resultado->enderecos->endereco;
            
            // Se encontrou apenas 1 CEP exato, já preenche direto
            if (count($enderecos) == 1) {
                $cep        = (string)$enderecos[0]->cep;
                $logradouro = (string)$enderecos[0]->logradouro;
                $bairro     = (string)$enderecos[0]->bairro;
                $cidade     = (string)$enderecos[0]->localidade;
                $estado     = (string)$enderecos[0]->uf;
                $mensagem   = "<div class='w3-panel w3-green w3-round-large w3-padding'>CEP encontrado e preenchido!</div>";
            } else {
                // Se encontrou vários (ex: ruas grandes com vários CEPs), guarda para o usuário escolher
                $lista_ceps_encontrados = $enderecos;
                $mensagem = "<div class='w3-panel w3-amber w3-round-large w3-padding'>Foram encontrados " . count($enderecos) . " CEPs para esse endereço. Selecione o correto abaixo:</div>";
            }
        } else {
            $mensagem = "<div class='w3-panel w3-red w3-round-large w3-padding'>Nenhum CEP encontrado para este endereço. Verifique UF, Cidade e Rua (mínimo 3 letras).</div>";
        }
    } else {
        $mensagem = "<div class='w3-panel w3-orange w3-round-large w3-padding'>Para buscar o CEP, preencha o <b>Estado (UF com 2 letras)</b>, a <b>Cidade</b> e pelo menos 3 letras do <b>Logradouro</b>.</div>";
    }
}

// AÇÃO 3: Clicou em "Selecionar" na lista de múltiplos CEPs encontrados
if (isset($_POST["btnSelecionarCep"])) {
    $dados = explode("|", $_POST["btnSelecionarCep"]);
    $cep        = $dados[0] ?? "";
    $logradouro = $dados[1] ?? "";
    $bairro     = $dados[2] ?? "";
    $cidade     = $dados[3] ?? "";
    $estado     = $dados[4] ?? "";
    $mensagem   = "<div class='w3-panel w3-green w3-round-large w3-padding'>Endereço selecionado com sucesso!</div>";
}

// AÇÃO 4: Clicou em "Cadastrar" (Apenas valida e exibe confirmação na tela, sem banco de dados)
if (isset($_POST["btnCadastrar"])) {
    $mensagem = "<div class='w3-panel w3-blue w3-round-large w3-padding'>
                    <h4>Dados prontos para envio (Simulação sem Banco de Dados):</h4>
                    <p><b>Nome:</b> " . htmlspecialchars($nome) . " | <b>CEP:</b> " . htmlspecialchars($cep) . "<br>
                    <b>Endereço:</b> " . htmlspecialchars($logradouro) . ", Nº " . htmlspecialchars($numero) . " - " . htmlspecialchars($bairro) . " (" . htmlspecialchars($cidade) . "/" . htmlspecialchars($estado) . ")</p>
                 </div>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3B1XeVIU" crossorigin="anonymous">
    <title>Enlatados Juarez - Cadastro via Web Service</title>
</head>
<body class="w3-black">

<div class="w3-padding-32 w3-content w3-text-grey w3-center" style="max-width: 750px;">
    <h2 class="w3-text-cyan">Cadastro de Clientes (Web Service ViaCEP)</h2>
    <p class="w3-small">Digite o CEP para buscar o endereço ou preencha Rua, Cidade e UF para descobrir o CEP.</p>

    <!-- Exibe avisos de retorno da API -->
    <?php echo $mensagem; ?>

    <form action="cadastro.php" method="post" class="w3-row w3-text-blue w3-center">
        
        <!-- Lista de opções caso a busca por endereço retorne mais de um CEP -->
        <?php if (!empty($lista_ceps_encontrados)): ?>
            <div class="w3-container w3-dark-grey w3-round-large w3-margin-bottom w3-padding">
                <table class="w3-table w3-bordered w3-small w3-text-white">
                    <thead>
                        <tr class="w3-text-cyan">
                            <th>CEP</th>
                            <th>Logradouro</th>
                            <th>Complemento</th>
                            <th>Bairro</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_ceps_encontrados as $item): 
                            $valor_pacote = "{$item->cep}|{$item->logradouro}|{$item->bairro}|{$item->localidade}|{$item->uf}";
                        ?>
                        <tr>
                            <td><?php echo $item->cep; ?></td>
                            <td><?php echo $item->logradouro; ?></td>
                            <td><?php echo $item->complemento; ?></td>
                            <td><?php echo $item->bairro; ?></td>
                            <td>
                                <button type="submit" name="btnSelecionarCep" value="<?php echo htmlspecialchars($valor_pacote); ?>" class="w3-button w3-tiny w3-cyan w3-round">
                                    Usar este
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Campo Nome -->
        <div class="w3-row w3-section">
            <div class="w3-col" style="width:11%;">
                <i class="w3-xxlarge fa fa-user"></i>
            </div>
            <div class="w3-rest">
                <input class="w3-input w3-border w3-round-large" name="txtNome" type="text" placeholder="Nome Completo" value="<?php echo htmlspecialchars($nome); ?>">
            </div>
        </div>

        <!-- Campo CEP + Botão Buscar Endereço -->
        <div class="w3-row w3-section">
            <div class="w3-col" style="width:11%;">
                <i class="w3-xxlarge far fa-envelope-open"></i>
            </div>
            <div class="w3-col" style="width:54%;">
                <input class="w3-input w3-border w3-round-large" name="txtCEP" type="text" placeholder="CEP Ex: 01208000" value="<?php echo htmlspecialchars($cep); ?>">
            </div>
            <div class="w3-rest">
                <button type="submit" name="btnBuscarEndereco" class="w3-button w3-blue w3-round-large w3-right" style="width: 95%;">
                    <i class="fa fa-search"></i> Buscar Endereço
                </button>
            </div>
        </div>

        <!-- Campo Logradouro + Botão Buscar CEP -->
        <div class="w3-row w3-section">
            <div class="w3-col" style="width:11%;">
                <i class="w3-xxlarge fas fa-road"></i>
            </div>
            <div class="w3-col" style="width:54%;">
                <input class="w3-input w3-border w3-round-large" name="txtLogradouro" type="text" placeholder="Rua, Avenida, Praça..." value="<?php echo htmlspecialchars($logradouro); ?>">
            </div>
            <div class="w3-rest">
                <button type="submit" name="btnBuscarCEP" class="w3-button w3-teal w3-round-large w3-right" style="width: 95%;" title="Preencha Rua, Cidade e Estado para buscar o CEP">
                    <i class="fa fa-map-pin"></i> Buscar CEP
                </button>
            </div>
        </div>

        <!-- Campo Número -->
        <div class="w3-row w3-section">
            <div class="w3-col" style="width:11%;">
                <span class="w3-xxlarge">Nº</span>
            </div>
            <div class="w3-rest">
                <input class="w3-input w3-border w3-round-large" name="txtNumero" type="text" placeholder="Número (ex: 1111)" value="<?php echo htmlspecialchars($numero); ?>">
            </div>
        </div>

        <!-- Campo Bairro -->
        <div class="w3-row w3-section">
            <div class="w3-col" style="width:11%;">
                <i class="w3-xxlarge fas fa-home"></i>
            </div>
            <div class="w3-rest">
                <input class="w3-input w3-border w3-round-large" name="txtBairro" type="text" placeholder="Bairro" value="<?php echo htmlspecialchars($bairro); ?>">
            </div>
        </div>

        <!-- Campo Cidade -->
        <div class="w3-row w3-section">
            <div class="w3-col" style="width:11%;">
                <i class="w3-xxlarge fas fa-map-marked"></i>
            </div>
            <div class="w3-rest">
                <input class="w3-input w3-border w3-round-large" name="txtCidade" type="text" placeholder="Cidade (ex: São Paulo)" value="<?php echo htmlspecialchars($cidade); ?>">
            </div>
        </div>

        <!-- Campo Estado (UF) -->
        <div class="w3-row w3-section">
            <div class="w3-col" style="width:11%;">
                <i class="w3-xxlarge fas fa-map-marked-alt"></i>
            </div>
            <div class="w3-rest">
                <input class="w3-input w3-border w3-round-large" name="txtEstado" type="text" maxlength="2" placeholder="Estado / UF (ex: SP)" value="<?php echo htmlspecialchars($estado); ?>">
            </div>
        </div>

        <!-- Botão Cadastrar -->
        <div class="w3-row w3-section">
            <div class="w3-center">
                <button type="submit" name="btnCadastrar" class="w3-button w3-block w3-margin-top w3-blue w3-round-large">
                    Cadastrar
                </button>
            </div>
        </div>

    </form>
</div>

</body>
</html>