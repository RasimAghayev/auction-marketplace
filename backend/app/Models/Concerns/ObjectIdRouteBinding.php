<?php

namespace App\Models\Concerns;

use MongoDB\BSON\ObjectId;

trait ObjectIdRouteBinding
{
    public function resolveRouteBinding($value, $field = null)
    {
        if (is_string($value) && preg_match('/^[0-9a-fA-F]{24}$/', $value)) {
            $model = static::where('_id', new ObjectId($value))->first();

            if ($model) {
                return $model;
            }
        }

        return parent::resolveRouteBinding($value, $field);
    }
}
