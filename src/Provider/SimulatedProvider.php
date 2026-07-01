<?php
namespace App\Provider;

use App\Provider\ProviderUnavailableException;

/**
 * Provider genérico e configurável que simula uma integração externa.
 * Instancie esta classe com os dados de cada provider no ProviderFactory.
 */
class SimulatedProvider implements ProviderInterface
{
    private const PLATE_ALL_PROVIDERS_FAIL = 'ZZZ9999';

    /**
     * @param string  $name          Nome do provider (ex: 'provider-c')
     * @param string  $format        Formato de saída: 'json' ou 'xml'
     * @param array   $mockData      Mapa de placa => lista de débitos
     * @param ?string $plateToFail   Placa que faz ESTE provider falhar (simula falha parcial)
     */
    public function __construct(
        private string  $name,
        private string  $format,
        private array   $mockData,
        private ?string $plateToFail = null
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function fetch(string $licensePlate): string
    {
        if ($licensePlate === self::PLATE_ALL_PROVIDERS_FAIL || $licensePlate === $this->plateToFail) {
            throw new ProviderUnavailableException($this->name . ' is unavailable for plate ' . $licensePlate);
        }

        $debts = $this->mockData[$licensePlate] ?? [];

        if ($this->format === 'xml') {
            return $this->toXml($licensePlate, $debts);
        }

        return json_encode(['vehicle' => $licensePlate, 'debts' => $debts], JSON_THROW_ON_ERROR);
    }

    private function toXml(string $licensePlate, array $debts): string
    {
        if (empty($debts)) {
            return sprintf('<response><vehicle>%s</vehicle><debts/></response>', $licensePlate);
        }

        $debtsXml = '';
        foreach ($debts as $debt) {
            $debtsXml .= sprintf(
                '<debt><type>%s</type><amount>%.2f</amount><due_date>%s</due_date></debt>',
                $debt['type'],
                $debt['amount'],
                $debt['due_date']
            );
        }

        return sprintf('<response><vehicle>%s</vehicle><debts>%s</debts></response>', $licensePlate, $debtsXml);
    }
}