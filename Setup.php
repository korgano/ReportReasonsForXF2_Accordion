<?php

namespace TickTackk\ReportReasons;

use TickTackk\ReportReasons\Entity\ReportReason as ReportReasonEntity;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter as AlterDbSchema;
use XF\Db\Schema\Create as CreateDbSchema;
use XF\Entity\Phrase as PhraseEntity;
use XF\Finder\Phrase as PhraseFinder;

/**
 * Class Setup
 *
 * @package TickTackk\ReportReasons
 */
class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	public function installStep1() : void
    {
        $sm = $this->schemaManager();

        $sm->createTable('xf_tck_report_reasons_report_reason', function(CreateDbSchema $table)
        {
            $table->addColumn('reason_id', 'int')->nullable()->autoIncrement()->primaryKey();
        });
    }

    public function installStep2() : void
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_report_comment', function (AlterDbSchema $table)
        {
            $table->addColumn('tck_report_reason_id', 'int')->nullable()->setDefault(null);
            $table->addKey('tck_report_reason_id');
        });
    }

    public function uninstallStep1() : void
    {
        $sm = $this->schemaManager();

        $sm->dropTable('xf_tck_report_reasons_report_reason');
    }

    public function uninstallStep2() : void
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_report_comment', function (AlterDbSchema $table)
        {
            $table->dropIndexes('tck_report_reason_id');
            $table->dropColumns('tck_report_reason_id');
        });
    }

    /**
     * @throws \XF\PrintableException
     */
    public function uninstallStep3() : void
    {
        /** @var PhraseFinder $phraseFinder */
        $phraseFinder = $this->app()->finder('XF:Phrase');
        $phraseFinder->fromAddOn('');
        $phraseFinder->whereOr(
            [
                $phraseFinder->columnUtf8('title'),
                'LIKE',
                $phraseFinder->escapeLike(ReportReasonEntity::REASON_PHRASE_GROUP, '?%')],
            [
                $phraseFinder->columnUtf8('title'),
                'LIKE',
                $phraseFinder->escapeLike(ReportReasonEntity::REASON_EXPLAIN_PHRASE_GROUP, '?%')
            ]
        );

        $db = $this->db();
        $db->beginTransaction();

        /** @var PhraseEntity $phrase */
        foreach ($phraseFinder->fetch() AS $phrase)
        {
            $phrase->delete(false, false);
        }

        $db->commit();
    }
}