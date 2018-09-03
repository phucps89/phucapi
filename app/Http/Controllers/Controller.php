<?php

namespace App\Http\Controllers;

use App\Repositories\CategoryRepository;
use App\Services\Response\ResponseFacade;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller as BaseController;
use Tymon\JWTAuth\Facades\JWTAuth;

class Controller extends BaseController
{
    //
    public function reference(){
        $categories = CategoryRepository::getInstance()->all();
        return $this->response([
            'categories' => $categories
        ]);
    }

    public function server(){
        ob_start();
        phpinfo();
        $pinfo = ob_get_contents();
        ob_end_clean();

        $pinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$pinfo);
        return $this->response([
            'server' => $pinfo
        ]);
    }

    function user(){
        return $this->response(JWTAuth::parseToken()->authenticate());
    }

    function logout(Request $request){
        JWTAuth::invalidate(JWTAuth::getToken());
        return \App\Services\Response\ResponseFacade::send('Logout successfully');
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
