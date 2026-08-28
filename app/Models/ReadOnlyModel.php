<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

abstract class ReadOnlyModel extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    public function save(array $options = []): bool
    {
        throw new LogicException(static::class.' is a read-only view model.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $options
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException(static::class.' is a read-only view model.');
    }

    public function delete(): ?bool
    {
        throw new LogicException(static::class.' is a read-only view model.');
    }
}
