<?php

namespace Tests;

use App\Normalizer\ProviderResponseNormalizer;
use App\Provider\ProviderFactory;
use App\Provider\ProviderRepository;
use App\Service\AllProvidersUnavailableException;
use App\Service\DebtEvaluator;
use App\Service\DebtService;
use App\Service\PaymentSimulator;
use PHPUnit\Framework\TestCase;

class ProviderFallbackIntegrationTest extends TestCase
{
    private function makeService(): DebtService
    {
        return new DebtService(
            new ProviderRepository(ProviderFactory::createAll()),
            [new ProviderResponseNormalizer()],
            new PaymentSimulator(),
            new DebtEvaluator()
        );
    }

    public function testHappyPathReturnsDebtsForKnownPlate(): void
    {
        $result = $this->makeService()->execute('ABC1234');

        $this->assertSame('ABC1234', $result['placa']);
        $this->assertNotEmpty($result['debitos']);
    }

    public function testFallsBackToProviderBWhenProviderAFails(): void
    {
        // ZZZ9998 faz o Provider A lançar exceção; Provider B responde normalmente.
        $result = $this->makeService()->execute('ZZZ9998');

        $this->assertSame('ZZZ9998', $result['placa']);
        $this->assertSame([], $result['debitos'], 'Provider B deve retornar vazio para placa não cadastrada.');
    }

    public function testReturnsEmptyDebtsForUnknownPlate(): void
    {
        // Qualquer placa válida não cadastrada no MOCK_DATA retorna vazio.
        $result = $this->makeService()->execute('XYZ9999');

        $this->assertSame([], $result['debitos']);
        $this->assertSame('0.00', $result['resumo']['total_atualizado']);
    }

    public function testThrowsWhenAllProvidersFail(): void
    {
        $this->expectException(AllProvidersUnavailableException::class);

        // ZZZ9999 faz ambos os providers lançarem exceção.
        $this->makeService()->execute('ZZZ9999');
    }
}