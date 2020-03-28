<?php

namespace TickTackk\ReportReasons\Entity;

use SV\ReportCentreEssentials\Entity\ReportQueue as ReportQueueEntity;
use SV\ReportCentreEssentials\Repository\ReportQueue as ReportQueueRepo;
use TickTackk\ReportReasons\Listener;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Mvc\Entity\Structure as EntityStructure;
use XF\Phrase;
use XF\Entity\Phrase as PhraseEntity;

/**
 * Class ReportReason
 *
 * @package TickTackk\ReportReasons\Entity
 *
 * COLUMNS
 * @property int reason_id
 * @property int|null report_queue_id
 * @property int display_order
 * @property int active
 *
 * GETTERS
 * @property Phrase reason
 * @property Phrase explain
 * @property ReportQueueEntity ReportQueue
 *
 * RELATIONS
 * @property PhraseEntity MasterReason
 * @property PhraseEntity MasterExplain
 * @property ReportQueueEntity ReportQueue_
 */
class ReportReason extends Entity
{
    public const REASON_PHRASE_GROUP = 'tckReportReasons_report_reason.';

    public const REASON_EXPLAIN_PHRASE_GROUP = 'tckReportReasons_report_reason_explain.';

    public const DEFAULT_REPORT_QUEUE_ID = 1;

    /**
     * @return bool
     */
    public function canEdit() : bool
    {
        if ($this->isInsert())
        {
            return true;
        }

        if ($this->reason_id !== 0)
        {
            return true;
        }

        return $this->reason_id !== 0 && $this->isUpdate();
    }

    /**
     * @param int|null $reportQueueId
     *
     * @return bool
     */
    protected function verifyReportQueueId(?int &$reportQueueId) : bool
    {
        if (!Listener::isReportCentreEssentialsInstalled())
        {
            return true; // just accept it
        }

        if ($reportQueueId === null)
        {
            $reportQueueId = 0; // 0 for whatever is the default report queue
            return true;
        }

        if ($reportQueueId === 0) // already fallback to default
        {
            return true;
        }

        /** @var ReportQueueEntity $reportQueue */
        $reportQueue = $this->em()->find('SV\ReportCentreEssentials:ReportQueue', $reportQueueId);
        if (!$reportQueue)
        {
            $this->error(
                \XF::phrase('tckReportReasons_please_select_a_valid_report_queue'),
                'report_queue_id'
            );
            return false;
        }

        return true;
    }

    /**
     * @return Phrase
     */
    public function getReason() : Phrase
    {
        $reasonPhrase = \XF::phrase(static::REASON_PHRASE_GROUP . $this->reason_id);
        $reasonPhrase->fallback(null, '');

        return $reasonPhrase;
    }

    /**
     * @return Phrase
     */
    public function getExplain() : Phrase
    {
        $explainPhrase = \XF::phrase(static::REASON_EXPLAIN_PHRASE_GROUP . $this->reason_id);
        $explainPhrase->fallback(null, '');

        return $explainPhrase;
    }

    /**
     * @return PhraseEntity
     */
    public function getMasterReason() : PhraseEntity
    {
        $phrase = $this->MasterReason;

        if (!$phrase)
        {
            /** @var PhraseEntity $phrase */
            $phrase = $this->em()->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function ()
            {
                return static::REASON_PHRASE_GROUP . $this->reason_id;
            }, 'save');
            $phrase->language_id = 0;
            $phrase->addon_id = '';
        }

        return $phrase;
    }

    /**
     * @return PhraseEntity
     */
    public function getMasterExplain() : PhraseEntity
    {
        $phrase = $this->MasterExplain;

        if (!$phrase)
        {
            /** @var PhraseEntity $phrase */
            $phrase = $this->em()->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function ()
            {
                return static::REASON_EXPLAIN_PHRASE_GROUP . $this->reason_id;
            }, 'save');
            $phrase->language_id = 0;
            $phrase->addon_id = '';
        }

        return $phrase;
    }

    /**
     * @return Entity|ReportQueueEntity
     */
    public function getReportQueue() : ReportQueueEntity
    {
        $reportQueue = $this->ReportQueue_;

        if (!$reportQueue)
        {
            return $this->em()->find(
                'SV\ReportCentreEssentials:ReportQueue',
                static::DEFAULT_REPORT_QUEUE_ID
            );
        }

        return $reportQueue;
    }

    protected function _preSave() : void
    {
        if (!$this->canEdit())
        {
            if ($this->isChanged(['active']))
            {
                $this->error(\XF::phrase('tckReportReasons_default_report_reason_cannot_be_disabled'));
                return;
            }
        }
    }

    protected function _preDelete() : void
    {
        if (!$this->canEdit())
        {
            $this->error(\XF::phrase('tckReportReasons_default_report_reason_cannot_be_deleted'));
            return;
        }
    }

    /**
     * @param EntityStructure $structure
     *
     * @return EntityStructure
     */
    public static function getStructure(EntityStructure $structure)
    {
        $structure->shortName = 'TickTackk\ReportReasons:ReportReason';
        $structure->table = 'xf_tck_report_reasons_report_reason';
        $structure->primaryKey = 'reason_id';
        $structure->columns = [
            'reason_id' => ['type' => static::UINT, 'autoIncrement' => true, 'nullable' => true],
            'report_queue_id' => ['type' => static::UINT, 'default' => 0, 'nullable' => true],
            'display_order' => ['type' => static::UINT, 'forced' => true, 'default' => 1],
            'active' => ['type' => self::BOOL, 'default' => true]
        ];
        $structure->getters = [
            'reason' => true,
            'explain' => true,
            'ReportQueue' => true
        ];
        $structure->relations = [
            'MasterReason' => [
                'entity' => 'XF:Phrase',
                'type' => static::TO_ONE,
                'conditions' => [
                    ['language_id', '=', 0],
                    ['title', '=', static::REASON_PHRASE_GROUP, '$reason_id']
                ]
            ],
            'MasterExplain' => [
                'entity' => 'XF:Phrase',
                'type' => static::TO_ONE,
                'conditions' => [
                    ['language_id', '=', 0],
                    ['title', '=', static::REASON_EXPLAIN_PHRASE_GROUP, '$reason_id']
                ]
            ]
        ];

        if (Listener::isReportCentreEssentialsInstalled())
        {
            $structure->relations['ReportQueue'] = [
                'entity' => 'SV\ReportCentreEssentials:ReportQueue',
                'type' => static::TO_ONE,
                'conditions' => [
                    ['queue_id', '=', '$report_queue_id']
                ],
                'primary' => true
            ];
        }

        return $structure;
    }

    /**
     * @return Repository|ReportQueueRepo
     */
    protected function getReportQueueRepo() : ReportQueueRepo
    {
        return $this->repository('SV\ReportCentreEssentials:ReportQueue');
    }
}