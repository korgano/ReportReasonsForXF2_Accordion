<?php

namespace TickTackk\ReportReasons\XF\Service\Report;

use SV\ReportCentreEssentials\XF\Service\Report\Creator as ExtendedReportEssReportCreatorSvc;
use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use TickTackk\ReportReasons\Listener;
use TickTackk\ReportReasons\XF\Service\Report\Creator as ExtendedReportCreatorSvc;
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
     * @param ReportReasonEntity|null $reportReason
     */
    public function setReportReason(?ReportReasonEntity $reportReason) : void
    {
        if ($reportReason)
        {
            /** @var ReportCommentEntity|ExtendedReportCommentEntity $comment */
            $comment = $this->comment;
            $comment->tck_report_reason_id = $reportReason->reason_id;

            if (Listener::isReportCentreEssentialsInstalled())
            {
                $reportQueue = $reportReason->ReportQueue;

                if ($reportQueue
                    // set report queue only if creating (report_queue_id = null) or is in default reports queue (queue_id = 1)
                    && \in_array($this->report->queue_id, [ReportReasonEntity::DEFAULT_REPORT_QUEUE_ID, null], true)
                    && $reportQueue->queue_id !== ReportReasonEntity::DEFAULT_REPORT_QUEUE_ID)
                {
                    $this->setQueue($reportQueue);
                }
            }
        }
    }
}