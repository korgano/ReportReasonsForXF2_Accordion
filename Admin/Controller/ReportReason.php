<?php

namespace TickTackk\ReportReasons\Admin\Controller;

use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\Delete as DeleteControllerPlugin;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\ControllerPlugin\AbstractPlugin as AbstractControllerPlugin;
use XF\Mvc\Reply\Error as ErrorReply;
use XF\Mvc\Reply\Redirect as RedirectReply;
use XF\Mvc\Reply\View as ViewReply;
use TickTackk\ReportReasons\Repository\ReportReason as ReportReasonRepo;

/**
 * Class ReportReason
 *
 * @package TickTackk\ReportReasons\Admin\Controller
 */
class ReportReason extends AbstractController
{
    /**
     * @param string $action
     * @param ParameterBag $params
     *
     * @throws ExceptionReply
     */
    protected function preDispatchController($action, ParameterBag $params) : void
    {
        $this->assertAdminPermission('tckReportReasons');
    }

    /**
     * @return ViewReply
     */
    public function actionIndex() : ViewReply
    {
        $reportReasonRepo = $this->getReportReasonRepo();
        $reportReasonFinder = $reportReasonRepo->findReportReasonsForList();

        $viewParams = [
            'reportReasons' => $reportReasonFinder->fetch()
        ];

        return $this->view(
            'TickTackk\ReportReasons:ReportReason\Listing',
            'tckReportReasons_report_reason_list',
            $viewParams
        );
    }

    /**
     * @param ReportReasonEntity $reportReason
     *
     * @return ViewReply
     */
    protected function reportReasonAddEdit(ReportReasonEntity $reportReason) : ViewReply
    {
        $viewParams = [
            'reportReason' => $reportReason
        ];
        return $this->view(
            'TickTackk\ReportReasons:ReportReason\Edit',
            'tckReportReasons_report_reason_edit',
            $viewParams
        );
    }

    /**
     * @param ParameterBag $parameterBag
     *
     * @return ViewReply
     *
     * @throws ExceptionReply
     */
    public function actionEdit(ParameterBag $parameterBag) : ViewReply
    {
        $reportReason = $this->assertReportReasonExists($parameterBag->reason_id);

        return $this->reportReasonAddEdit($reportReason);
    }

    /**
     * @return ViewReply
     */
    public function actionAdd() : ViewReply
    {
        /** @var ReportReasonEntity $reportReason */
        $reportReason = $this->em()->create('TickTackk\ReportReasons:ReportReason');
        return $this->reportReasonAddEdit($reportReason);
    }

    /**
     * @param ReportReasonEntity $reportReason
     *
     * @return FormAction
     */
    protected function reportReasonSaveProcess(ReportReasonEntity $reportReason) : FormAction
    {
        $formAction = $this->formAction();

        $input = $this->filter([
            'reason' => 'str',
            'explain' => 'str'
        ]);

        $formAction->basicEntitySave($reportReason, []);

        $formAction->validate(function (FormAction $formAction) use($input)
        {
            if ($input['reason'] === '')
            {
                $formAction->logError(\XF::phrase('tckReportReasons_please_enter_valid_reason'));
            }
        });

        $formAction->apply(function () use($input, $reportReason)
        {
            $reasonPhrase = $reportReason->getMasterReason();
            $reasonPhrase->phrase_text = $input['reason'];
            $reasonPhrase->save();

            $explainPhrase = $reportReason->getMasterExplain();
            $explainPhrase->phrase_text = $input['explain'];
            $explainPhrase->save();
        });

        return $formAction;
    }

    /**
     * @param ParameterBag $parameterBag
     *
     * @return RedirectReply
     *
     * @throws ExceptionReply
     * @throws \XF\PrintableException
     */
    public function actionSave(ParameterBag $parameterBag) : RedirectReply
    {
        $this->assertPostOnly();

        if ($parameterBag->reason_id)
        {
            $reportReason = $this->assertReportReasonExists($parameterBag->reason_id);
        }
        else
        {
            /** @var ReportReasonEntity $reportReason */
            $reportReason = $this->em()->create('TickTackk\ReportReasons:ReportReason');
        }

        $this->reportReasonSaveProcess($reportReason)->run();

        return $this->redirect($this->buildLink('report-reasons', $reportReason));
    }

    /**
     * @param ParameterBag $parameterBag
     *
     * @return ErrorReply|RedirectReply|ViewReply
     *
     * @throws ExceptionReply
     */
    public function actionDelete(ParameterBag $parameterBag)
    {
        $reportReason = $this->assertReportReasonExists($parameterBag->reason_id);

        return $this->getDeleteControllerPlugin()->actionDelete(
            $reportReason,
            $this->buildLink('report-reasons/delete', $reportReason),
            $this->buildLink('report-reasons/edit', $reportReason),
            $this->buildLink('report-reasons'),
            $reportReason->reason
        );
    }

    /**
     * @param int|null $reportReasonId
     * @param array $with
     *
     * @return ReportReasonEntity|Entity
     *
     * @throws ExceptionReply
     */
    protected function assertReportReasonExists(?int $reportReasonId, array $with = []) : ReportReasonEntity
    {
        return $this->assertRecordExists(
            'TickTackk\ReportReasons:ReportReason',
            $reportReasonId, $with,
            'tckReportReasons_requested_report_reason_not_found'
        );
    }

    /**
     * @return AbstractControllerPlugin|DeleteControllerPlugin
     */
    protected function getDeleteControllerPlugin() : DeleteControllerPlugin
    {
        return $this->plugin('XF:Delete');
    }

    /**
     * @return Repository|ReportReasonRepo
     */
    protected function getReportReasonRepo() : ReportReasonRepo
    {
        return $this->repository('TickTackk\ReportReasons:ReportReason');
    }
}