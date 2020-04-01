<?php

namespace TickTackk\ReportReasons;

use XF\App as BaseApp;

/**
 * Class Listener
 *
 * @package TickTackk\ReportReasons
 */
class Listener
{
    /**
     * @param BaseApp|null $app
     *
     * @return bool
     */
    public static function isReportCentreEssentialsInstalled(BaseApp $app = null) : bool
    {
        $app = $app ?: \XF::app();
        $registry = $app->registry();
        $addOns = $registry->get('addOns');

        if (!\array_key_exists('SV/ReportCentreEssentials', $addOns))
        {
            return false;
        }

        return $addOns['SV/ReportCentreEssentials'] >= 2030000;
    }
}