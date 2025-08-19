<?php

namespace Settings\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class AddKeyToSettingsTable extends Migration
{
  public function apply()
  {
    $this->Table('STT_SETTINGS')
      ->string('ds_key', 17)
      ->Index('KEY', DbVocab::IDX_UNIQUE)->onColumn('ds_key');
  }
}
