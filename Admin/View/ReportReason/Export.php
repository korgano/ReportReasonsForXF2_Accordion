<?php

namespace TickTackk\ReportReasons\Admin\View\ReportReason;

use XF\Mvc\View;
use XF\Http\Response as HttpResponse;
use DOMDocument;

/**
 * Class Export
 *
 * @package TickTackk\ReportReasons\Admin\View\ReportReason
 */
class Export extends View
{
    /**
     * @return string
     */
    public function renderXml() : string
    {
        $params = $this->getParams();

        /** @var DOMDocument $document */
        $document = $params['xml'];
        $fileName = $params['fileName'] ?? 'report_reasons.xml';

        $response = $this->getResponse();
        $response->setDownloadFileName($fileName);

        return $document->saveXML();
    }

    /**
     * @return HttpResponse
     */
    protected function getResponse()
    {
        return $this->response;
    }
}