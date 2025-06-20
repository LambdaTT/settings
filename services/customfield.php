<?php

namespace Settings\Services;

use SplitPHP\Service;
use SplitPHP\Database\Dao;
use Exception;

class CustomField extends Service
{
  const TABLE = "STT_SETTINGS_CUSTOMFIELD";
  const TABLE_VAL = "STT_SETTINGS_CUSTOMFIELD_VALUE";

  public function fieldsOfEntity($entityName)
  {
    return $this->getDao(self::TABLE)
      ->filter('ds_entityname')->equalsTo($entityName)
      ->find();
  }

  public function createField($data)
  {
    $data = $this->getService('utils/misc')->dataBlackList($data, [
      'id_stt_settings_customfield',
      'ds_fieldname'
    ]);

    $data['ds_fieldname'] = $this->getService('utils/misc')->stringToSlug($data['ds_fieldlabel']);

    // Validation
    $record = $this->getDao(self::TABLE)
      ->filter('ds_entityname')->equalsTo($data['ds_entityname'])
      ->and('ds_fieldname')->equalsTo($data['ds_fieldname'])
      ->find();

    if(!empty($record)) throw new Exception ("Já existe um campo {$data['ds_fieldlabel']} neste cadastro.", CONFLICT);

    if(isset($data['do_fieldtype'])){
      if(!in_array($data['do_fieldtype'], ['T','N']))
        throw new Exception('Preenchimento incorreto do "Tipo de campo".', VALIDATION_FAILED);
    }
    
    if(isset($data['do_is_required'])) {
      if(!in_array($data['do_is_required'], ['Y','N']))
        throw new Exception('Preenchimento incorreto do campo "Obrigatório".', VALIDATION_FAILED);
    }

    return $this->getDao(self::TABLE)->insert($data);
  }

  public function deleteField($entityName, $fieldName)
  {
    return (bool) $this->getDao(self::TABLE)
      ->filter('ds_entityname')->equalsTo($entityName)
      ->and('ds_fieldname')->equalsTo($fieldName)
      ->delete();
  }

  /////////////////
  // VALUE FUNCTIONS
  /////////////////

  public function getValuesOfRegister($entityName, $entityId)
  {
    $query = "SELECT f.ds_fieldname, val.tx_value 
              FROM STT_SETTINGS_CUSTOMFIELD f
              LEFT JOIN STT_SETTINGS_CUSTOMFIELD_VALUE val
              ON val.id_stt_settings_customfield = f.id_stt_settings_customfield
              AND val.id_reference_entity = ?entityId?
              WHERE f.ds_entityname = ?entityName?";

    $result = [];

    //  Traz os valores de todos os 'campos personalizados'
    $this->getDao(self::TABLE)
      ->filter('entityName')->equalsTo($entityName)
      ->and('entityId')->equalsTo($entityId)
      ->fetch(
          function($item) use (&$result) {$result[$item->ds_fieldname] = empty($item->tx_value) ? null : $item->tx_value;}, 
          $query
      );

    return (object) $result;
  }

  public function setValuesOfRegister($entityName, $entityId, $data)
  {
    // Filter $data
    $data = $this->filterCustomFieldData($entityName, $data);
    
    // Removes old values
    $deleted = $this->removeValuesOfRegister($entityName, $entityId);

    // Do the update
    $updated = $this->getDao(self::TABLE)
      ->filter('ds_entityname')->equalsTo($entityName) 
      ->fetch(function($field) use ($entityId, $data) {
          if($field->do_is_required === 'Y' && empty($data[$field->ds_fieldname]))
            throw new Exception("O campo {$field->ds_fieldlabel} é obrigatório", BAD_REQUEST);       
          
          if(empty($data[$field->ds_fieldname])) return;

          eval($field->tx_rules);

          $this->getDao(self::TABLE_VAL)->insert([
            'id_stt_settings_customfield' => $field->id_stt_settings_customfield,
            'id_reference_entity' => $entityId,
            'tx_value' => $data[$field->ds_fieldname]
          ]);
        });

    Dao::flush();
    
    if($deleted) return count($updated);

    return $this->getValuesOfRegister($entityName, $entityId);
  }

  public function removeValuesOfRegister($entityName, $entityId)
  {
    $deleteCount = 0;

    $this->getDao(self::TABLE)
      ->filter('ds_entityname')->equalsTo($entityName)
      ->fetch(function($field) use ($entityId, &$deleteCount) {
          $deleteCount += $this->getDao(self::TABLE_VAL)
            ->filter('id_stt_settings_customfield')->equalsTo($field->id_stt_settings_customfield)
            ->and('id_reference_entity')->equalsTo($entityId)
            ->delete();
        });

    return $deleteCount;
  }

 /**
   * Removes any information that is not related to custom fields.
   * 
   * @param   string  $entityName The name of the entity that serves as a reference.
   * @param   array   $data       The array to be filtered.
   * @return  array   Returns the filtered $data array, containing only custom-field-related information.
   */
  public function filterCustomFieldData($entityName, $data)
  {
    $keys = [];

    foreach($this->fieldsOfEntity($entityName) as $field){
      $keys[] = $field->ds_fieldname;
    }

    return $this->getService('utils/misc')->dataWhiteList($data, $keys);
  }

  /**
   * Cleans the content of the $data array, removing any information related to custom fields.
   * 
   * @param   string  $entityName The name of the entity that serves as a reference.
   * @param   array   $data       The array containing the data to be cleaned.
   * @return  array   Returns the cleaned $data array, with all custom-field-related information removed.
   */
  public function removeCustomFieldData($entityName, $data)
  {
    $keys = [];

    foreach($this->fieldsOfEntity($entityName) as $field){
      $keys[] = $field->ds_fieldname;
    }

    return $this->getService('utils/misc')->dataBlackList($data, $keys);
  }
}