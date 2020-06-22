<?php


namespace Nipwaayoni\ElasticApmLaravel\Tests;

use Nipwaayoni\ElasticApmLaravel\Facades\ElasticApm;
use Nipwaayoni\ElasticApmLaravel\Providers\ElasticApmServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [ElasticApmServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'ElasticApm' => ElasticApm::class
        ];
    }
}