<?php

namespace Settings\Seeds;

use SplitPHP\DbManager\Seed;
use SplitPHP\ModsManager;

class SettingsPermissions extends Seed
{
  public function apply()
  {
    if (!ModsManager::modExists('iam')) return;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Ler Configurações do Sistema')
      ->onField('ds_tag')->setFixedValue('settings.settings.read')
    ;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Gerenciar Configurações do Sistema')
      ->onField('ds_tag')->setFixedValue('settings.settings.manage')
    ;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Ler Campos Personalizados')
      ->onField('ds_tag')->setFixedValue('settings.customfield.read')
    ;

    $this->SeedTable('IAM_PERMISSION', batchSize: 1)
      ->onField('ds_title')->setFixedValue('Gerenciar Campos Personalizados')
      ->onField('ds_tag')->setFixedValue('settings.customfield.manage')
    ;
  }
}
