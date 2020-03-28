<?php

namespace TickTackk\ReportReasons\XF\Service\Report;

use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use TickTackk\ReportReasons\Listener;
use TickTackk\ReportReasons\XF\Service\Report\CommentPreparer as ExtendedReportCommentPreparerSvc;

/**
 * Class Creator
 * 
 * Extends \XF\Service\Report\Creator
 *
 * @package TickTackk\ReportReasons\XF\Service\Report
 *
 * @property ExtendedReportCommentPreparerSvc $commentPreparer
 */
class Creator extends XFCP_Creator
{
    /**
     * @param ReportReasonEntity $reportReason
     * @param string $originalMessage
     */
    public function setReportReason(ReportReasonEntity $reportReason, string $originalMessage) : void
    {
        /** @var ExtendedReportCommentPreparerSvc $commentPreparer */
        $commentPreparer = $this->getCommentPreparer();
        $commentPreparer->setReportReason($reportReason, $originalMessage);

        if (Listener::isReportCentreEssentialsInstalled())
        {
            $reportQueue = $reportReason->ReportQueue;
            if (!$reportQueue)
            {
                return;
            }

            // set report queue only if creating (report_queue_id = null) or is in default reports queue (queue_id = 1)
            if (!\in_array($this->report->queue_id, [ReportReasonEntity::DEFAULT_REPORT_QUEUE_ID, null], true))
            {
                return;
            }

            if ($reportQueue->queue_id === ReportReasonEntity::DEFAULT_REPORT_QUEUE_ID)
            {
                return;
            }

            $this->setQueue($reportQueue);
        }

        if (!$reportReason->reason_id)
        {
            return;
        }

        $threadCreator = $this->getThreadCreator();
        if ($threadCreator)
        {
            $currentMessage = $commentPreparer->getComment()->message;
            if ($currentMessage !== $originalMessage)
            {
                $report = $this->report;
                $handler = $report->getHandler();

                $params = $handler->getContentForThreadReport($report, $originalMessage);
                $params['reason_id'] = $reportReason->reason_id;
                $params['reason'] = $reportReason->reason;

                $messageContentPhrase = 'tckReportReasons_reported_thread_message';
                if ($originalMessage)
                {
                    $messageContentPhrase = 'tckReportReasons_reported_thread_message_with_additional_information';
                }

                $title = \XF::phrase('reported_thread_title', ['title' => $handler->getContentTitle($report)])->render('raw');
                $messageContent = \XF::phrase($messageContentPhrase, $params)->render('raw');

                $threadCreator->setContent($title, $messageContent);
            }
        }
    }
}