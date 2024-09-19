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
namespace World\HyperfElasticsearch;

use Elastic\Elasticsearch\ClientBuilder;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Coroutine\Coroutine;
use function Hyperf\Collection\data_get;
use function Hyperf\Config\config;
use function Hyperf\Support\make;

class ElasticsearchClientBuilderFactory
{
    public function create($config=[])
    {
        $builder = ClientBuilder::create();

        $logger = make(LoggerFactory::class);

        if (empty($config)) {
            $config = config('elasticsearch');
        }
        $hosts = data_get($config, 'hosts', 'http://127.0.0.1:9200');
        $hosts = explode('|', $hosts);
        $builder->setHosts($hosts);

        $username = data_get($config, 'username', 'elastic');
        $password = data_get($config, 'password');
        if (!empty($username) && !empty($password)) {
            $builder->setBasicAuthentication($username, $password);
        }

        $loggerGroup = data_get($config, 'logger.'.config('app_env', 'local'));
        if ($loggerGroup) {
            $builder->setLogger($logger->get('es', $loggerGroup));
        }

        if (Coroutine::inCoroutine()) {
            $options = [
                'handler' => new ElasticsearchCoroutineHandler($config),
                'base_uri' => count($hosts) == 1 ? $hosts[0] : '',
            ];
            $builder->setHttpClientOptions($options);
        }

        return $builder;
    }
}
