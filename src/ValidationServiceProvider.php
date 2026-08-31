<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit;

use Closure;
use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\Event\Events\ExtensionAwakened;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use Concept\Support\FactoryResolver;
use InvalidArgumentException;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use Psr\Container\ContainerInterface;

final class ValidationServiceProvider extends AbstractServiceProvider
{
    private const string EXTENSION_NAME = 'validation-rakit';
    private const string ERR_RULE_MUST_IMPLEMENT_INTERFACE = 'Rule %s must implement %s.';

    /**
     * @param array<string, class-string<RuleInterface>> $customRules
     * @param Closure(): ?DataMaskerInterface|null $dataMaskerFactory
     */
    public function __construct(
        private readonly array $customRules = [],
        private readonly bool $logEnabled = false,
        private readonly string $logFilePath = '',
        private readonly int $logMaxFiles = 7,
        private readonly ?Closure $dataMaskerFactory = null,
    ) {}

    public function provides(string $id): bool
    {
        return in_array($id, [
            ValidatorInterface::class,
            ValidationLogger::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ValidationLogger::class, function(): ValidationLogger {
            $monolog = new Monolog('validation');
            $monolog->pushHandler(new RotatingFileHandler(
                $this->logFilePath,
                $this->logMaxFiles,
                Level::Debug,
            ));

            $masker = $this->resolveDataMasker();

            return new ValidationLogger($this->logEnabled, $monolog, $masker);
        })->setShared(true);

        $container->add(ValidatorInterface::class, function() use ($container): ValidatorInterface {
            EventDispatcherResolver::optional($container)?->dispatch(new ExtensionAwakened(
                extensionName: self::EXTENSION_NAME,
                anchorId: ValidatorInterface::class,
            ));

            $validator = new Validator();
            $validator->addRules($this->resolveCustomRules($container));

            return $validator;
        })->setShared(true);
    }

    /**
     * @return array<string, RuleInterface>
     */
    private function resolveCustomRules(ContainerInterface $container): array
    {
        $rules = [];
        foreach ($this->customRules as $name => $ruleClass) {
            $rule = $container->get($ruleClass);
            if (!$rule instanceof RuleInterface) {
                throw new InvalidArgumentException(sprintf(
                    self::ERR_RULE_MUST_IMPLEMENT_INTERFACE,
                    $ruleClass,
                    RuleInterface::class,
                ));
            }

            $rules[$name] = $rule;
        }

        return $rules;
    }

    private function resolveDataMasker(): ?DataMaskerInterface
    {
        return FactoryResolver::optional(
            $this->dataMaskerFactory,
            DataMaskerInterface::class,
            'Data masker factory result',
        );
    }
}
