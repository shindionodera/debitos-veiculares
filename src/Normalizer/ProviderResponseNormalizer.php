<?php

namespace App\Normalizer;

use App\Domain\Debt;
use App\Domain\VehicleDebts;

class ProviderResponseNormalizer implements ProviderNormalizerInterface
{
    public function supports(string $providerName): bool
    {
        return true;
    }

    public function normalize(string $providerResponse): VehicleDebts
    {
        $payload = $this->decodeJson($providerResponse);

        if ($payload !== null) {
            return $this->normalizeJson($payload);
        }

        return $this->normalizeXml($providerResponse);
    }

    private function decodeJson(string $payload): ?array
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    private function normalizeJson(array $payload): VehicleDebts
    {
        $vehicle = $payload['vehicle'] ?? '';
        $debts = [];

        foreach ($payload['debts'] ?? [] as $debt) {
            $debts[] = new Debt(
                $debt['type'], 
                (float) $debt['amount'], 
                $debt['due_date']
            );
        }

        return new VehicleDebts($vehicle, $debts);
    }

    private function normalizeXml(string $payload): VehicleDebts
    {
        $xml = simplexml_load_string($payload, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($xml === false) {
            throw new \RuntimeException('Resposta de provedor em formato inválido');
        }

        $vehicle = (string) ($xml->vehicle ?? '');
        $debts = [];

        if (isset($xml->debts->debt)) {
            foreach ($xml->debts->debt as $debtElement) {
                $debts[] = new Debt(
                    (string) $debtElement->type,
                    (float) $debtElement->amount,
                    (string) $debtElement->due_date
                );
            }
        }

        return new VehicleDebts($vehicle, $debts);
    }
}
