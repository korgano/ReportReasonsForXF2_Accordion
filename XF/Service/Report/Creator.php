<?php

namespace TickTackk\ReportReasons\XF\Service\Report;

use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use TickTackk\ReportReasons\XF\Service\Report\Exception\InvalidReportReasonProvidedException;
use XF\App as BaseApp;
use XF\Entity\ReportComment as ReportCommentEntity;
use TickTackk\ReportReasons\XF\Entity\ReportComment as ExtendedReportCommentEntity;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Service\AbstractService;
use XF\Mvc\Entity\Manager as EntityManager;
use XF\Job\Manager as JobManager;

/**
 * Class Creator
 * 
 * Extends \XF\Service\Report\Creator
 *
 * @package TickTackk\ReportReasons\XF\Service\Report
 */
class Creator extends XFCP_Creator
{
    /**
     * @param ReportReasonEntity|int $reportReasonId
     */
    public function setReportReason($reportReasonId) : void
    {
        if ($reportReasonId instanceof ReportReasonEntity)
        {
            $this->setReportReason($reportReasonId->reason_id);
        }
        else if (\is_int($reportReasonId))
        {
            /** @var ReportCommentEntity|ExtendedReportCommentEntity $report */
            $comment = $this->comment;
            $comment->tck_report_reason_id = $reportReasonId;
        }
        else
        {
            throw new InvalidReportReasonProvidedException($reportReasonId);
        }
    }
}