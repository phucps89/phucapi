<?php

namespace App\Http\Controllers;

use App\Repositories\CategoryRepository;
use App\Services\Response\ResponseFacade;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    //
    public function reference(){
        $categories = CategoryRepository::getInstance()->all();
        return $this->response([
            'categories' => $categories
        ]);
    }

    /**
     * @param array|object|string|\Exception $data
     * @param int $code
     * @param null $message
     * @return mixed
     */
    protected function response($data, $code = Response::HTTP_OK, $message = null)
    {
        return ResponseFacade::send($data, $code, $message);
    }
}
