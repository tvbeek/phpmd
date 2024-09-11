<?php

/**
 * This file is part of PHP Mess Detector.
 *
 * Copyright (c) Manuel Pichler <mapi@phpmd.org>.
 * All rights reserved.
 *
 * Licensed under BSD License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Manuel Pichler <mapi@phpmd.org>
 * @copyright Manuel Pichler. All rights reserved.
 * @license https://opensource.org/licenses/bsd-license.php BSD License
 * @link http://phpmd.org/
 */

namespace PHPMD;

use AppendIterator;
use ArrayIterator;
use Exception;
use GlobIterator;
use InvalidArgumentException;
use PDepend\Application;
use PDepend\Engine;
use PDepend\Input\CompositeFilter;
use PDepend\Input\ExcludePathFilter;
use PDepend\Input\ExtensionFilter;
use PDepend\Input\Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileObject;

/**
 * Simple factory that is used to return a ready to use PDepend instance.
 */
final class ParserFactory
{

    /** @var string The default config file name */
    private const PDEPEND_CONFIG_FILE_NAME = '/pdepend.xml';

    /** @var string The distribution config file name */
    private const PDEPEND_CONFIG_FILE_NAME_DIST = '/pdepend.xml.dist';

    /**
     * Mapping between phpmd option names and those used by pdepend.
     *
     * @var array<string, string>
     */
    private array $phpmd2pdepend = [
        'coverage' => 'coverage-report',
    ];

    /** Prefix for PHP streams. */
    protected string $phpStreamPrefix = 'php://';    /** A composite filter for input files. */

    /**
     * Creates the used {@link \PHPMD\Parser} analyzer instance.
     *
     * @throws Exception
     */
    public function create(PHPMD $phpmd): Parser
    {
        $pdepend = $this->createInstance();
        $pdepend = $this->init($pdepend, $phpmd);

//        return new Parser($pdepend);
        return new Parser($pdepend, $this->createFileIterator($phpmd));
    }

    /**
     * Creates a clean php depend instance with some base settings.
     *
     * @throws Exception
     */
    private function createInstance(): Engine
    {
        $application = new Application();

        $workingDirectory = getcwd();
        if (file_exists($workingDirectory . self::PDEPEND_CONFIG_FILE_NAME)) {
            $application->setConfigurationFile($workingDirectory . self::PDEPEND_CONFIG_FILE_NAME);
        } elseif (file_exists($workingDirectory . self::PDEPEND_CONFIG_FILE_NAME_DIST)) {
            $application->setConfigurationFile($workingDirectory . self::PDEPEND_CONFIG_FILE_NAME_DIST);
        }

        return $application->getEngine();
    }

    /**
     * Configures the given PDepend\Engine instance based on some user settings.
     *
     * @throws InvalidArgumentException
     */
    private function init(Engine $pdepend, PHPMD $phpmd): Engine
    {
        $this->initOptions($pdepend, $phpmd);
//        $this->initInput($pdepend, $phpmd);
        $this->initIgnores($pdepend, $phpmd);
        $this->initExtensions($pdepend, $phpmd);
        $this->initResultCache($pdepend, $phpmd);

        return $pdepend;
    }

    /**
     * Configures the input source.
     *
     * @throws InvalidArgumentException
     */
    private function initInput(Engine $pdepend, PHPMD $phpmd): void
    {
        foreach (explode(',', $phpmd->getInput()) as $path) {
            $trimmedPath = trim($path);
            if (is_dir($trimmedPath)) {
                $pdepend->addDirectory($trimmedPath);

                continue;
            }
            $pdepend->addFile($trimmedPath);
        }
    }



    /**
     * Initializes the ignored files and path's.
     */
    private function initIgnores(Engine $pdepend, PHPMD $phpmd): void
    {
        if (count($phpmd->getIgnorePatterns()) > 0) {
            $pdepend->addFileFilter(
                new ExcludePathFilter($phpmd->getIgnorePatterns())
            );
        }
    }

    /**
     * Initializes the accepted php source file extensions.
     */
    private function initExtensions(Engine $pdepend, PHPMD $phpmd): void
    {
        if (count($phpmd->getFileExtensions()) > 0) {
            $pdepend->addFileFilter(
                new ExtensionFilter($phpmd->getFileExtensions())
            );
        }
    }

    /**
     * Cache result hook to filter cached files
     */
    private function initResultCache(Engine $pdepend, PHPMD $phpmd): void
    {
        $resultCache = $phpmd->getResultCache();
        if ($resultCache !== null) {
            $pdepend->addFileFilter($resultCache->getFileFilter());
        }
    }

    /**
     * Initializes additional options for pdepend.
     */
    private function initOptions(Engine $pdepend, PHPMD $phpmd): void
    {
        $options = [];
        foreach (array_filter($phpmd->getOptions()) as $name => $value) {
            if (isset($this->phpmd2pdepend[$name])) {
                $options[$this->phpmd2pdepend[$name]] = $value;
            }
        }
        $pdepend->setOptions($options);
    }

    private function createFileIterator(PHPMD $phpmd): ArrayIterator
    {
        $fileIterator = new AppendIterator();

        $fileFilter = new CompositeFilter();

        foreach (explode(',', $phpmd->getInput()) as $path) {
            $trimmedPath = trim($path);
            if (is_dir($trimmedPath)) {
                $fileIterator->append(
                    new Iterator(
                        new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator(
                                $trimmedPath . '/',
                                RecursiveDirectoryIterator::FOLLOW_SYMLINKS,
                            ),
                        ),
                        $fileFilter,
                        $trimmedPath,
                    ),
                );
                continue;
            }
            $fileIterator->append(
                $this->isPhpStream($trimmedPath)
                    ? new ArrayIterator([new SplFileObject($trimmedPath)])
                    : new Iterator(new GlobIterator($trimmedPath), $fileFilter),
            );
        }
        $files = [];
        foreach ($fileIterator as $file) {
            if (is_string($file)) {
                $files[$file] = $file;
            } else {
                $pathname = $file->getRealPath() ?: $file->getPathname();
                $files[$pathname] = $pathname;
            }
        }

        ksort($files);

        return new ArrayIterator(array_values($files));
    }

    private function isPhpStream(string $path): bool
    {
        return str_starts_with($path, $this->phpStreamPrefix);
    }
}
