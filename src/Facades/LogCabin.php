<?php

namespace Forestry\LogCabin\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void report(string $type, string $message, array $context = [], string $level = 'event')
 * @method static void reportException(\Throwable $exception, array $context = [])
 *
 * @see \Forestry\LogCabin\Laravel\LogCabinReporter
 */
class LogCabin extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Forestry\LogCabin\Laravel\LogCabinReporter::class;
    }
}
