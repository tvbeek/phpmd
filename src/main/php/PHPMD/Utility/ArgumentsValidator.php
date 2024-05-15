<?php

namespace PHPMD\Utility;

use InvalidArgumentException;

final class ArgumentsValidator
{
    /**
     * @param string[] $originalArguments
     * @param string[] $arguments
     */
    public function __construct(
        private bool $hasImplicitArguments,
        private array $originalArguments,
        private array $arguments,
    ) {
    }

    /**
     * Throw an exception if the given $value cannot be used as a value for the argument $name.
     *
     * @param string $name
     * @param string $value
     *
     * @throws InvalidArgumentException if the given $value cannot be used as a value for the argument $name
     */
    public function validate($name, $value): void
    {
        if (!$this->hasImplicitArguments) {
            return;
        }

        if (substr($value, 0, 1) !== '-') {
            return;
        }

        $options = array_diff($this->originalArguments, $this->arguments, ['--']);

        throw new InvalidArgumentException(
            'Unknown option ' . $value . '.' . PHP_EOL .
            'If you intend to use "' . $value . '" as a value for ' . $name . ' argument, ' .
            'use the explicit argument separator:' . PHP_EOL .
            rtrim('phpmd ' . implode(' ', $options)) . ' -- ' .
            implode(' ', $this->arguments)
        );
    }
}
