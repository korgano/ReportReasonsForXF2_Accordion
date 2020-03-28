<?php

namespace TickTackk\ReportReasons\ControllerPlugin;

use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use TickTackk\ReportReasons\Repository\ReportReason as ReportReasonRepo;
use XF\ControllerPlugin\AbstractPlugin as AbstractControllerPlugin;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Mvc\Reply\Exception as ExceptionReply;

/**
 * Class ReportReason
 *
 * @package TickTackk\ReportReasons\ControllerPlugin
 */
class ReportReason extends AbstractControllerPlugin
{
    /**
     * @param int|null $reportReasonId
     * @param array $with
     *
     * @return ReportReasonEntity|Entity
     *
     * @throws ExceptionReply
     */
    public function assertReportReasonExists(?int $reportReasonId, array $with = []) : ReportReasonEntity
    {
        $reportReasonRepo = $this->getReportReasonRepo();
        $reportReasonFinder = $reportReasonRepo->getReportReasonFinder();
        $reportReason = $reportReasonFinder->where('reason_id', $reportReasonId)->fetchOne();

        if (!$reportReason)
        {
            throw $this->exception($this->notFound(
                \XF::phrase('tckReportReasons_requested_report_reason_not_found')
            ));
        }

        return $reportReason;
    }

    /**
     * @return Repository|ReportReasonRepo
     */
    protected function getReportReasonRepo() : ReportReasonRepo
    {
        return $this->repository('TickTackk\ReportReasons:ReportReason');
    }
}