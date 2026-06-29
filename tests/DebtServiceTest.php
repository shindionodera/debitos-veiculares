<?php
namespace Tests;

use App\Domain\VehicleDebts;
use App\Domain\Debt;
use App\Normalizer\ProviderNormalizerInterface;
use App\Provider\ProviderInterface;
use App\Provider\ProviderRepository;
use App\Provider\ProviderUnavailableException;
use App\Service\AllProvidersUnavailableException;
use App\Service\DebtEvaluator;
use App\Service\DebtService;
use App\Service\PaymentSimulator;
use PHPUnit\Framework\TestCase;

class DebtServiceTest extends TestCase
{
    private function makeNormalizer(): ProviderNormalizerInterface
    {
        return new class implements ProviderNormalizerInterface {
            public function supports(string $providerName): bool { return true; }
            public function normalize(string $response): VehicleDebts
            {
                $data = json_decode($response, true);
                $debts = array_map(function ($debtData) {
                    return new Debt($debtData['type'], $debtData['amount'], $debtData['due_date']);
                }, $data['debts'] ?? []);

                return new VehicleDebts('ABC1234', $debts);
            }
        };
    }

    private function makeProvider(string $name, \Closure $fetch): ProviderInterface
    {
        return new class($name, $fetch) implements ProviderInterface {
            public function __construct(private string $n, private \Closure $fn) {}
            public function getName(): string { return $this->n; }
            public function fetch(string $plate): string { return ($this->fn)($plate); }
        };
    }

    private function makeService(array $providers): DebtService
    {
        return new DebtService(
            new ProviderRepository($providers),
            [$this->makeNormalizer()],
            new PaymentSimulator(),
            new DebtEvaluator()
        );
    }

    public function testFallsBackToNextProviderWhenFirstFails(): void
    {
        $providerA = $this->makeProvider('a', function () {
            throw new ProviderUnavailableException('down');
        });
        $providerB = $this->makeProvider('a', function () {
            return json_encode(['debts' => [['type' => 'IPVA', 'amount' => 1000.00, 'due_date' => '2024-05-10']]]);
        });

        $result = $this->makeService([$providerA, $providerB])->execute('ABC1234');

        $this->assertSame('1000.00', $result['resumo']['total_original']);
    }

    public function testThrowsWhenAllProvidersFail(): void
    {
        $this->expectException(AllProvidersUnavailableException::class);

        $fail = $this->makeProvider('a', function () {
            throw new ProviderUnavailableException('down');
        });

        $this->makeService([$fail, $fail])->execute('ABC1234');
    }

    public function testReturnsEmptyDebtsWhenProviderRespondsWithNoDebts(): void
    {
        $provider = $this->makeProvider('a', function () {
            return json_encode(['debts' => []]);
        });

        $result = $this->makeService([$provider])->execute('ABC1234');

        $this->assertSame([], $result['debitos']);
        $this->assertSame('0.00', $result['resumo']['total_original']);
    }

    public function testDoesNotCallNextProviderWhenFirstSucceedsWithEmptyDebts(): void
    {
        $calledB = false;

        $providerA = $this->makeProvider('a', fn() => json_encode(['debts' => []]));
        $providerB = $this->makeProvider('a', function () use (&$calledB) {
            $calledB = true;
            return json_encode(['debts' => []]);
        });

        $this->makeService([$providerA, $providerB])->execute('ABC1234');

        $this->assertFalse($calledB, 'Provider B não deve ser chamado se Provider A respondeu com sucesso.');
    }
}