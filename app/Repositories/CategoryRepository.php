<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 8/27/2018
 * Time: 1:36 PM
 */

namespace App\Repositories;


use App\Libraries\Helper;
use App\Models\Category;

class CategoryRepository extends BaseRepository
{

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        // TODO: Implement model() method.
        return Category::class;
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function getPage(){
        $table = $this->makeModel()->getTable();
        $querySearch = [
            'id' => [$table,'id','='],
            'name' => [$table,'name','LIKE'],
        ];
        $query = $this->makeModel()->newQuery();
        $query = Helper::searchFieldsMapping($query, $querySearch);
        $page = $query->paginate(Helper::getItemPerPage());
        return $page;
    }
}