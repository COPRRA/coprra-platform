<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;

abstract class ValidatableModel extends Model
{
    protected ?MessageBag $errors = null;

    /**
     * @var array<string, string>
     */
    protected array $rules = [];
}
