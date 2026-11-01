<?php

namespace Settings\Services;

use Exception;
use SplitPHP\Exceptions\BadRequest;
use SplitPHP\EventDispatcher;
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

    // An empty string from the client means the field was cleared: persist it as
    // NULL (not '') so every read treats "unconfigured" uniformly across all
    // formats. The 'file' block below already handles its own null/'' case.
    if ($value === '') $value = null;

    if ($format == 'file' && $this->getService('modcontrol/control')->moduleExists('filemanager')) {
      if (isset($_FILES[$fieldname]) && $_FILES[$fieldname]['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = [...$_FILES[$fieldname]];
        $file = $this->getService('filemanager/file')->add($upload['name'], $upload['tmp_name'], 'Y');
        $value = $file->id_fmn_file;
        if (!empty($record?->tx_fieldvalue)) {
          $this->getService('filemanager/file')->remove(['id_fmn_file' => $record->tx_fieldvalue]);
        }
      } else {
        if ($value === '' || $value === null) {
          if (!empty($record?->tx_fieldvalue)) {
            $this->getService('filemanager/file')->remove(['id_fmn_file' => $record->tx_fieldvalue]);
          }
          $value = null;
        } elseif (gettype($value) === 'object') {
          $file = $this->getService('filemanager/file')->add($value->filename, $value->filepath, 'Y');
          $value = $file->id_fmn_file;
        } else throw new Exception("Invalid file format");
      }
    }

    // Proposed change, announced to the application before it is persisted. The
    // 'settings.setting.changing' event wraps the persist as its main action, so
    // an application listener may reject it (throw → persist skipped), adjust it
    // (mutate $change->value, e.g. clamp), or stop propagation to skip it. When
    // no listener subscribes, the persist runs unchanged. The generic module
    // itself enforces nothing here — it only announces.
    $change = (object) [
      'context'   => $context,
      'fieldname' => $fieldname,
      'value'     => $value,
      'format'    => $format ?: 'text',
      'current'   => $record,
      'user'      => $loggedUser,
    ];

    $result = null;
    EventDispatcher::dispatch(function () use (&$result, $change) {
      $data = [
        'ds_context'          => $change->context,
        'ds_format'           => $change->format,
        'ds_fieldname'        => $change->fieldname,
        'tx_fieldvalue'       => $change->value,
        'id_iam_user_updated' => $change->user?->id_iam_user ?? null,
      ];

      if (empty($change->current)) {
        $data['ds_key'] = 'stt-' . uniqid();
        $result = $this->getDao(self::TABLE)->insert($data);
        return;
      }

      $result = $this->getDao(self::TABLE)
        ->filter('ds_context')->equalsTo($change->context)
        ->and('ds_fieldname')->equalsTo($change->fieldname)
        ->update($data);
    }, 'settings.setting.changing', [$change]);

    return $result;
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
