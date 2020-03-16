<?php

namespace TickTackk\ReportReasons\XF\Service\Report\Exception;

use Throwable;

/**
 * Class InvalidReportReasonProvidedException
 *
 * @package TickTackk\ReportReasons\XF\Service\Report\Exception
 */
class InvalidReportReasonProvidedException extends \InvalidArgumentException
{
    /**
     * @var mixed
     */
    protected $reportReason;

    /**
     * InvalidReportReasonProvidedException constructor.
     *
     * @param mixed $reportReason
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($reportReason, $code = 0, Throwable $previous = null)
    {
        $this->reportReason = $reportReason;

        parent::__construct('Invalid report reason provided.', $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getReportReason()
    {
        return $this->reportReason;
    }
}