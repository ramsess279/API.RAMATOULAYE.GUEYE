<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait UuidTrait
{
    /**
     * Boot the model with UUID generation.
     */
    protected static function bootUuidTrait()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the key type for the model.
     */
    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Indicate that the IDs are not auto-incrementing.
     */
    public function getIncrementing()
    {
        return false;
    }
}