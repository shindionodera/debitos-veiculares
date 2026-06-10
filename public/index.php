<?php

require __DIR__ . '/../vendor/autoload.php';

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

try {
    $data = $validator->validate($input);
} catch (RequestValidationException $error) {
    http_response_code(400);
    echo json_encode([
        'error' => $error->getErrorCode(),
        'message' => $error->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
}

$repository = new ProviderRepository(ProviderFactory::createAll());
$normalizers = [new ProviderResponseNormalizer()];
$simulator = new PaymentSimulator();
$evaluator = new DebtEvaluator();
$service = new DebtService($repository, $normalizers, $simulator, $evaluator);

try {
    $result = $service->execute($data['placa']);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (UnknownDebtTypeException $error) {
    http_response_code(422);
    echo json_encode([
        'error' => 'unknown_debt_type',
        'type' => $error->getType(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
} catch (AllProvidersUnavailableException $error) {
    http_response_code(503);
    echo json_encode([
        'error' => 'all_providers_unavailable',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
} catch (\Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'internal_server_error',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
}
