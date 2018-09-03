<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 9/3/2018
 * Time: 2:44 PM
 */

namespace App\Http\Controllers;


use App\Repositories\CategoryRepository;

class CategoryController extends Controller
{
    function list(){
        $list = CategoryRepository::getInstance()->getPage();
        return $this->response($list);
    }
}