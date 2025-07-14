<?php

namespace Settings\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class CreateTablesRelatedToCustomFields extends Migration
{
  public function apply()
  {
    $this->Table('STT_SETTINGS_CUSTOMFIELD', 'Custom Field')
      ->id('id_stt_settings_customfield') // int primary key auto increment
      ->string('ds_entityname', 60)
      ->string('ds_fieldname', 60)
      ->string('ds_fieldlabel', 60)
      ->string('do_fieldtype', 1)->setDefaultValue('T')
      ->string('do_is_required', 1)->setDefaultValue('N')
      ->text('tx_rules')->nullable()->setDefaultValue(null);

    $this->Table('STT_SETTINGS_CUSTOMFIELD_VALUE', 'Custom Field Value')
      ->id('id_stt_settings_customfield_value') // int primary key auto increment
      ->int('id_stt_settings_customfield')
      ->int('id_reference_entity')
      ->text('tx_rules')
      ->Foreign('id_stt_settings_customfield')
        ->references('id_stt_settings_customfield')
        ->atTable('STT_SETTINGS_CUSTOMFIELD')
        ->onUpdate(DbVocab::FKACTION_CASCADE)
        ->onDelete(DbVocab::FKACTION_CASCADE);
  }
}
