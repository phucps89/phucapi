<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 8/27/2018
 * Time: 9:08 AM
 */

namespace App\Repositories;


abstract class BaseRepository extends \Prettus\Repository\Eloquent\BaseRepository
{
    /**
     * @return static
     */
    public static function getInstance(){
        return app(static::class);
    }
}