<?php
namespace Crossjoin\Browscap;

use Crossjoin\Browscap\Formatter\FormatterInterface;

/**
 * Main Crossjoin\Browscap class
 *
 * Crossjoin\Browscap allows to check for browser settings, using the data
 * from the Browscap project (browscap.org). It's about 40x faster than the
 * get_browser() function in PHP, with a very small memory consumption.
 *
 * It includes automatic updates of the Browscap data and allows to extends
 * or replace nearly all components: the updater, the parser (including the
 * used source), and the formatter (for the result set).
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
class Browscap
{
    /**
     * Current version of the package.
     * Has to be updated to automatically renew cache data.
     */
    const VERSION = '1.0.5';

    /**
     * Data set types
     */
    const DATASET_TYPE_DEFAULT = 1;
    const DATASET_TYPE_SMALL   = 2;
    const DATASET_TYPE_LARGE   = 3;

    /**
     * Use automatic updates (if no explicit updater set)
     *
     * @var \Crossjoin\Browscap\Updater\AbstractUpdater
     */
    protected $autoUpdate;

    /**
     * Updater to use
     *
     * @var \Crossjoin\Browscap\Updater\AbstractUpdater
     */
    protected static $updater;

    /**
     * Parser to use
     *
     * @var \Crossjoin\Browscap\Parser\AbstractParser
     */
    protected static $parser;

    /**
     * Formatter to use
     *
     * @var FormatterInterface
     */
    protected static $formatter;

    /**
     * The data set type to use (default, small or large,
     * see constants).
     */
    protected static $datasetType = self::DATASET_TYPE_DEFAULT;

    /**
     * Probability in percent that the update check is done
     *
     * @var float
     */
    protected $updateProbability = 1.0;

    /**
     * Constructor
     *
     * @param bool $autoUpdate
     */
    public function __construct($autoUpdate = true)
    {
        $this->autoUpdate = (bool)(int)$autoUpdate;
    }

    /**
     * Checks the given/detected user agent and returns a
     * formatter instance with the detected settings
     *
     * @param string $userAgent
     * @return FormatterInterface
     */
    public function getBrowser($userAgent = null)
    {
        // automatically detect the user agent
        if ($userAgent === null) {
            $userAgent = '';
            if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
            }
        }

        // check for update first
        if ($this->autoUpdate === true) {
            $randomMax = floor(100 / $this->updateProbability);
            if (function_exists('random_int')) {
                $randomInt = random_int(1, $randomMax);
            } else {
                /** @noinspection RandomApiMigrationInspection */
                $randomInt = mt_rand(1, $randomMax);
            }
            if ($randomInt === 1) {
                static::getParser()->update();
            }
        }

        // try to get browser data
        $return = static::getParser()->getBrowser($userAgent);

        // if not found, there has to be a problem with the source data,
        // because normally default browser data are returned,
        // so set the probability to 100%, to force an update.
        if ($return === null && $this->updateProbability < 100) {
            $updateProbability = $this->updateProbability;
            $this->updateProbability = 100;
            $return = $this->getBrowser($userAgent);
            $this->updateProbability = $updateProbability;
        }

        // if return is still NULL, updates are disabled... in this
        // case we return an empty formatter instance
        if ($return === null) {
            $return = static::getFormatter();
        }

        return $return;
    }

    /**
     * Set the formatter instance to use for the getBrowser() result
     *
     * @param FormatterInterface $formatter
     */
    public static function setFormatter(FormatterInterface $formatter)
    {
        static::$formatter = $formatter;
    }

    /**
     * @return FormatterInterface
     */
    public static function getFormatter()
    {
        if (static::$formatter === null) {
            static::setFormatter(new Formatter\PhpGetBrowser());
        }
        return static::$formatter;
    }

    /**
     * Sets the parser instance to use
     *
     * @param \Crossjoin\Browscap\Parser\AbstractParser $parser
     */
    public static function setParser(Parser\AbstractParser $parser)
    {
        static::$parser = $parser;
    }

    /**
     * @return Parser\AbstractParser
     */
    public static function getParser()
    {
        if (static::$parser === null) {
            // generators are supported from PHP 5.5, so select the correct parser version to use
            // (the version without generators requires about 2-3x the memory and is a bit slower)
            if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
                static::setParser(new Parser\Ini());
            } else {
                static::setParser(new Parser\IniLt55());
            }
        }
        return static::$parser;
    }

    /**
     * Sets the updater instance to use
     *
     * @param \Crossjoin\Browscap\Updater\AbstractUpdater $updater
     */
    public static function setUpdater(Updater\AbstractUpdater $updater)
    {
        static::$updater = $updater;
    }

    /**
     * Gets the updater instance (and initializes the default one, if not set)
     *
     * @return \Crossjoin\Browscap\Updater\AbstractUpdater
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getUpdater()
    {
        if (static::$updater === null) {
            $updater = Updater\FactoryUpdater::getInstance();
            if ($updater !== null) {
                static::setUpdater($updater);
            }
        }
        return static::$updater;
    }

    /**
     * Sets the data set type to use for the source.
     *
     * @param integer $dataSetType
     * @throws \InvalidArgumentException
     */
    public static function setDataSetType($dataSetType)
    {
        if (in_array(
            $dataSetType,
            array(self::DATASET_TYPE_DEFAULT, self::DATASET_TYPE_SMALL, self::DATASET_TYPE_LARGE),
            true
        )) {
            static::$datasetType = $dataSetType;
        } else {
            throw new \InvalidArgumentException("Invalid value for argument 'dataSetType'.");
        }
    }

    /**
     * Gets the data set type to use for the source.
     *
     * @return integer
     */
    public static function getDataSetType()
    {
        return static::$datasetType;
    }

    /**
     * Triggers an update check (with the option to force an update).
     *
     * @param boolean $forceUpdate
     */
    public static function update($forceUpdate = false)
    {
        static::getParser()->update($forceUpdate);
    }
}
