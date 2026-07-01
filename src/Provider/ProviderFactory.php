<?php
namespace App\Provider;

class ProviderFactory
{
    /** @return ProviderInterface[] */
    public static function createAll(): array
    {
        return [
            new SimulatedProvider(
                name: 'provider-a',
                format: 'json',
                mockData: [
                    'ABC1234' => [
                        //['type' => 'IPVA',  'amount' => 1500.00, 'due_date' => '2024-01-10'],
                        //['type' => 'MULTA', 'amount' => 300.50,  'due_date' => '2024-02-15'],
                        ['type' => 'IPVA',  'amount' => 1750.00, 'due_date' => '2024-02-10'],
                        ['type' => 'MULTA', 'amount' => 400.00,  'due_date' => '2024-03-15'],
                    ],
                ],
                plateToFail: 'ZZZ9998'
            ),
            new SimulatedProvider(
                name: 'provider-b',
                format: 'xml',
                mockData: [
                    'ABC1234' => [
                        ['type' => 'IPVA',  'amount' => 1500.00, 'due_date' => '2024-01-10'],
                        ['type' => 'MULTA', 'amount' => 300.50,  'due_date' => '2024-02-15'],
                    ],
                ],
            ),            
            // new SimulatedProvider(
            //     name: 'provider-c',
            //     format: 'json',
            //     mockData: [
            //         'ABC1234' => [
            //             ['type' => 'IPVA',  'amount' => 1500.00, 'due_date' => '2024-01-10'],
            //             ['type' => 'MULTA', 'amount' => 300.50,  'due_date' => '2024-02-15'],
            //         ],
            //     ],
            // ),
        ];
    }
}