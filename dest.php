<?php

/* -------------------------------------
GERAR ARQUIVO DE IMPORTAÇÃO DESTINATARIOS SIGEP WEB

HOMOLOGADO: PHP 8.0.12

DRIVERS MICROSOFT PARA PDO / MSSQL
https://docs.microsoft.com/pt-br/sql/connect/php/download-drivers-php-sql-server?view=sql-server-ver15
GDrive:\ACDM\PHP\Drivers PDO Microsoft SQL Server

INCLUÍDOS DRIVERS NO DIRETÓRIO
\PHP\ext
    php_pdo_sqlsrv_80_nts_x64.dll
    php_sqlsrv_80_nts_x64.dll

CONFIG EM PHP.INI
    ;extension=pdo_sqlite
    extension=php_pdo_sqlsrv_80_nts_x64.dll
    ;extension=php_sqlsrv_80_nts_x64.dll 

HABILITADO MBSTRING EM PHP.INI, PARA USO DE mb_strlen. 
strlen, retorna valor incorreto para caracteres acentuados.
    extension=mbstring

EXEMPLO CONSULTA // VIEW SQL
    SELECT * FROM CIGAM.CIGAM.EXPORTACAO_ETIQUETA AS ETIQUETA WHERE NF > '168350' AND NF < '168450' ORDER BY Nf;

------------------------------------- */ 

require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// VARIAVEIS CONEXÃO :: DOTENV
$DB_DRIVER = $_ENV['DB_DRIVER'];
$DB_HOST = $_ENV['DB_HOST'];
$DB_NAME = $_ENV['DB_NAME'];
$DB_USER = $_ENV['DB_USER'];
$DB_PASSWORD = $_ENV['DB_PASSWORD'];

// FUNÇÕES
// ADICIONA ESPAÇOS NO FINAL DA STRING
function addSpaces($campo, $totalSpaces)
{
    if(mb_strlen($campo, 'utf8') >= $totalSpaces) return substr($campo, 0, $totalSpaces);
    $qtdSpaces = $totalSpaces - mb_strlen($campo, 'utf8'); 
    for ($x = 0;$x < $qtdSpaces;$x++) $campo = $campo . ' ';
    return $campo;
}

// ADICIONA ESPAÇOS A ESQUERDA, PARA NUMEROS
function addPrefix($campo, $totalSpaces)
{
    if(mb_strlen($campo, 'utf8') >= $totalSpaces) return substr($campo, 0, $totalSpaces);
    $qtdSpaces = $totalSpaces - mb_strlen($campo, 'utf8'); 
    $new = '';
    for ($x = 0;$x < $qtdSpaces;$x++) $new .= ' ';
    $campo = $new . $campo;
    return $campo;
}

// SAUDAÇÃO PROGRAMA
echo "========================================================\n";
echo "========================================================\n";
echo "GERAR ARQUIVO PARA IMPORTAR REMETENTES CIGAM -> SIGEPWEB\n";
echo "VERSÃO 1.0 DE 26/01/2022\n";
echo "\n";

// TESTE
//$nf_inicial = "168363";
//$nf_final = "168373";

// PROMPT
echo "DE NOTA FISCAL: ";
$nf_inicial = trim(fgets(STDIN));
echo "\n";

echo "ATÉ NOTA FISCAL: ";
$nf_final = trim(fgets(STDIN));
echo "\n";

// CONFIG CONEXÃO COM O BANCO
$pdoConfig  = $DB_DRIVER . ":". "Server=" . $DB_HOST . ";";
$pdoConfig .= "Database=". $DB_NAME.";";

// CONEXÃO COM O BANCO
try {
    $connection = new PDO($pdoConfig, $DB_USER, $DB_PASSWORD);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $mensagem = "Drivers disponiveis: " . implode(",", PDO::getAvailableDrivers());
    $mensagem .= "\nErro: " . $e->getMessage();
    throw new Exception($mensagem);
}

