<?php

namespace TickTackk\ReportReasons\XF\ControllerPlugin;

use TickTackk\ReportReasons\ControllerPlugin\ReportReason as ReportReasonControllerPlugin;
use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use TickTackk\ReportReasons\Repository\ReportReason as ReportReasonRepo;
use XF\ControllerPlugin\AbstractPlugin as AbstractControllerPlugin;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\View as ViewReply;
use XF\Mvc\Reply\Error as ErrorReply;
use XF\Mvc\Reply\Redirect as RedirectReply;
use XF\Phrase;
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
        $reportReasonRepo = $this->getReportReasonRepo();
        $reportReasonsCount = $reportReasonRepo->findReportReasonsForList()->total();
        if ($reportReasonsCount <= 1)
        {
            return parent::setupReportCreate($contentType, $content);
        }

        $reportReasonId = $this->filter('reason_id', 'int');
        $reportReason = $this->assertReportReasonExists($reportReasonId);

        /** @var ExtendedReportCreatorSvc $reportCreatorSvc */

        $originalMessage = $this->filter('message', 'str');
        if ($originalMessage === '' && $reportReason->reason_id)
        {
            $this->request()->set('message', \XF::generateRandomString(10));
        }

        try
        {
            $reportCreatorSvc = parent::setupReportCreate($contentType, $content);
        }
        catch (ExceptionReply $exception)
        {
            if ($reportReason->reason_id === 0)
            {
                $reply = $exception->getReply();
                if ($reply instanceof ErrorReply)
                {
                    $errors = $reply->getErrors();
                    foreach ($errors AS $index => $error)
                    {
                        if ($error instanceof Phrase
                            && $error->getName() === 'please_enter_reason_for_reporting_this_message'
                        )
                        {
                            $errors[$index] = \XF::phrase(ReportReasonEntity::REASON_EXPLAIN_PHRASE_GROUP . '0');
                        }
                    }
                    $reply->setErrors($errors, false);
                }
            }

            throw $exception;
        }

        // set the current reason for tracking purposes
        $reportCreatorSvc->setReportReason($reportReason, $originalMessage);

        if ($originalMessage === '' && $reportReason->reason_id)
        {
            $this->request()->set('message', $originalMessage);
        }

        return $reportCreatorSvc;
    }

    /**
     * @param int|null $reportReasonId
     * @param array $with
     *
     * @return ReportReasonEntity
     *
     * @throws ExceptionReply
     */
    protected function assertReportReasonExists(?int $reportReasonId, array $with = []) : ReportReasonEntity
    {
        return $this->getReportReasonControllerPlugin()->assertReportReasonExists($reportReasonId, $with);
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

    /**
     * @return AbstractControllerPlugin|ReportReasonControllerPlugin
     */
    protected function getReportReasonControllerPlugin() : ReportReasonControllerPlugin
    {
        return $this->plugin('TickTackk\ReportReasons:ReportReason');
    }
}