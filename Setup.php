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
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Manager as EntityManager;

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
            $table->addColumn('report_queue_id', 'int')->nullable();
            $table->addColumn('display_order', 'int')->setDefault(0);
            $table->addColumn('active', 'tinyint');

            $table->addKey('display_order');
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

    /**
     * @throws \XF\Db\Exception
     */
    public function installStep3() : void
    {
        $db = $this->db();

        $originalSqlMode = $db->fetchOne('SELECT @@sql_mode');

        $tmpSqlMode = \explode(',', $originalSqlMode);
        $tmpSqlMode[] = 'NO_AUTO_VALUE_ON_ZERO';
        $tmpSqlMode = \implode(',', \array_unique($tmpSqlMode));

        $db->query("SET SESSION sql_mode = ?", $tmpSqlMode);

        $db->insert('xf_tck_report_reasons_report_reason', [
            'reason_id' => 0,
            'report_queue_id' => 1, // default is 1
            'display_order' => 100,
            'active' => 1
        ]);

        $db->query("SET SESSION sql_mode = ?", $originalSqlMode);
    }

    /**
     * @throws \XF\PrintableException
     */
    public function installStep4() : void
    {
        $db = $this->db();
        $db->beginTransaction();

        $phrases = [
            ReportReasonEntity::REASON_PHRASE_GROUP . '0' => 'Other',
            ReportReasonEntity::REASON_EXPLAIN_PHRASE_GROUP . '0' => 'Please use "More information" field to explain further.'
        ];

        foreach ($phrases AS $title => $phraseText)
        {
            $phrase = $this->em()->findOne('XF:Phrase', ['title' => $title]);
            if (!$phrase)
            {
                /** @var PhraseEntity $phrase */
                $phrase = $this->em()->create('XF:Phrase');
                $phrase->language_id = 0;
                $phrase->title = $title;
                $phrase->phrase_text = $phraseText;
                $phrase->save(false, false);
            }
        }

        $db->commit();
    }

    public function upgrade1000012Step1() : void
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_tck_report_reasons_report_reason', function (AlterDbSchema $table)
        {
            $table->addColumn('report_queue_id', 'int')->nullable();
        });
    }

    public function upgrade1000013Step1() : void
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_tck_report_reasons_report_reason', function (AlterDbSchema $table)
        {
            $table->addColumn('display_order', 'int')->setDefault(0);
            $table->addKey('display_order');
        });
    }

    public function upgrade1000013Step2() : void
    {
        $db = $this->db();

        $reportReasons = $db->fetchAllKeyed("
            SELECT *
            FROM xf_tck_report_reasons_report_reason
            ORDER BY reason_id
        ", 'reason_id');

        if (\count($reportReasons))
        {
            $currentDisplayOrder = 5;

            foreach ($reportReasons AS $reportReason)
            {
                $db->update('xf_tck_report_reasons_report_reason', [
                    'display_order' => $currentDisplayOrder
                ], 'reason_id = ?', $reportReason['reason_id']);

                $currentDisplayOrder += 5;
            }
        }
    }

    public function upgrade1000013Step3() : void
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_tck_report_reasons_report_reason', function (AlterDbSchema $table)
        {
            $table->addColumn('active', 'tinyint');
        });
    }

    public function upgrade1000013Step4() : void
    {
        $this->db()->update('xf_tck_report_reasons_report_reason', [
            'active' => 1
        ], 'active = ?', 0);
    }

    /**
     * @throws \XF\Db\Exception
     */
    public function upgrade1010070Step1() : void
    {
        $this->installStep3();
    }

    /**
     * @throws \XF\PrintableException
     */
    public function upgrade1010070Step2() : void
    {
        $this->installStep4();
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

    /**
     * @return EntityManager
     */
    protected function em() : EntityManager
    {
        return $this->app()->em();
    }

    /**
     * @param string $identifier
     *
     * @return Finder
     */
    protected function finder(string $identifier) : Finder
    {
        return $this->app()->finder($identifier);
    }
}