# Débitos Veiculares

Projeto em PHP para consulta e simulação de pagamento de débitos veiculares

## Estrutura

- `src/` - código da aplicação
- `src/Domain/` - modelos de domínio
- `src/Provider/` - provedores de dados e fallback
- `src/Normalizer/` - adaptadores para normalizar formatos de provedores
- `src/Service/` - regras de negócio e simulação de pagamento
- `public/index.php` - ponto de entrada da aplicação
- `composer.json` - autoload PSR-4
- `Dockerfile` - container para execução
- `docker-compose.yml` - orquestração do container com persistência de logs

## Conceitos usados

- SOLID
  - Classes com responsabilidade única.
  - Novos provedores podem ser adicionados sem modificar classes existentes.
  - Serviços dependem de abstrações, não de implementações.
  - Provedores e normalizadores estão separados.
  - Camada mais alta depende de interfaces em vez de classes concretas.

- Padrões de projeto
  - Factory: `ProviderFactory` cria providers por nome.
  - Adapter: normalizadores convertem formatos diferentes para o modelo canônico.
  - Strategy: cada provedor implementa `ProviderInterface` e pode ser trocado sem alterar o serviço.
  - Repository: `ProviderRepository` encapsula a ordem de fallback.

## Como construir

Precisa do Docker Desktop para rodar o projeto:
https://www.docker.com/products/docker-desktop/

Criar uma pasta com o nome de `debitos-veiculares`, entrar dentro da pasta e clonar o projeto:
git clone https://github.com/shindionodera/debitos-veiculares

Dentro do diretório do projeto:

```bash
docker build -t debitos-veiculares .
```

## Como executar

A aplicação registra logs estruturados em `src/Log/StructuredLogger.php`, salvos em `logs/search.log` dentro do container. Para que esses logs não sejam perdidos quando o container é removido, recomenda-se usar `docker-compose`, que monta a pasta `logs/` do host (sua máquina) com a pasta `logs/` do container.

### Usando docker-compose (recomendado)

No Linux/macOS:

```bash
docker compose run --rm debitos-veiculares < '{"placa": "ABC1234"}'
```

No Windows (PowerShell):

```powershell
'{"placa": "ABC1234"}' | docker compose run --rm debitos-veiculares
```

O arquivo de log estará disponível em `logs/search.log` na raiz do projeto, mesmo com o container sendo removido ao final da execução.

### Usando docker run diretamente

No Linux/macOS ou em shells compatíveis com redirecionamento padrão:

```bash
docker run --rm -v "$(pwd)/logs:/app/logs" debitos-veiculares < '{"placa": "ABC1234"}'
```

No Windows (PowerShell):

```powershell
'{"placa": "ABC1234"}' | docker run --rm -i -v ${PWD}/logs:/app/logs debitos-veiculares
```

O serviço lerá JSON da entrada padrão e retornará resultado JSON com débitos normalizados, juros e opções de pagamento.

## Como rodar os testes

Como o projeto é Dockerizado, não é necessário instalar dependências PHP localmente. Após construir a imagem, execute os testes dentro do container:
Para rodar um arquivo de teste específico dentro da pasta `tests`:

```bash
docker run --rm debitos-veiculares vendor/bin/phpunit --colors=never tests/DebtEvaluatorTest.php
```

Para rodar apenas uma classe de teste ou um método específico:

```bash
docker run --rm debitos-veiculares vendor/bin/phpunit --colors=never --filter DebtEvaluatorTest
```

```bash
docker run --rm debitos-veiculares vendor/bin/phpunit --colors=never --filter testIpvaInterestIsCappedAtTwentyPercent
```
Se você alterou o código, reconstrua a imagem antes de rodar os testes.

## Como adicionar novos provedores

Para incluir um novo provedor, crie uma nova classe em `src/Provider/` que implemente `ProviderInterface` e registre-a em `ProviderFactory::createAll()`. O serviço usa um normalizador genérico em `src/Normalizer/ProviderResponseNormalizer.php` para converter respostas JSON ou XML ao modelo canônico `VehicleDebts`.

Isso mantém a integração isolada da lógica de negócio: o provedor só busca dados brutos e o adaptador genérico cuida da normalização.

## Como adicionar novas regras de juros ou taxas

Para adicionar um novo tipo de cobrança, inclua a regra em `src/Service/DebtTypeRules.php`. As regras de débito são centralizadas em um único local com taxas por tipo, evitando um conjunto de classes por tipo de débito.

Novos tipos de débito podem ser suportados sem alterar o código de avaliação principal, apenas estendendo a tabela de regras.

## Observações

- Não há banco de dados.
- Provedores são simulados internamente.
- O serviço usa cálculo de juros simples e oferece simulação de pagamento via PIX e cartão de crédito.
- O projeto valida a placa e rejeita requisições inválidas com `400`.
- Quando todos os provedores falham, retorna `503`.
- Quando o tipo de débito não é suportado, retorna `422`.
- Os logs de busca são salvos em `logs/search.log`, com a placa parcialmente mascarada por privacidade. Use `docker-compose` ou a flag `-v` do `docker run` para persistir esse arquivo fora do container.

## Regras de negócio aplicadas

- Data fixa para o teste: `2024-05-10T00:00:00Z` (UTC).
- Juros simples aplicados por atraso.
- Arredondamento `HALF_UP` com 2 casas decimais em todos os valores monetários.
- Valores monetários de saída são enviados como strings decimais.
- IPVA: 0,33% ao dia com teto de 20% sobre o valor original.
- MULTA: 1,00% ao dia sem teto.
- Débitos não vencidos (dias de atraso <= 0) não recebem juros.
- PIX: desconto de 5% aplicado ao valor atualizado total e a cada pagamento parcial.
- Cartão de crédito: opções fixas `1x`, `6x` e `12x`, com amortização Price a 2,5% ao mês para 6x e 12x.
- Tipos de débito desconhecidos retornam erro `unknown_debt_type`.
- Placa validada para formatos Mercosul e antigo.
- Rejeita JSON com campos desconhecidos e corpo maior que 1 MiB.
- Retorna 503 quando todos os provedores falham.