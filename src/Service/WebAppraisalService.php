<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Service\Logs\LogService;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WebAppraisalService
{
    const OBJECT_TYPE = [
        "ROOM" => "Комната",
        "FLAT" => "Квартира",
        "HOUSE" => "Дом",
        "LAST_ROOM" => "Последняя комната",
        "TOWNHOUSE" => "Таунхаус",
        "APARTMENTS" => "Апартаменты",
        "FLAT_PART" => "Доля в квартире",
        "AUTO" => "Автомобиль",
        "PARKING_PLACE" => "Парковочное место",
        "BUSINESS" => "Бизнес",
        "COMMERCIAL" => "Коммерческая недвижимость",
        "COURT" => "Для суда",
        "EQUIPMENT" => "Оборудование",
        "INVESTMENT" => "Инвестиции",
        "LAND_PLOT" => "Земельный участок",
    ];

    const BUILDING_STATE = [
        "NEW" => "Новостройка",
        "BUILT" => "Дом построен",
    ];

    const DECORATION_TYPE = [
        "NO_DECORATION" => "Без отделки",
        "OVERHAUL_REQUIRED" => "Требуется капитальный ремонт",
        "FOR_FINISHING" => "Предчистовая отделка",
        "COSMETIC_REQUIRED" => "Требуется косметический ремонт",
        "GOOD" => "Хорошее состояние",
        "IMPROVED" => "Улучшенная отделка",
        "EURO" => "Евроремонт",
        "EXCLUSIVE" => "Эксклюзивный ремонт",
    ];

    const WALL_MATERIAL = [
        "PANEL" => "Панельный",
        "BLOCK" => "Блочный",
        "MONOLITHIC_BRICK" => "Монолитно-кирпичный",
        "BRICK" => "Кирпичный",
        "MONOLITHIC" => "Монолитный",
        "WOOD" => "Деревянный",
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private LogService $logService,
        private HttpClientInterface $httpClient,
        private CacheInterface $cacheInterface,
        private ContainerBagInterface $params,
    ) {
    }

    public function getAuthToken() : string
    {
        $authArray = $this->cacheInterface->get('webAppraisalAuth', function (CacheItemInterface $cacheItem) {
            $response = $this->httpClient->request('POST', $this->params->get('webAppraisalApiUrl') . 'oauth/token', 
            [
                'body' => [
                    'username' => 'fantomster@yandex.ru',
                    'client_id' => $this->params->get('webAppraisalId'),
                    'client_secret' => $this->params->get('webAppraisalSecret'),
                    'grant_type' => 'password'
                ]
            ]);
            $authArray = json_decode($response->getContent(), true);
            $cacheItem->expiresAfter($authArray['expires_in']);
            $authArray['expires_at'] = time() + $authArray['expires_in'];

            return $authArray;
        });
        
        if ($authArray['expires_at'] < time() + 600) {
            $this->cacheInterface->delete('webAppraisalAuth');
            $authArray = $this->cacheInterface->get('webAppraisalAuth', function (CacheItemInterface $cacheItem) use ($authArray) {
                $response = $this->httpClient->request('POST', $this->params->get('webAppraisalApiUrl') . 'oauth/token', 
                [
                    'body' => [
                        'refresh_token' => $authArray['refresh_token'],
                        'grant_type' => 'refresh_token'
                    ]
                ]);
                $authArray = json_decode($response->getContent(), true);
                $cacheItem->expiresAfter($authArray['expires_in']);
                $authArray['expires_at'] = time() + $authArray['expires_in'];
    
                return $authArray;
            });
        }

        return $authArray['access_token'];
    }

    public function checkInformation(string $query)
    {
        $response = $this->httpClient->request('GET', 'https://r.beta.webappraiser.ru/api/v1/calculations/checkInformation', 
            [
                'query' => [
                    'query' => $query,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAuthToken()
                ]
            ]);

        return json_decode($response->getContent());
    }

    public function calculate($data)
    {
        $response = $this->httpClient->request('POST', 'https://r.beta.webappraiser.ru/api/v1/calculations/calculate', 
            [
                'body' => json_encode($data),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAuthToken(),
                    'Content-type' => 'application/json',
                ]
            ]);

        return json_decode($response->getContent(false), true);
    }
}
