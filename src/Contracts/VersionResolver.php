<?php


namespace Nipwaayoni\ElasticApmLaravel\Contracts;


interface VersionResolver
{
    public function getVersion(): string;
}
