<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 9/1/2018
 * Time: 5:56 PM
 */

namespace App\Repositories;


use App\Models\User;

class UserRepository extends BaseRepository
{

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        // TODO: Implement model() method.
        return User::class;
    }
}