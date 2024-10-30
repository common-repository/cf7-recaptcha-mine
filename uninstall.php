<?php

namespace VENDOR\CF7_RECAPTCHA_MINE_FREE;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die('Are you ok?');
}

/** Class Uninstall
 * 
 */
class Uninstall
{
    /**
     * @return void
     */
    public static function run()
    {
        require_once dirname(__FILE__) . '/class-option.php';

        $constants = (new \ReflectionClass(Option::class))->getConstants();

        foreach ($constants as $constant) {
            $constPrefix = substr($constant, 0, strlen(Option::PREFIX));

            if ($constPrefix === Option::PREFIX) {
                delete_option($constant);
            }
        }
    }
}

Uninstall::run();
