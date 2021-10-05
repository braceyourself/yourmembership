<?php

namespace Braceyourself\Yourmembership;

use Braceyourself\Yourmembership\Clients\Client;
use Illuminate\Support\Str;

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

        if (class_exists($entity_name) && $entity_name->trim("\\")->startsWith("Braceyourself\Yourmembership\Models")) {
            $entity_name = $entity_name->classBasename()->snake()->lower();
        }

        return config("yourmembership.classmap.$entity_name");
    }


}