<?php

namespace TickTackk\ReportReasons\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use TickTackk\ReportReasons\Finder\ReportReason as ReportReasonFinder;

/**
 * Class ReportReason
 *
 * @package TickTackk\ReportReasons\Repository
 */
class ReportReason extends Repository
{
    /**
     * @return ReportReasonFinder|Finder
     */
    public function findReportReasonsForList() : ReportReasonFinder
    {
        return $this->getReportReasonFinder()->setDefaultOrder('reason_id');
    }

    /**
     * @return Finder|ReportReasonFinder
     */
    public function getReportReasonFinder() : ReportReasonFinder
    {
        return $this->finder('TickTackk\ReportReasons:ReportReason');
    }
}