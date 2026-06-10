<?php

namespace App\Service;

use App\Domain\VehicleDebts;
use App\Normalizer\ProviderNormalizerInterface;
use App\Provider\ProviderInterface;
use App\Provider\ProviderRepository;
use App\Provider\ProviderUnavailableException;
use App\Service\AllProvidersUnavailableException;

class DebtService
{
    /** @var ProviderNormalizerInterface[] */
    private array $normalizers;
    private ProviderRepository $repository;
    private PaymentSimulator $simulator;
    private DebtEvaluator $evaluator;

    public function __construct(ProviderRepository $repository, array $normalizers, PaymentSimulator $simulator, DebtEvaluator $evaluator)
    {
        $this->repository = $repository;
        $this->normalizers = $normalizers;
        $this->simulator = $simulator;
        $this->evaluator = $evaluator;
    }

    public function execute(string $licensePlate): array
    {
        $vehicleDebts = $this->fetchFromProviders($licensePlate);

        if ($vehicleDebts->totalAmount() === 0.0) {
            return [
                'placa' => $licensePlate,
                'debitos' => [],
                'resumo' => [
                    'total_original' => '0.00',
                    'total_atualizado' => '0.00',
                ],
                'pagamentos' => ['opcoes' => []],
            ];
        }

        $evaluation = $this->evaluator->evaluate($vehicleDebts);

        return [
            'placa' => $licensePlate,
            'debitos' => $evaluation['debts'],
            'resumo' => [
                'total_original' => $evaluation['total_original'],
                'total_atualizado' => $evaluation['total_updated'],
            ],
            'pagamentos' => $this->simulator->simulate($evaluation),
        ];
    }

    private function fetchFromProviders(string $licensePlate): VehicleDebts
    {
        $providers = $this->repository->all();
        $failedProviders = 0;

        /** @var ProviderInterface $provider */
        foreach ($providers as $provider) {
            try {
                $response = $provider->fetch($licensePlate);
                $normalizer = $this->findNormalizer($provider);
                $vehicleDebts = $normalizer->normalize($response);

                if ($vehicleDebts->totalAmount() > 0.0) {
                    return $vehicleDebts;
                }
            } catch (ProviderUnavailableException $error) {
                $failedProviders++;
                continue;
            } catch (\Throwable $error) {
                continue;
            }
        }

        if ($failedProviders === count($providers)) {
            throw new AllProvidersUnavailableException('All providers failed.');
        }

        return new VehicleDebts($licensePlate, []);
    }

    private function findNormalizer(ProviderInterface $provider): ProviderNormalizerInterface
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($provider->getName())) {
                return $normalizer;
            }
        }

        throw new \RuntimeException('Nenhum normalizador disponível para ' . $provider->getName());
    }
}
