<?php

namespace App\Modules\Core\Actions;

use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Base class for every business operation.
 *
 * Actions are invoked as `SomeAction::run(...)` and always execute inside a
 * database transaction, so a controller can never write a half-finished
 * business document. Subclasses define `handle(...)` with their own signature.
 */
abstract class Action
{
    public static function run(mixed ...$arguments): mixed
    {
        return app(static::class)->execute(...$arguments);
    }

    public function execute(mixed ...$arguments): mixed
    {
        if (! method_exists($this, 'handle')) {
            throw new LogicException(static::class.' must define a handle() method.');
        }

        return DB::transaction(fn () => $this->handle(...$arguments));
    }
}
