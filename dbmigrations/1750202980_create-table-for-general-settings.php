<?php

namespace Settings\Migrations;

use SplitPHP\DbMigrations\Migration;
use SplitPHP\Database\DbVocab;

class CreateTableForGeneralSettings extends Migration
{
  public function apply()
  {
    $this->Table('STT_SETTINGS', 'General Settings')
      ->id('id_stt_settings') // int primary key auto increment
      ->datetime('dt_updated')->setDefaultValue(DbVocab::SQL_CURTIMESTAMP())
      ->int('id_iam_user_updated')->nullable()->setDefaultValue(null)
      ->string('ds_context', 60)
      ->string('ds_format', 20)->setDefaultValue('text')
      ->string('ds_fieldname', 60)
      ->text('tx_fieldvalue')->nullable()->setDefaultValue(null);
  }
}
