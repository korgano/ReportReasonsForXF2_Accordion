<?php

namespace TickTackk\ReportReasons\XF\ControllerPlugin;

use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use TickTackk\ReportReasons\Repository\ReportReason as ReportReasonRepo;
use XF\App as BaseApp;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Mvc\Reply\View as ViewReply;
use XF\Mvc\Reply\Error as ErrorReply;
use XF\Mvc\Reply\Redirect as RedirectReply;
use XF\Service\Report\Creator as ReportCreatorSvc;
use TickTackk\ReportReasons\XF\Service\Report\Creator as ExtendedReportCreatorSvc;
use XF\Http\Request as HttpRequest;
use XF\Mvc\Reply\Exception as ExceptionReply;

/**
 * Class Report
 * 
 * Extends \XF\ControllerPlugin\Report
 *
 * @package TickTackk\ReportReasons\XF\ControllerPlugin
 */
class Report extends XFCP_Report
{
    /**
     * @param string $contentType
     * @param Entity $content
     * @param string $confirmUrl
     * @param string $returnUrl
     * @param array $options
     *
     * @return ErrorReply|RedirectReply|ViewReply
     */
    public function actionReport($contentType, Entity $content, $confirmUrl, $returnUrl, $options = [])
    {
        $reply = parent::actionReport($contentType, $content, $confirmUrl, $returnUrl, $options);

        if ($reply instanceof ViewReply)
        {
            $reportReasonRepo = $this->getReportReasonRepo();
            $reportReasonFinder = $reportReasonRepo->findReportReasonsForList()->isActive();

            $reply->setParam('reportReasons', $reportReasonFinder->fetch());
        }

        return $reply;
    }

    /**
     * @param string $contentType
     * @param Entity $content
     *
     * @return ExtendedReportCreatorSvc|ReportCreatorSvc
     *
     * @throws ExceptionReply
     */
    protected function setupReportCreate($contentType, Entity $content)
    {
        $originalMessage = $this->filter('message', 'str');
        $reportReasonId = $this->filter('reason_id', 'uint');
        $reportReason = null;
        $request = $this->request();

        if ($reportReasonId)
        {
            // create original reason backup
            $originalMessage = $request->filter('message', 'str');

            $reportReason = $this->assertReportReasonExists($reportReasonId);

            // set report reason in input
            $this->request()->set('message', $reportReason->reason->render('raw'));
        }

        /** @var ExtendedReportCreatorSvc $reportCreatorSvc */
        $reportCreatorSvc = parent::setupReportCreate($contentType, $content);

        // set the current reason for tracking purposes
        $reportCreatorSvc->setReportReason($reportReason);

        if ($reportReason)
        {
            // restore original message (even if it's empty)
            $request->set('message', $originalMessage);
        }

        return $reportCreatorSvc;
    }

    /**
     * @param int|null $reportReasonId
     * @param array $with
     * @param string $phraseKey
     *
     * @return ReportReasonEntity|Entity
     *
     * @throws ExceptionReply
     */
    protected function assertReportReasonExists(?int $reportReasonId, array $with = [], string $phraseKey = 'tckReportReasons_requested_report_reason_not_found') : ReportReasonEntity
    {
        /** @var ReportReasonEntity $reportReason */
        $reportReason = $this->assertRecordExists(
            'TickTackk\ReportReasons:ReportReason',
            $reportReasonId, $with,
            $phraseKey
        );

        if (!$reportReason->active)
        {
            throw $this->exception($this->noPermission(\XF::phrase($phraseKey)));
        }

        return $reportReason;
    }

    /**
     * @return HttpRequest
     */
    protected function request() : HttpRequest
    {
        return $this->request;
    }

    /**
     * @return Repository|ReportReasonRepo
     */
    protected function getReportReasonRepo() : ReportReasonRepo
    {
        return $this->repository('TickTackk\ReportReasons:ReportReason');
    }
}