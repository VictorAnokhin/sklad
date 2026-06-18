<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NovaPoshtaController extends Controller
{
    private function requestNovaPoshta(string $modelName, string $calledMethod, array $methodProperties): array
    {
        $response = Http::acceptJson()
            ->timeout((int) config('services.nova_poshta.timeout', 10))
            ->post((string) config('services.nova_poshta.endpoint'), [
                'apiKey' => (string) config('services.nova_poshta.api_key', ''),
                'modelName' => $modelName,
                'calledMethod' => $calledMethod,
                'methodProperties' => $methodProperties,
            ]);

        if (!$response->ok()) {
            abort(502, 'Нова пошта тимчасово недоступна');
        }

        $payload = $response->json();
        if (!is_array($payload) || ($payload['success'] ?? false) !== true) {
            $errors = $payload['errors'] ?? [];
            $message = is_array($errors) && count($errors) > 0
                ? implode('; ', array_map('strval', $errors))
                : 'Не вдалося отримати дані Нової пошти';

            abort(502, $message);
        }

        return $payload;
    }

    public function cities(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['items' => []]);
        }

        $limit = max(1, min((int) $request->query('limit', 20), 50));
        $payload = $this->requestNovaPoshta('AddressGeneral', 'searchSettlements', [
            'CityName' => $query,
            'Limit' => (string) $limit,
            'Page' => '1',
        ]);

        $addresses = $payload['data'][0]['Addresses'] ?? [];
        $items = collect(is_array($addresses) ? $addresses : [])
            ->map(function ($item) {
                $name = trim((string) ($item['MainDescription'] ?? ''));
                $present = trim((string) ($item['Present'] ?? $name));
                $area = trim((string) ($item['Area'] ?? ''));
                $region = trim((string) ($item['Region'] ?? ''));
                $deliveryCityRef = trim((string) ($item['DeliveryCity'] ?? ''));
                $ref = trim((string) ($item['Ref'] ?? ''));

                return [
                    'ref' => $ref,
                    'delivery_city_ref' => $deliveryCityRef !== '' ? $deliveryCityRef : $ref,
                    'name' => $name !== '' ? $name : $present,
                    'present' => $present,
                    'area' => $area,
                    'region' => $region,
                    'settlement_type' => (string) ($item['SettlementTypeCode'] ?? ''),
                    'warehouses' => (int) ($item['Warehouses'] ?? 0),
                ];
            })
            ->filter(fn ($item) => $item['name'] !== '' && $item['delivery_city_ref'] !== '')
            ->values();

        return response()->json(['items' => $items]);
    }

    public function warehouses(Request $request)
    {
        $cityRef = trim((string) $request->query('city_ref', ''));
        if ($cityRef === '') {
            return response()->json(['items' => []]);
        }

        $query = trim((string) $request->query('q', ''));
        $limit = max(1, min((int) $request->query('limit', 50), 100));
        $properties = [
            'CityRef' => $cityRef,
            'Limit' => (string) $limit,
            'Page' => '1',
            'Language' => 'UA',
        ];
        if ($query !== '') {
            $properties['FindByString'] = $query;
        }

        $payload = $this->requestNovaPoshta('AddressGeneral', 'getWarehouses', $properties);
        $items = collect($payload['data'] ?? [])
            ->map(function ($item) {
                $description = trim((string) ($item['Description'] ?? $item['DescriptionRu'] ?? ''));
                $number = trim((string) ($item['Number'] ?? ''));

                return [
                    'ref' => (string) ($item['Ref'] ?? ''),
                    'number' => $number,
                    'description' => $description,
                    'short_address' => (string) ($item['ShortAddress'] ?? ''),
                    'type' => (string) ($item['TypeOfWarehouse'] ?? ''),
                    'label' => Str::of($description !== '' ? $description : ('Відділення ' . $number))->squish()->toString(),
                ];
            })
            ->filter(fn ($item) => $item['ref'] !== '' && $item['label'] !== '')
            ->values();

        return response()->json(['items' => $items]);
    }
}
