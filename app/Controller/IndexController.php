<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use App\Request\IndexRequest;
use App\Service\SmCryptorService;
use App\Service\WechatService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\Validation\Annotation\Scene;

#[Controller('/')]
class IndexController extends AbstractController
{
    public function __construct(protected WechatService $wechatService)
    {
    }

    #[RequestMapping(path: '/', methods: ['get']), Scene()]
    public function index(IndexRequest $request)
    {
        return $this->success();
    }

    #[RequestMapping(path: '/test', methods: ['post']), Scene()]
    public function test(IndexRequest $request)
    {
        $params = $this->request->all();
        return $this->success($params);
    }
}
