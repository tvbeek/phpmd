<?php
/**
 * This file is part of PHP Mess Detector.
 * Copyright (c) Manuel Pichler <mapi@phpmd.org>.
 * All rights reserved.
 * Licensed under BSD License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 * @author    Manuel Pichler <mapi@phpmd.org>
 * @copyright Manuel Pichler. All rights reserved.
 * @license   https://opensource.org/licenses/bsd-license.php BSD License
 * @link      http://phpmd.org/
 */

namespace PHPMD\Rule\Controversial;

use PHPMD\AbstractNode;
use PHPMD\AbstractRule;
use PHPMD\Rule\ClassAware;
use PHPMD\Rule\EnumAware;
use PHPMD\Rule\InterfaceAware;
use PHPMD\Rule\TraitAware;
use PHPMD\Utility\Strings;

/**
 * This rule class detects namespace parts that are not named in CamelCase.
 */
class CamelCaseNamespace extends AbstractRule implements ClassAware, InterfaceAware, TraitAware, EnumAware
{
    /** @var array<string, int>|null */
    protected $exceptions;

    public function apply(AbstractNode $node)
    {
        $pattern = '/^[A-Z][a-zA-Z0-9]*$/';
        if ($this->getBooleanProperty('camelcase-abbreviations', false)) {
            // disallow any consecutive uppercase letters
            $pattern = '/^([A-Z][a-z0-9]+)*$/';
        }

        $exceptions     = $this->getExceptionsList();
        $fullNamespace  = $node->getNamespaceName();
        $namespaceNames = $fullNamespace === '' ? array() : explode('\\', $fullNamespace);

        foreach ($namespaceNames as $namespaceName) {
            if (isset($exceptions[$namespaceName])) {
                continue;
            }

            if (!preg_match($pattern, $namespaceName)) {
                $this->addViolation($node, array($namespaceName, $fullNamespace));
            }
        }
    }

    /**
     * Gets array of exceptions from property
     * @return array<string, int>
     */
    protected function getExceptionsList()
    {
        if ($this->exceptions === null) {
            $this->exceptions = array_flip(
                Strings::splitToList($this->getStringProperty('exceptions', ''), ',')
            );
        }

        return $this->exceptions;
    }
}