// SELECT
$query = $connection->query(
        "SELECT Nf,
                CNPJ_CPF,
                Nome,
                EMAIL,
                DEPENDENCIA,
                Contato,
                Cep,
                END_ENTREGA,
                NUMERO_ENTREGA,
                COMPLEMENTO_ENTREGA,
                BAIRRO_ENTREGA,
                MUNICIPIO_ENTREGA,
                FONE,
                FONE_FAX
        FROM CIGAM.CIGAM.EXPORTACAO_ETIQUETA AS ETIQUETA WHERE Nf >= '" . $nf_inicial . "' AND Nf <= '" . $nf_final . "' ORDER BY Nf;");
 
$etiquetas = $query->fetchAll();

// MONTAGEM DO ARQUIVO
$file = '';
$file = "1SIGEP DESTINATARIO NACIONAL\r\n";
$number_of_lines = 0;

// DESTINATARIOS // ETIQUETAS
foreach($etiquetas as $etiqueta ) {
    // NÚMERO REGISTRO - FIXO EM "2"
    $file .= '2';
    // CNPJ/CPF
    $cnpj_cpf = trim($etiqueta['CNPJ_CPF']);
    $cnpj_cpf = addPrefix($cnpj_cpf, 14);
    $file .= $cnpj_cpf;
    // NOME
    $nome = trim($etiqueta['Nome']);
    $nome = addSpaces($nome, 50);
    $file .= $nome;
    // EMAIL
    $email = trim($etiqueta['EMAIL']);
    $email = addSpaces($email, 50);
    $file .= $email;
    // AOS CUIDADOS // DEPENDENCIA
    $ac = trim($etiqueta['DEPENDENCIA']);
    $ac = addSpaces($ac, 50);
    $file .= $ac;
    // CONTATO
    $contato = trim($etiqueta['Contato']);
    $contato = addSpaces($contato, 50);
    $file .= $contato;
    // CEP
    $cep = trim($etiqueta['Cep']);
    $cep = addPrefix($cep, 8);
    $file .= $cep;
    // Logradouro // Endereço de Entrega
    $logradouro = trim($etiqueta['END_ENTREGA']);
    $logradouro = addSpaces($logradouro, 50);
    $file .= $logradouro;
    // Numero
    $numero = trim($etiqueta['NUMERO_ENTREGA']);
    $numero = addSpaces($numero, 6);
    $file .= $numero;
    // Complemento
    $complemento = trim($etiqueta['COMPLEMENTO_ENTREGA']);
    $complemento = addSpaces($complemento, 30);
    $file .= $complemento;
    // BAIRRO
    $bairro = trim($etiqueta['BAIRRO_ENTREGA']);
    $bairro = addSpaces($bairro, 50);
    $file .= $bairro;
    // MUNICIPIO // CIDADE
    $municipio = trim($etiqueta['MUNICIPIO_ENTREGA']);
    $municipio = addSpaces($municipio, 50);
    $file .= $municipio;

    // TELEFONE
    // TELEFONE COM BUG - NO LEIAUTE CAMPO RESERVA 12 POSIÇÕES
    // PLANILHA DOS CORREIOS ALINHA A ESQUERDA
    // IMPORTA MAS GERA ERRO NO LOG E IMPORTA FALTANDO O ULTIMO CARACTERE
    // POR HORA, GRAVAR ESPAÇOS EM BRANCOS PARA FONE, FAX E CELULAR

    //$telefone = trim($etiqueta['FONE']);
    //$telefone = preg_replace('/[^0-9]+/', '', $telefone);
    //$telefone = addPrefix($telefone, 12);

    $telefone = '';
    $telefone = addSpaces($telefone, 109);
    $file .= $telefone;

    // NOVA LINHA
    $file .= "\r\n";
    
    // CONTA O NÚMERO DE LINHAS
    $number_of_lines += 1;
}

// CONTAGEM E CRIAÇÃO STRING DA LINHA FINAL
if(!(strlen($number_of_lines) >= 6)) {
    $qtd = 6 - strlen($number_of_lines); 
    $new = '';
    for ($x = 0;$x < $qtd;$x++) $new .= '0';
    $number_of_lines = $new . $number_of_lines;
}

// LINHA FINAL - INFORMANDO NUMERO DE REGISTROS
$file .= '9' . $number_of_lines . "\r\n";

// GRAVA ARQUIVO
file_put_contents('destinatarios.txt', $file);

echo PHP_EOL;
echo "Arquivo destinatarios.txt gravado!";
echo PHP_EOL;