<?php
// 1. Token da Hugging Face (idealmente, use uma variável de ambiente)
$token = getenv("HF_TOKEN") ?: "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";

// 2. Modelo utilizado
$modelo = "google/flan-t5-small";

// 3. Receber dados JSON do front-end
$inputRaw = file_get_contents('php://input');
$dadosRecebidos = json_decode($inputRaw, true);

// 4. Extrair pergunta e matéria
$perguntaUsuario = $dadosRecebidos['pergunta'] ?? null;
$materiaEscolhida = $dadosRecebidos['materia'] ?? 'Matéria desconhecida';

// 5. Validar entrada
if (!$perguntaUsuario) {
    http_response_code(400);
    echo json_encode(["erro" => "Pergunta não enviada."]);
    exit;
}

// 6. Criar prompt para o modelo
$prompt = "Matéria: $materiaEscolhida\nPergunta: $perguntaUsuario\nResposta:";

// 7. Preparar chamada à API Hugging Face
$url = "https://api-inference.huggingface.co/models/" . urlencode($modelo);
$cabecalhos = [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
];
$dadosEnvio = json_encode([
    "inputs" => $prompt
]);

// 8. Enviar requisição com cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $cabecalhos,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $dadosEnvio
]);

$respostaBruta = curl_exec($ch);

// 9. Verificar erros de cURL
if (curl_errno($ch)) {
    $erroCurl = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(["erro" => "Erro de conexão com a IA: $erroCurl"]);
    exit;
}

curl_close($ch);

// 10. Interpretar resposta da API
$respostaDecodificada = json_decode($respostaBruta, true);
$textoFinal = "Erro desconhecido ao interpretar a resposta.";

if (isset($respostaDecodificada[0]['generated_text'])) {
    $textoFinal = trim($respostaDecodificada[0]['generated_text']);
} elseif (isset($respostaDecodificada['error'])) {
    $textoFinal = "Erro de IA: " . $respostaDecodificada['error'];
}

// 11. Enviar resposta final para o front-end
echo json_encode(["resposta" => $textoFinal]);
?>
