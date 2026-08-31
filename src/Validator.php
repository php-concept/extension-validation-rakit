<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit;

use Concept\Extensions\ValidationRakit\Adapters\RuleAdapter;
use Concept\Extensions\ValidationRakit\Adapters\ValidationAdapter;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidationInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use Rakit\Validation\Validator as RakitValidator;

final class Validator implements ValidatorInterface
{
    private readonly RakitValidator $libraryValidator;

    public function __construct(?RakitValidator $libraryValidator = null)
    {
        $this->libraryValidator = $libraryValidator ?? new RakitValidator();
    }

    public function addRules(array $rules): void
    {
        foreach ($rules as $name => $rule) {
            $this->libraryValidator->addValidator($name, new RuleAdapter($rule));
        }
    }

    public function make(array $data, array $rulesConfig): ValidationInterface
    {
        return new ValidationAdapter($this->libraryValidator->make($data, $rulesConfig));
    }
}
