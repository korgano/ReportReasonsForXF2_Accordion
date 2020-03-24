<?php

namespace TickTackk\ReportReasons\Admin\Controller;

use SV\ReportCentreEssentials\Entity\ReportQueue as ReportQueueEntity;
use SV\ReportCentreEssentials\Repository\ReportQueue as ReportQueueRepo;
use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use TickTackk\ReportReasons\Listener;
use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\Delete as DeleteControllerPlugin;
use XF\ControllerPlugin\Sort as SortControllerPlugin;
use XF\ControllerPlugin\Toggle as ToggleControllerPlugin;
use XF\ControllerPlugin\Xml as XmlControllerPlugin;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Exception as ExceptionReply;
use XF\ControllerPlugin\AbstractPlugin as AbstractControllerPlugin;
use XF\Mvc\Reply\Error as ErrorReply;
use XF\Mvc\Reply\Message as MessageReply;
use XF\Mvc\Reply\Redirect as RedirectReply;
use XF\Mvc\Reply\View as ViewReply;
use TickTackk\ReportReasons\Repository\ReportReason as ReportReasonRepo;
use XF\AddOn\Manager as AddOnManager;

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
            'reportReasons' => $reportReasonFinder->fetch(),
            'exportView' => $this->filter('export', 'bool')
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
        $reportQueues = null;
        if (Listener::isReportCentreEssentialsInstalled($this->app()))
        {
            $reportQueueRepo = $this->getReportQueueRepo();
            $reportQueueFinder = $reportQueueRepo->findReportQueues();
            $reportQueues = $reportQueueFinder->fetch();
        }

        $viewParams = [
            'reportReason' => $reportReason,

            'reportQueues' => $reportQueues
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

        $entityInput = $this->filter([
            'display_order' => 'uint',
            'active' => 'bool'
        ]);
        if (Listener::isReportCentreEssentialsInstalled($this->app()))
        {
            $entityInput['report_queue_id'] = $this->filter('report_queue_id', '?uint');
        }

        $phraseInput = $this->filter([
            'reason' => 'str',
            'explain' => 'str'
        ]);

        $formAction->basicEntitySave($reportReason, $entityInput);
        $formAction->validate(function (FormAction $formAction) use($phraseInput)
        {
            if ($phraseInput['reason'] === '')
            {
                $formAction->logError(\XF::phrase('tckReportReasons_please_enter_valid_reason'));
            }
        });

        $formAction->apply(function () use($phraseInput, $reportReason)
        {
            $reasonPhrase = $reportReason->getMasterReason();
            $reasonPhrase->phrase_text = $phraseInput['reason'];
            $reasonPhrase->save();

            $explainPhrase = $reportReason->getMasterExplain();
            $explainPhrase->phrase_text = $phraseInput['explain'];
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
     * @return AbstractReply|RedirectReply|ViewReply
     */
    public function actionSort() : AbstractReply
    {
        $reportReasonRepo = $this->getReportReasonRepo();
        $reportReasonFinder = $reportReasonRepo->findReportReasonsForList();
        $reportReasons = $reportReasonFinder->fetch();

        if ($this->isPost())
        {
            $sortData = $this->filter('report_reasons', 'json-array');

            $sorter = $this->getSortControllerPlugin();
            $sorter->sortFlat($sortData, $reportReasons);

            return $this->redirect($this->buildLink('report-reasons'));
        }

        $viewParams = [
            'reportReasons' => $reportReasons
        ];
        return $this->view(
            'TickTackk\ReportReasons:ReportReason\Sort',
            'tckReportReasons_report_reason_sort',
            $viewParams
        );
    }

    /**
     * @return MessageReply
     */
    public function actionToggle() : MessageReply
    {
        $toggleControllerPlugin = $this->getToggleControllerPlugin();
        return $toggleControllerPlugin->actionToggle('TickTackk\ReportReasons:ReportReason');
    }

    /**
     * @return ViewReply
     *
     * @throws ExceptionReply
     */
    public function actionExport() : ViewReply
    {
        $reportReasonRepo = $this->getReportReasonRepo();
        $reportReasonFinder = $reportReasonRepo->getReportReasonFinder()
            ->where('reason_id', $this->filter('export', 'array-uint'));

        $xmlControllerPlugin = $this->getXmlControllerPlugin();
        return $xmlControllerPlugin->actionExport(
            $reportReasonFinder,
            'TickTackk\ReportReasons:ReportReason\Exporter',
            'TickTackk\ReportReasons:ReportReason\Export'
        );
    }

    /**
     * @return ErrorReply|RedirectReply|ViewReply
     *
     * @throws ExceptionReply
     */
    public function actionImport() : AbstractReply
    {
        $xmlControllerPlugin = $this->getXmlControllerPlugin();
        return $xmlControllerPlugin->actionImport(
            'report-reasons',
            'report_reasons',
            'TickTackk\ReportReasons:ReportReason\Importer'
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
     * @return AbstractControllerPlugin|SortControllerPlugin
     */
    protected function getSortControllerPlugin() : SortControllerPlugin
    {
        return $this->plugin('XF:Sort');
    }

    /**
     * @return AbstractControllerPlugin|ToggleControllerPlugin
     */
    protected function getToggleControllerPlugin() : ToggleControllerPlugin
    {
        return $this->plugin('XF:Toggle');
    }

    /**
     * @return AbstractControllerPlugin|XmlControllerPlugin
     */
    protected function getXmlControllerPlugin() : XmlControllerPlugin
    {
        return $this->plugin('XF:Xml');
    }

    /**
     * @return Repository|ReportReasonRepo
     */
    protected function getReportReasonRepo() : ReportReasonRepo
    {
        return $this->repository('TickTackk\ReportReasons:ReportReason');
    }

    /**
     * @return Repository|ReportQueueRepo
     */
    protected function getReportQueueRepo() : ReportQueueRepo
    {
        return $this->repository('SV\ReportCentreEssentials:ReportQueue');
    }
}