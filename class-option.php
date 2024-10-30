<?php 

namespace VENDOR\CF7_RECAPTCHA_MINE_FREE;

defined( 'ABSPATH' ) or die( 'Are you ok?' );

/**
 * Class Option: Each instance of that class is intended to hold an option for the plugin
 * 
 */
class Option
{
    /** @var string */
    const PREFIX = 'rcm_';

    /** @var int */
    const INT = 1;

    /** @var int */
    const STRING = 2;

    /** @var string */
    const PAGE_QUERY = '?page=' . self::PREFIX . 'options';

    /** @var string */
    const POW_SALT = self::PREFIX . 'pow_salt';

    /** @var string */
    const POW_STAMP_LOG = self::PREFIX . 'pwo_stamp_log';

    /** @var string */
    const POW_DIFFICULTY = self::PREFIX . 'pow_difficulty';

    /** @var string */
    const POW_TIME_WINDOW = self::PREFIX . 'pow_time_window';

    /** @var string */
    const HASH = self::PREFIX . 'hash';

    /** @var string */
    private $name;

    /** @var int */
    private $type;

    /** @var int */
    private $default;

    /** @var int */
    private $hint;

    /** @var string|int */
    private $value = '';

    /**
     * @param string $name
     * @param int $type
     */
    public function __construct(string $name, int $type, string $default, string $hint)
    {
        $this->name = "<h2><b>".$name."</b></h2>";
        $this->type = $type;
        $this->default = $default;
        $this->value = $default;
        $this->hint = $hint;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getDefault(): string
    {
        return $this->default;
    }

    /**
     * @return string
     */
    public function getHint(): string
    {
        return $this->hint;
    }

    /**
     * @return int|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
