<?php
// 1. Token da Hugging Face
$token = "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";

// 2. Modelo
$modelo = "google/flan-t5-small";

// 3. Receber dados do front-end
$dadosRecebidos = json_decode(file_get_contents('php://input'), true);
$perguntaUsuario = $dadosRecebidos['pergunta'] ?? null;
$materiaEscolhida = $dadosRecebidos['materia'] ?? 'Matéria desconhecida';

// 4. Verificação
if (!$perguntaUsuario) {
    echo json_encode(["resposta" => "Erro: pergunta não enviada."]);
    exit;
}

// 5. Montar prompt
$prompt = "Matéria: $materiaEscolhida\nPergunta: $perguntaUsuario\nResposta:";

// 6. Configurar requisição
$url = "https://api-inference.huggingface.co/models/$modelo";

$cabecalhos = [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
];

$dadosEnvio = json_encode([
    "inputs" => $prompt
]);

// 7. Enviar requisição com cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $cabecalhos);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosEnvio);

$respostaBruta = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["resposta" => "Erro de conexão com a IA."]);
    exit;
}
curl_close($ch);

// 8. Interpretar a resposta
$respostaDecodificada = json_decode($respostaBruta, true);

if (isset($respostaDecodificada[0]['generated_text'])) {
    $textoFinal = trim($respostaDecodificada[0]['generated_text']);
} elseif (isset($respostaDecodificada['error'])) {
    $textoFinal = "Erro de IA: " . $respostaDecodificada['error'];
} else {
    $textoFinal = "Erro desconhecido ao interpretar a resposta.";
}

// 9. Retornar para o front-end
echo json_encode(["resposta" => $textoFinal]);
?>
