<?php

namespace TickTackk\ReportReasons\Entity;

use XF\Mvc\Entity\Entity;
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
 *
 * GETTERS
 * @property Phrase reason
 * @property Phrase explain
 *
 * RELATIONS
 * @property PhraseEntity MasterReason
 * @property PhraseEntity MasterExplain
 */
class ReportReason extends Entity
{
    public const REASON_PHRASE_GROUP = 'tckReportReasons_report_reason.';

    public const REASON_EXPLAIN_PHRASE_GROUP = 'tckReportReasons_report_reason_explain.';

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
            'reason_id' => ['type' => static::UINT, 'autoIncrement' => true, 'nullable' => true]
        ];
        $structure->getters = [
            'reason' => true,
            'explain' => true
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

        return $structure;
    }
}