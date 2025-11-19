<?php

namespace Settings\Services;

use SplitPHP\Exceptions\BadRequest;
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
          case 'file':
            $record->tx_fieldvalue = $this->getService('filemanager/file')->get(['id_fmn_file' => $record->tx_fieldvalue])?->ds_url;
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
      case 'file':
        $record->tx_fieldvalue = $this->getService('filemanager/file')->get(['id_fmn_file' => $record->tx_fieldvalue])?->ds_url;
        break;
    }

    return $record;
  }

  public function change($context, $fieldname, $value, $format = 'text')
  {
    // Set refs
    $record = $this->get($context, $fieldname);
    $loggedUser = $this->getService('iam/session')->getLoggedUser();

    if ($format == 'file' && $this->getService('modcontrol/control')->moduleExists('filemanager')) {
      if (!isset($_FILES[$fieldname]))
        throw new BadRequest("Nenhum arquivo foi enviado para o campo '$fieldname'");

      $upload = [...$_FILES[$fieldname]];
      $file = $this->getService('filemanager/file')->create($upload['name'], $upload['tmp_name'], 'Y');
      $value = $file->id_fmn_file;
      $this->getService('filemanager/file')->remove(['id_fmn_file' => $record?->tx_fieldvalue ?: null]);
    }

    // Set values
    $data = [
      'ds_context' => $context,
      'ds_format' => $format ?: 'text',
      'ds_fieldname' => $fieldname,
      'tx_fieldvalue' => $value,
      'id_iam_user_updated' => $loggedUser?->id_iam_user ?? null
    ];

    if (empty($record)) return $this->getDao(self::TABLE)->insert($data);

    return $this->getDao(self::TABLE)
      ->filter('ds_context')->equalsTo($context)
      ->and('ds_fieldname')->equalsTo($fieldname)
      ->update($data);
  }

  public function remove($context, $fieldname)
  {
    $record = $this->get($context, $fieldname);
    $fileModExist = $this->getService('modcontrol/control')->moduleExists('filemanager');
    $isFile = $fileModExist && $record?->ds_format == 'file';
    $value = $record?->tx_fieldvalue;

    if ($isFile && !empty($value))
      $this->getService('filemanager/file')->remove(['id_fmn_file' => $value]);

    return $this->getDao(self::TABLE)
      ->filter('ds_context')->equalsTo($context)
      ->and('ds_fieldname')->equalsTo($fieldname)
      ->delete();
  }
}
