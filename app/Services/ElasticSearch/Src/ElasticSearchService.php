<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 6/14/2018
 * Time: 3:26 PM
 */

namespace App\Services\ElasticSearch\Src;


use App\Libraries\Helpers;
use App\Services\Validator\ValidationFacade;
use App\Validators\ElasticSearch\ElasticSearchQueryMappingValidator;
use App\Validators\ElasticSearch\ElasticSearchSortMappingValidator;
use Cviebrock\LaravelElasticsearch\Manager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @property Manager _elasticSearchManager
 */
class ElasticSearchService
{
    /**
     * ElasticSearchService constructor.
     * @param Manager $elasticSearchManager
     */
    public function __construct(Manager $elasticSearchManager)
    {
        $this->_elasticSearchManager = $elasticSearchManager;
    }

    public function ping($connectionName = 'default'){
        return $this->_elasticSearchManager->connection($connectionName)->ping();
    }

    /**
     * @param string $connectionName
     * @return \Elasticsearch\Client
     */
    public function getClient($connectionName = 'default'){
        return $this->_elasticSearchManager->connection($connectionName);
    }

    public function getIndexName($index)
    {
        $prefix = env('PREFIX_ELASTICSEARCH');
        if (!empty($prefix)) {
            $prefix .= '_';
        }
        return strtolower($prefix . $index);
    }

    public function transformDataOut(array $outData)
    {
        $result = [
            'rows' => []
        ];
        foreach ($outData['hits']['hits'] as $hit) {
            $result['rows'][] = $hit['_source'];
        }
        $result['total_record'] = $outData['hits']['total'];
        return $result;
    }

    public function formatPagination(array $outData, $page, $length)
    {
        $result = $this->transformDataOut($outData);
        $result['page'] = intval($page);
        $result['length'] = intval($length);
        $result['total_page'] = ceil($result['total_record'] / $length);
        return $result;
    }

    public function paginationMapping(array $queryElastic = [], Request $request = null)
    {
        if (empty($request)) {
            $request = Request::capture();
        }
        $page = $request->get('page', 1);
        $limit = $request->get(config('repository.pagination.params.limit'), config('repository.pagination.limit'));
        $query = [
            'from' => ($page - 1) * $limit,
            'size' => $limit,
        ];
        if (empty($queryElastic)) {
            return $query;
        }
        if (empty($queryElastic['body'])) {
            $queryElastic['body'] = [];
        }
        $queryElastic['body'] =  array_merge($queryElastic['body'], $query);
        return $queryElastic;
    }

