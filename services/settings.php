<?php

namespace Settings\Services;

use SplitPHP\Service;

class Settings extends Service
{
  const TABLE = "STT_SETTINGS";

  public function listByContext($context)
  {
    return $this->getDao(self::TABLE)
      ->filter('ds_context')->equalsTo($context)
      ->fetch(function (&$record) {
        switch ($record->ds_format) {
          case 'json':
            $record->tx_fieldvalue = json_decode($record->tx_fieldvalue);
            break;
        }
      });
  }

  public function contextObject($context)
  {
    $vars = $this->listByContext($context);
    $object = [];
    foreach ($vars as $var) {
      $object[$var->ds_fieldname] = $var->tx_fieldvalue;
    }
    return empty($object) ? null : (object) $object;
  }

  public function get($context, $fieldname)
  {
    $record = $this->getDao(self::TABLE)
      ->filter('ds_context')->equalsTo($context)
      ->and('ds_fieldname')->equalsTo($fieldname)
      ->first();

    if (empty($record)) return null;

    switch ($record->ds_format) {
      case 'json':
        $record->tx_fieldvalue = json_decode($record->tx_fieldvalue);
        break;
    }

    return $record;
  }

  public function change($context, $fieldname, $value, $format = 'text')
  {
    $loggedUser = $this->getService('iam/session')->getLoggedUser();

    // Set values
    $data = [
      'ds_context' => $context,
      'ds_format' => $format ?: 'text',
      'ds_fieldname' => $fieldname,
      'tx_fieldvalue' => $value,
      'id_iam_user_updated' => empty($loggedUser) ? null : $loggedUser->id_iam_user
    ];

    // Set refs
    $record = $this->get($context, $fieldname);

    if (empty($record)) return $this->getDao(self::TABLE)->insert($data);

    return $this->getDao(self::TABLE)
      ->filter('ds_context')->equalsTo($context)
      ->and('ds_fieldname')->equalsTo($fieldname)
      ->update($data);
  }

  public function remove($context, $fieldname)
  {
    return $this->getDao(self::TABLE)
      ->filter('ds_context')->equalsTo($context)
      ->and('ds_fieldname')->equalsTo($fieldname)
      ->delete();
  }
}
