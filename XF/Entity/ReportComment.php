<?php

namespace TickTackk\ReportReasons\XF\Entity;

use XF\Mvc\Entity\Structure as EntityStructure;

/**
 * Class ReportComment
 * 
 * Extends \XF\Entity\ReportComment
 *
 * @package TickTackk\ReportReasons\XF\Entity
 *
 * COLUMNS
 * @property null|int tck_report_reason_id
 */
class ReportComment extends XFCP_ReportComment
{
    /**
     * @param EntityStructure $structure
     *
     * @return EntityStructure
     */
    public static function getStructure(EntityStructure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns['tck_report_reason_id'] = ['type' => static::UINT, 'default' => 0, 'nullable' => true];
    
        return $structure;
    }
}