    public function formatQueryMapping(array $queryMappings, array $queryElastic = [], Request $request = null)
    {
        if (empty($request)) {
            $request = Request::capture();
        }
        $query = [];
        foreach ($queryMappings as $key => $queryMapping) {
            try {
                ValidationFacade::validate($queryMapping, ElasticSearchQueryMappingValidator::class);
            } catch (ValidationException $e) {
                continue;
            }

            $requireRequest = Helpers::get($queryMapping, 'request', true);
            $dataKey = $request->get($key);
            if ($dataKey === null && $requireRequest) {
                continue;
            }

            $type = strtolower($queryMapping['type']);
            if(!empty($queryMapping['input'])) {
                $inputType = strtolower($queryMapping['input']);
            }
            else{
                $inputType = 'single';
            }
            switch ($type) {
                case 'match':
                    if($inputType == 'single') {
                        $operator = Helpers::get($queryMapping, 'operator', 'and');
                        $query[] = [
                            $type => [
                                $queryMapping['field'] => [
                                    'query'    => $dataKey,
                                    'operator' => $operator
                                ]
                            ]
                        ];
                    }
                    else if ($inputType == 'array'){
                        $boolShould = [];
                        if(!is_array($dataKey)){
                            $dataKey = [$dataKey];
                        }
                        foreach ($dataKey as $dataValue) {
                            $boolShould[] = [
                                $type => [
                                    $queryMapping['field'] => $dataValue
                                ]
                            ];
                        }
                        $query[] = [
                            'bool' => [
                                'should' => $boolShould
                            ]
                        ];
                    }
                    break;
                case 'query_string_wildcard':
                    if($inputType == 'single') {
//                        $operator = Helpers::get($queryMapping, 'operator', 'and');
                        $query[] = [
                            'query_string' => [
                                'default_field' => $queryMapping['field'],
                                'query'    => "*{$dataKey}*",
                            ]
                        ];
                    }
                    else if ($inputType == 'array'){
                        $boolShould = [];
                        if(!is_array($dataKey)){
                            $dataKey = [$dataKey];
                        }
                        foreach ($dataKey as $dataValue) {
                            $boolShould[] = [
                                'query_string' => [
                                    'default_field' => $queryMapping['field'],
                                    'query'    => "*{$dataValue}*",
                                ]
                            ];
                        }
                        $query[] = [
                            'bool' => [
                                'should' => $boolShould
                            ]
                        ];
                    }
                    break;
//                case 'bool':
//                    $operator = Helpers::get($queryMapping, 'operator', 'must');
//                    $queryType = Helpers::get($queryMapping, 'query', null);
//                    if (empty($queryType)) {
//                        throw new \Exception('Query property is required for field ' . $queryMapping['field']);
//                    }
//                    if (!is_array($dataKey)) {
//                        $dataKey = [$dataKey];
//                    }
//                    foreach ($dataKey as $value) {
//                        $query[$type][$operator][] = [
//                            $queryType => [
//                                $queryMapping['field'] => $value
//                            ]
//                        ];
//                    }
//                    break;
                case 'range':
                    $queryType = Helpers::get($queryMapping, 'query', null);
                    if (empty($queryType)) {
                        throw new \Exception('Query property is required for field ' . $queryMapping['field']);
                    }

                    $format = Helpers::get($queryMapping, 'format', null);
                    if (!empty($format)) {
                        $query[] = [
                            $type => [
                                $queryMapping['field'] => [
                                    $queryType => $dataKey,
                                    'format' => $format,
                                ]
                            ]
                        ];
                    }
                    else{
                        $query[] = [
                            $type => [
                                $queryMapping['field'] => [
                                    $queryType => $dataKey,
                                ]
                            ]
                        ];
                    }
                    break;
                case 'match_phrase':
                case 'match_phrase_prefix':
                    $query[] = [
                        $type => [
                            $queryMapping['field'] => $dataKey
                        ]
                    ];
                    break;
                case 'exists':
                    $query[] = [
                        $type => [
                            'field' => $queryMapping['field']
                        ]
                    ];
                    break;
                default:
                    $query[] = [
                        $type => [
                            $queryMapping['field'] => $dataKey
                        ]
                    ];
            }

        }

        if (empty($query)) {
            return $queryElastic;
        }
        if (empty($queryElastic)) {
            return $query;
        }
        $queryElastic['body']['query']['bool']['must'] = $query;
        return $queryElastic;
    }

    public function formatSortMapping(array $sortMappings, array $queryElastic = [], Request $request = null)
    {
        if (empty($request)) {
            $request = Request::capture();
        }

        $orderFieldKeyRaw = $request->get('order');
        if (empty($orderFieldKeyRaw)) {
            return $queryElastic;
        }

        $sortFieldKeyRaw = $request->get('sort');

        $orderFieldKeyArray = explode(',', $orderFieldKeyRaw);
        $sortFieldKeyArray = explode(',', $sortFieldKeyRaw);
        $query = [];
        foreach ($orderFieldKeyArray as $index => $orderKey) {
            if (empty($sortMappings[$orderKey])) {
                continue;
            }

            ValidationFacade::validate($sortMappings[$orderKey], ElasticSearchSortMappingValidator::class);
            $orderType = !empty($sortFieldKeyArray[$index]) ? $sortFieldKeyArray[$index] : 'asc';
            $orderType = strtolower($orderType);
            $fieldSort = $sortMappings[$orderKey]['field'];
            $modeSort = $sortMappings[$orderKey]['mode'] ?? null;

            $dataSort['order'] = $orderType;
            if (!empty($modeSort)) {
                $dataSort['mode'] = $modeSort;
            }
            $query[$fieldSort] = $dataSort;
        }

        if (empty($query)) {
            return $queryElastic;
        }
        if (empty($queryElastic)) {
            return $query;
        }
        $queryElastic['body']['sort'] = $query;
        return $queryElastic;
    }
}
