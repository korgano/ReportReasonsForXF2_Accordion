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
        $installedAddOns = $app->addOnManager()->getInstalledAddOns();

        if (!\array_key_exists('SV/ReportCentreEssentials', $installedAddOns))
        {
            return false;
        }

        $addOn = $installedAddOns['SV/ReportCentreEssentials']->getInstalledAddOn();
        return $addOn->version_id >= 2030000;
    }
}