<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 6/14/2018
 * Time: 3:26 PM
 */

namespace App\Services\ElasticSearch;


use App\Services\ElasticSearch\Src\ElasticSearchService;
use Elasticsearch\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * Class ElasticSearchFacade
 *
 * @method static bool ping(string $connectionName = 'default')
 * @method static Client getClient(string $connectionName = 'default')
 * @method static array formatQueryMapping(array $queryMappings, array $queryElastic = [], Request $request = null)
 * @method static array formatSortMapping(array $sortMappings, array $queryElastic = [], Request $request = null)
 * @method static string getIndexName(string $index)
 * @method static array paginationMapping(array $queryElastic = [], Request $request = null)
 * @method static array transformDataOut(array $elasticResult)
 * @method static array formatPagination(array $outData, $page, $length)
 *
 * @package App\Services\ElasticSearch
 */
class ElasticSearchFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ElasticSearchService::class;
    }
}