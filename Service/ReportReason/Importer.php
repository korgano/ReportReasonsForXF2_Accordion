<?php

namespace TickTackk\ReportReasons\Service\ReportReason;

use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use XF\Entity\Phrase as PhraseEntity;
use XF\Service\AbstractXmlImport;
use SimpleXMLElement;
use XF\Util\Xml as XmlUtil;

/**
 * Class Importer
 *
 * @package TickTackk\ReportReasons\Service\ReportReason
 */
class Importer extends AbstractXmlImport
{
    /**
     * @param SimpleXMLElement $xml
     *
     * @throws \XF\PrintableException
     */
    public function import(SimpleXMLElement $xml) : void
    {
        $db = $this->db();
        $db->beginTransaction();

        $xmlReportReasons = $xml->report_reason;

        foreach ($xmlReportReasons AS $xmlReportReason)
        {
            $data = $this->getReportReasonDataFromXml($xmlReportReason);
            $phrases = $this->getReportReasonPhrasesFromXml($xmlReportReason);

            /** @var ReportReasonEntity $reportReason */
            $reportReason = $this->em()->create('TickTackk\ReportReasons:ReportReason');
            $reportReason->bulkSet($data);
            $reportReason->save(false, false);

            foreach ($phrases AS $relationName => $phraseText)
            {
                /** @var PhraseEntity $phrase */
                $phrase = $reportReason->getRelationOrDefault($relationName);
                $phrase->phrase_text = $phraseText;
                $phrase->save(false, false);
            }
        }

        $db->commit();
    }

    /**
     * @param SimpleXMLElement $xmlReportReason
     *
     * @return array
     */
    protected function getReportReasonDataFromXml(SimpleXMLElement $xmlReportReason) : array
    {
        $reportReasonData = [];

        foreach ($this->getAttributes() AS $attribute)
        {
            $reportReasonData[$attribute] = $this->getTypeCastedDataValue(
                $attribute, $xmlReportReason[$attribute]
            );
        }

        return $reportReasonData;
    }

    /**
     * @param SimpleXMLElement $xmlReportReason
     *
     * @return array
     */
    protected function getReportReasonPhrasesFromXml(SimpleXMLElement $xmlReportReason) : array
    {
        return [
            'MasterReason' => XmlUtil::processSimpleXmlCdata($xmlReportReason->reason),
            'MasterExplain' => XmlUtil::processSimpleXmlCdata($xmlReportReason->explain)
        ];
    }

    /**
     * @return array
     */
    protected function getAttributes() : array
    {
        return [
            'report_queue_id', 'display_order', 'active'
        ];
    }

    /**
     * @param string $attribute
     * @param mixed $value
     *
     * @return int
     */
    protected function getTypeCastedDataValue(string $attribute, SimpleXMLElement $value)
    {
        switch ($attribute)
        {
            case 'report_queue_id':
            case 'display_order':
                return (int) $value;

            case 'active':
                return ((int) $value) === 1;

            default:
                return (string) $value;
        }
    }
}