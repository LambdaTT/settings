<?php

namespace Settings\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class AddUserCreatedFk extends Migration
{
  public function apply()
  {
    $this->Table('STT_SETTINGS')
      ->Foreign('id_iam_user_updated')
      ->references('id_iam_user')
      ->atTable('IAM_USER')
      ->onUpdate(DbVocab::FKACTION_CASCADE)
      ->onDelete(DbVocab::FKACTION_SETNULL);
  }
}
