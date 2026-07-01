<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Log\StructuredLogger;
use App\Normalizer\ProviderResponseNormalizer;
use App\Provider\ProviderFactory;
use App\Provider\ProviderRepository;
use App\Request\RequestValidationException;
use App\Service\AllProvidersUnavailableException;
use App\Service\DebtEvaluator;
use App\Service\DebtService;
use App\Service\PaymentSimulator;
use App\Service\UnknownDebtTypeException;
use App\Request\RequestValidator;

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$uri    = strtok($_SERVER['REQUEST_URI'], '?'); //ignora query string se vier

// Roteamento

// GET /debitos/{placa}
if ($method === 'GET' && preg_match('#^/debitos/([^/]+)$#', $uri, $matches)) {
    $placa = strtoupper(trim($matches[1]));

    $validator = new RequestValidator();
    $logger    = new StructuredLogger();

    // Reutiliza o mesmo validador de antes, montando o JSON esperado por ele
    try {
        $data = $validator->validate(json_encode(['placa' => $placa]));
    } catch (RequestValidationException $error) {
        $response = ['error' => $error->getErrorCode(), 'message' => $error->getMessage()];
        $logger->logSearch(null, $response);
        http_response_code(400);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $service = new DebtService(
        new ProviderRepository(ProviderFactory::createAll()),
        [new ProviderResponseNormalizer()],
        new PaymentSimulator(),
        new DebtEvaluator()
    );

    try {
        $result = $service->execute($data['placa']);
        $logger->logSearch($data['placa'], $result);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (UnknownDebtTypeException $error) {
        $response = ['error' => 'unknown_debt_type', 'type' => $error->getType()];
        $logger->logSearch($data['placa'], $response);
        http_response_code(422);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } catch (AllProvidersUnavailableException $error) {
        $response = ['error' => 'all_providers_unavailable'];
        $logger->logSearch($data['placa'], $response);
        http_response_code(503);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $error) {
        http_response_code(500);
        echo json_encode(['error' => 'internal_server_error'], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// Qualquer outra rota
http_response_code(404);
echo json_encode([
    'error'  => 'not_found',
    'routes' => [
        'GET /health'          => 'verifica se a API está no ar',
        'GET /debitos/{placa}' => 'consulta débitos de um veículo',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);