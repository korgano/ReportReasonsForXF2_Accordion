<?php

namespace TickTackk\ReportReasons\Service\ReportReason;

use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use XF\Mvc\Entity\Entity;
use XF\Service\AbstractXmlExport;

/**
 * Class Exporter
 *
 * @package TickTackk\ReportReasons\Service\ReportReason
 */
class Exporter extends AbstractXmlExport
{
    /**
     * @return string
     */
    public function getRootName() : string
    {
        return 'report_reasons';
    }

    /**
     * @return string
     */
    public function getChildName() : string
    {
        return 'report_reason';
    }

    /**
     * @param Entity|ReportReasonEntity $entity
     * @param \DOMElement $node
     */
    protected function exportEntry(Entity $entity, \DOMElement $node) : void
    {
        $childNodes = [
            'reason' => $entity->MasterReason->phrase_text,
            'explain' => $entity->MasterExplain->phrase_text
        ];

        foreach ($childNodes AS $attribute => $value)
        {
            $childNode = $node->ownerDocument->createElement($attribute);

            $this->exportCdata($childNode, $value);
            $node->appendChild($childNode);
        }
    }

    /**
     * @return array
     */
    protected function getAttributes() : array
    {
        return [
            'report_queue_id',
            'display_order',
            'active'
        ];
    }
}