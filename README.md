# Hyperf Demo

基于官方 [hyperf/hyperf-skeleton](https://github.com/hyperf/hyperf-skeleton) 骨架的扩展项目，在保留 Hyperf 核心能力的基础上，集成了统一响应格式、XSS 过滤、跨域处理、国密算法、微信 SDK 等常用功能组件，可作为中大型项目的起步模板。

---

# 环境要求

Hyperf 对系统环境有一定要求，它只能在 Linux 和 Mac 环境下运行，但由于 Docker 虚拟化技术的发展，在 Windows 环境下也可以使用 Docker for Windows 作为运行环境。

各版本的 Dockerfile 已在 [hyperf/hyperf-docker](https://github.com/hyperf/hyperf-docker) 项目中准备就绪，或直接基于已构建的 [hyperf/hyperf](https://hub.docker.com/r/hyperf/hyperf) 镜像运行。

当不使用 Docker 作为运行环境基础时，需要确保运行环境满足以下要求：

- PHP >= 8.3
- 以下任一网络引擎
  - Swoole PHP 扩展 >= 5.0，且在 `php.ini` 中设置 `swoole.use_shortname = Off`
  - Swow PHP 扩展 >= 1.3
- JSON PHP 扩展
- Pcntl PHP 扩展
- OpenSSL PHP 扩展（如需使用 HTTPS）
- PDO PHP 扩展（如需使用 MySQL 客户端）
- Redis PHP 扩展（如需使用 Redis 客户端）
- Protobuf PHP 扩展（如需使用 gRPC 服务端或客户端）

---

# 安装

## 通过 Composer 安装

```bash
composer create-project hyperf/hyperf-skeleton path/to/install
```

若开发环境基于 Docker，可使用官方 Composer 镜像：

```bash
docker run --rm -it -v $(pwd):/app composer create-project --ignore-platform-reqs hyperf/hyperf-skeleton path/to/install
```

## 依赖说明

本项目在官方骨架基础上新增以下依赖：

| 依赖包 | 说明 |
|--------|------|
| `oh86/sm_cryptor` | 国密 SM2/SM3/SM4 算法库 |
| `w7corp/easywechat` | 微信官方账号/小程序/支付 SDK |

---

# 快速启动

```bash
cd path/to/install
php bin/hyperf.php start
```

或使用项目提供的 `docker-compose.yml`：

```bash
cd path/to/install
docker-compose up
```

默认启动在 `9501` 端口，访问 `http://localhost:9501/` 即可看到默认首页。

---

# 新增功能一览

## 1. ResponseTrait — 统一 API 响应格式

**文件位置：** [`app/Trait/ResponseTrait.php`](app/Trait/ResponseTrait.php)

所有控制器通过引入该 Trait，可快速返回结构统一的 JSON 响应：

```php
namespace App\Controller;

use App\Trait\ResponseTrait;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller('/api')]
class UserController
{
    use ResponseTrait;

    #[RequestMapping(path: '/info', methods: ['get'])]
    public function info()
    {
        // 成功响应
        return $this->success(['id' => 1, 'name' => 'hyperf']);

        // 失败响应
        return $this->error('参数错误', 400);
    }
}
```

**响应格式：**

```json
{
  "code": 200,
  "message": "Success",
  "data": {"id": 1, "name": "hyperf"}
}
```

| 方法 | 说明 |
|------|------|
| `success($data, $message, $code)` | 成功响应，默认 `code = 200` |
| `error($message, $code, $data)` | 失败响应，默认 `code = 500` |

---

## 2. AppException & 异常处理器

### 2.1 AppException — 业务异常

**文件位置：** [`app/Exception/AppException.php`](app/Exception/AppException.php)

用于抛出可预知的业务错误，默认错误码 `10001`：

```php
use App\Exception\AppException;

throw new AppException('用户不存在', 10004);
```

### 2.2 AppExceptionHandler — 全局异常处理

**文件位置：** [`app/Exception/Handler/AppExceptionHandler.php`](app/Exception/Handler/AppExceptionHandler.php)

- 捕获 `AppException` 时，返回 `ResponseTrait::error()` 格式的 JSON 响应，客户端可拿到业务错误码与提示信息。
- 捕获其他异常时，记录错误日志；若请求为 JSON 类型，同样返回统一格式的 JSON 错误响应，否则返回纯文本 `Internal Server Error.`。
- JSON 请求判定：同时检测 `Accept` 和 `Content-Type` 头是否包含 `application/json`。

**注册位置：** [`config/autoload/exceptions.php`](config/autoload/exceptions.php)

---

## 3. ValidationExceptionHandler — 表单验证异常处理

**文件位置：** [`app/Exception/Handler/ValidationExceptionHandler.php`](app/Exception/Handler/ValidationExceptionHandler.php)

继承自官方 `Hyperf\Validation\ValidationExceptionHandler`，增强点如下：

| 特性 | 官方处理器 | 自定义处理器 |
|------|-----------|-------------|
| 响应格式 | 固定 `text/plain` | JSON 请求返回 `application/json`，其余保持 `text/plain` |
| JSON 检测 | 不支持 | 检测 `Accept` + `Content-Type` 双头部 |
| 错误结构 | 直接输出文本 | JSON 请求使用 `ResponseTrait::error()` 返回统一格式 |
| 依赖管理 | 无容器注入 | 通过构造函数注入 `ContainerInterface` |

**使用示例：**

当请求头包含 `Accept: application/json` 且验证失败时：

```json
{
  "code": 422,
  "message": "用户名不能为空",
  "data": {}
}
```

**注册位置：** [`config/autoload/exceptions.php`](config/autoload/exceptions.php)

---

## 4. CleanXssMiddleware — XSS 过滤中间件

**文件位置：** [`app/Middleware/CleanXssMiddleware.php`](app/Middleware/CleanXssMiddleware.php)

自动对请求的 `Query` 参数和 `POST` 参数进行 XSS 过滤：

- 移除 `<script>...</script>`、`<style>...</style>`、HTML 注释等危险标签与内容。
- 清理大量常见 HTML 标签（如 `iframe`、`object`、`embed` 等），只保留安全的文本内容。
- 对参数键名和值均进行过滤。

**注册位置：** [`config/autoload/middlewares.php`](config/autoload/middlewares.php)

```php
return [
    'http' => [
        App\Middleware\CorsMiddleware::class,
        App\Middleware\CleanXssMiddleware::class,
        ValidationMiddleware::class,
    ],
];
```

> 如需调整过滤规则，可直接修改 `CleanXssMiddleware::clean_xss()` 方法中的正则与标签白名单。

---

## 5. CorsMiddleware — 跨域处理中间件

**文件位置：** [`app/Middleware/CorsMiddleware.php`](app/Middleware/CorsMiddleware.php)

为所有 HTTP 响应自动添加 CORS 头部，支持前端跨域请求：

| 响应头 | 值 |
|--------|-----|
| `Access-Control-Allow-Origin` | `*` |
| `Access-Control-Allow-Credentials` | `true` |
| `Access-Control-Allow-Headers` | `DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,Authorization` |

同时自动处理 `OPTIONS` 预检请求，直接返回响应，避免进入后续业务逻辑。

**注册位置：** [`config/autoload/middlewares.php`](config/autoload/middlewares.php)

> 生产环境建议将 `Access-Control-Allow-Origin` 从 `*` 改为具体的域名白名单。

---

## 6. SmCryptorService — 国密算法服务

**文件位置：** [`app/Service/SmCryptorService.php`](app/Service/SmCryptorService.php)

基于 `oh86/sm_cryptor` 库封装，支持 SM2/SM3/SM4 等国密算法：

| 方法 | 说明 |
|------|------|
| `sm4Encrypt($text)` | SM4 加密 |
| `sm4Decrypt($cipherText)` | SM4 解密 |
| `sm3($text)` | SM3 摘要 |
| `hmacSm3($text)` | HMAC-SM3 签名 |
| `sm2GenSign($text)` | SM2 签名 |
| `sm2VerifySign($text, $sign)` | SM2 验签 |
| `sm2Encrypt($text)` | SM2 加密 |
| `sm2Decrypt($cipherText)` | SM2 解密 |

**配置位置：** [`config/autoload/sm_cryptor.php`](config/autoload/sm_cryptor.php)

支持本地加解密、电信/联通/广东移动密码池等多种驱动模式。本地模式需在 `.env` 中配置：

```env
SM_CRYPTOR_DRIVER=local
SM4_KEY=08c8e6db4907dc755a6097d0abd417c5
HMAC_KEY=08c8e6db4907dc755a6097d0abd417c5
SM2_PUBLIC_KEY=xxx
SM2_PRIVATE_KEY=xxx
```

**使用示例：**

```php
use App\Service\SmCryptorService;

class IndexController extends AbstractController
{
    public function __construct(protected SmCryptorService $smCryptor)
    {
    }

    public function encrypt()
    {
        $cipher = $this->smCryptor->sm4Encrypt('hello');
        return $this->success(['cipher' => $cipher]);
    }
}
```

---

## 7. WechatService — 微信服务集成

**文件位置：** [`app/Service/WechatService.php`](app/Service/WechatService.php)

基于 `w7corp/easywechat` 封装，统一提供微信公众号、微信小程序、微信支付的实例获取：

| 方法 | 说明 |
|------|------|
| `officialAccount($name = 'default')` | 获取公众号 `OfficialAccountApplication` 实例 |
| `miniApp($name = 'default')` | 获取小程序 `MiniAppApplication` 实例 |
| `pay($name = 'default')` | 获取微信支付 `PayApplication` 实例 |

**配置位置：** [`config/autoload/wechat.php`](config/autoload/wechat.php)

在 `.env` 中配置对应参数：

```env
# 微信公众号
WECHAT_OFFICIAL_ACCOUNT_APPID=wx...
WECHAT_OFFICIAL_ACCOUNT_SECRET=...
WECHAT_OFFICIAL_ACCOUNT_TOKEN=...
WECHAT_OFFICIAL_ACCOUNT_AES_KEY=...

# 微信小程序
WECHAT_MINI_APP_APPID=wx...
WECHAT_MINI_APP_SECRET=...

# 微信支付
WECHAT_PAY_APPID=wx...
WECHAT_PAY_MCH_ID=...
WECHAT_PAY_PRIVATE_KEY=/path/to/apiclient_key.pem
WECHAT_PAY_CERTIFICATE=/path/to/apiclient_cert.pem
WECHAT_PAY_CERTIFICATE_SERIAL_NO=...
WECHAT_PAY_V3_SECRET_KEY=...
```

**使用示例：**

```php
use App\Service\WechatService;

class IndexController extends AbstractController
{
    public function __construct(protected WechatService $wechatService)
    {
    }

    public function getUserInfo(string $openId)
    {
        $app = $this->wechatService->officialAccount();
        $user = $app->getUser()->get($openId);
        return $this->success($user);
    }
}
```

---

# 项目结构速览

```
app/
├── Controller/           # 控制器
│   ├── AbstractController.php
│   └── IndexController.php
├── Exception/            # 异常类与处理器
│   ├── AppException.php
│   └── Handler/
│       ├── AppExceptionHandler.php
│       └── ValidationExceptionHandler.php
├── Listener/             # 事件监听器
├── Middleware/           # 中间件
│   ├── CleanXssMiddleware.php
│   └── CorsMiddleware.php
├── Model/                # 数据模型
├── Request/              # 验证请求类
├── Service/              # 业务服务
│   ├── SmCryptorService.php
│   └── WechatService.php
└── Trait/
    └── ResponseTrait.php # 统一响应 Trait
```

---

# 常用命令

```bash
# 启动开发服务器
php bin/hyperf.php start

# 运行测试
composer test

# 代码格式化
composer cs-fix

# 静态分析
composer analyse

# 清理代理缓存（热更新异常时可执行）
composer start
```

---

# 官方资源

- [Hyperf 官方文档](https://hyperf.wiki)
- [Hyperf GitHub](https://github.com/hyperf/hyperf)
- [Hyperf Docker 镜像](https://hub.docker.com/r/hyperf/hyperf)

> 提示：可根据实际项目需要，将 `composer.json`、`docker-compose.yml` 中的 `hyperf-skeleton` 替换为真实项目名称。
