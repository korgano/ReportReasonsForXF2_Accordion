<?php

namespace TickTackk\ReportReasons\XF\Service\Report;

use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use TickTackk\ReportReasons\XF\Entity\ReportComment as ExtendedReportCommentEntity;

/**
 * Class CommentPreparer
 * 
 * Extends \XF\Service\Report\CommentPreparer
 *
 * @package TickTackk\ReportReasons\XF\Service\Report
 */
class CommentPreparer extends XFCP_CommentPreparer
{
    /**
     * @param ReportReasonEntity $reportReason
     * @param string $originalMessage
     */
    public function setReportReason(ReportReasonEntity $reportReason, string $originalMessage) : void
    {
        /** @var ExtendedReportCommentEntity $comment */
        $comment = $this->getComment();
        $comment->tck_report_reason_id = $reportReason->reason_id;

        if (!$reportReason->reason_id)
        {
            return;
        }

        $phraseName = 'tckReportReasons_report_comment_message_with_reason';
        if ($originalMessage)
        {
            $phraseName = 'tckReportReasons_report_comment_message_with_reason_and_more_information';
        }

        $message = \XF::phrase($phraseName, [
            'reason' => $reportReason->reason,
            'message' => $originalMessage
        ], true)->render('raw');

        $this->setMessage($message);
    }
}