<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Log\StructuredLogger;
use App\Normalizer\ProviderResponseNormalizer;
use App\Provider\ProviderFactory;
use App\Provider\ProviderRepository;
use App\Request\RequestValidator;
use App\Request\RequestValidationException;
use App\Service\AllProvidersUnavailableException;
use App\Service\DebtEvaluator;
use App\Service\DebtService;
use App\Service\PaymentSimulator;
use App\Service\UnknownDebtTypeException;

$input = file_get_contents('php://stdin');
$validator = new RequestValidator();
$logger = new StructuredLogger();

try {
    $data = $validator->validate($input);
} catch (RequestValidationException $error) {
    $response = [
        'error' => $error->getErrorCode(),
        'message' => $error->getMessage(),
    ];

    $logger->logSearch(null, $response);

    http_response_code(400);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
}

$repository = new ProviderRepository(ProviderFactory::createAll());
$normalizers = [new ProviderResponseNormalizer()];
$simulator = new PaymentSimulator();
$evaluator = new DebtEvaluator();
$service = new DebtService($repository, $normalizers, $simulator, $evaluator);

try {
    $result = $service->execute($data['placa']);
    $logger->logSearch($data['placa'], $result);

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (UnknownDebtTypeException $error) {
    $response = [
        'error' => 'unknown_debt_type',
        'type' => $error->getType(),
    ];

    $logger->logSearch($data['placa'], $response);

    http_response_code(422);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
} catch (AllProvidersUnavailableException $error) {
    $response = [
        'error' => 'all_providers_unavailable',
    ];

    $logger->logSearch($data['placa'], $response);

    http_response_code(503);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
} catch (\Throwable $error) {
    $response = [
        'error' => 'internal_server_error',
    ];

    $logger->logSearch($data['placa'], $response);

    http_response_code(500);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
}
