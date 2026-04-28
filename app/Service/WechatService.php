<?php

declare(strict_types=1);

namespace App\Service;

use EasyWeChat\MiniApp\Application as MiniAppApplication;
use EasyWeChat\OfficialAccount\Application as OfficialAccountApplication;
use EasyWeChat\Pay\Application as PayApplication;
use Hyperf\Contract\ConfigInterface;
use RuntimeException;

class WechatService
{
    private ConfigInterface $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * 获取公众号应用实例.
     *
     * @param string $name 配置名称，默认 default
     */
    public function officialAccount(string $name = 'default'): OfficialAccountApplication
    {
        $config = $this->config->get("wechat.official_account.{$name}");

        if (empty($config)) {
            throw new RuntimeException("WeChat official account config [{$name}] not found.");
        }

        return new OfficialAccountApplication($config);
    }

    /**
     * 获取小程序应用实例.
     *
     * @param string $name 配置名称，默认 default
     */
    public function miniApp(string $name = 'default'): MiniAppApplication
    {
        $config = $this->config->get("wechat.mini_app.{$name}");

        if (empty($config)) {
            throw new RuntimeException("WeChat mini app config [{$name}] not found.");
        }

        return new MiniAppApplication($config);
    }

    /**
     * 获取微信支付应用实例.
     *
     * @param string $name 配置名称，默认 default
     */
    public function pay(string $name = 'default'): PayApplication
    {
        $config = $this->config->get("wechat.pay.{$name}");

        if (empty($config)) {
            throw new RuntimeException("WeChat pay config [{$name}] not found.");
        }

        return new PayApplication($config);
    }
}
