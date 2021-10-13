<?php

namespace Braceyourself\Yourmembership;

use Braceyourself\Yourmembership\Clients\Client;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;

class Yourmembership
{
    public static function mapInto(string $entity_name, array $attributes)
    {
        $class = static::getMappedClass($entity_name);

        return new $class(...$attributes);
    }


    public static function getMappedClass($entity_name)
    {
        $entity_name = Str::of($entity_name);

        if (class_exists("$entity_name") && $entity_name->trim("\\")->startsWith("Braceyourself\Yourmembership\Models")) {
            $entity_name = $entity_name->classBasename()->snake()->lower();
        }

        return config("yourmembership.classmap.$entity_name");
    }

    public static function mergeConfig($account, array $config)
    {
        $key = "yourmembership.accounts.$account";

        $config = collect($config)->map(function ($option) {
            if (is_array($option)) {
                return collect($option)->map(function ($item) {
                    if ($item instanceof \Closure) {
                        $item = new SerializableClosure($item);
                    }

                    return $item;
                });
            }

            return $option;
        })->toArray();

        \Config::set($key, array_merge_recursive(
            \Config::get($key),
            $config
        ));
    }


}