<?php declare(strict_types=1);

namespace Tests\Support;

use Concept\Extensions\ValidationRakit\Rules\Rule;

final class StartsWithRule extends Rule
{
    protected string $message = 'The :attribute must start with :prefix';

    /** @var list<string> */
    protected array $fillable = ['prefix'];

    /** @var list<string> */
    protected array $required = ['prefix'];

    public function passes(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $prefix = $this->parameter('prefix', '');

        return str_starts_with($value, (string) $prefix);
    }
}
