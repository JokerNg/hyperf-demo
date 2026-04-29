<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request\IndexRequest;
use App\Service\WechatService;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Validation\Annotation\Scene;
use Psr\Log\LoggerInterface;

#[Controller(prefix: 'index')]
class IndexController extends AbstractController
{
    public function __construct(
        protected WechatService $wechatService,
        protected LoggerInterface $logger
    )
    {
    }

    #[GetMapping(path: 'index'), Scene()]
    public function index(IndexRequest $request)
    {
        return $this->success();
    }

    #[PostMapping(path: 'test'), Scene()]
    public function test(IndexRequest $request)
    {
        $params = $this->request->all();
        return $this->success($params);
    }
}
