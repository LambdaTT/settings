<?php

namespace Settings\Routes;

use SplitPHP\Request;
use SplitPHP\WebService;

class Settings extends WebService
{
  public function init()
  {
    /////////////////
    // GENERAL SETTINGS ENDPOINTS:
    /////////////////

    $this->addEndpoint('GET', "/v1/from-context/?context?", function (Request $request) {
      $context = $request->getRoute()->params['context'];

      $list = $this->getService('settings/director')->listByContext($context);

      if (empty($list)) return $this->response->withStatus(404);

      return $this->response
        ->withStatus(200)
        ->withData($list, false);
    }, false);

    $this->addEndpoint('GET', "/v1/setting/?context?/?fieldname?", function (Request $request) {
      $context = $request->getRoute()->params['context'];
      $fieldname = $request->getRoute()->params['fieldname'];

      $record = $this->getService('settings/director')->get($context, $fieldname);

      if (empty($record)) return $this->response->withStatus(404);

      return $this->response
        ->withStatus(200)
        ->withData($record, false);
    }, false);

    $this->addEndpoint('PUT', "/v1/setting", function (Request $request) {
      // Auth user login:
      if (!$this->getService('iam/session')->authenticate()) return $this->response->withStatus(401);

      // Validate user permissions:
      $this->getService('iam/permission')->validatePermissions([
        'STT_SETTINGS' => 'U'
      ]);

      $params = $request->getBody();

      foreach ($params as $field) {
        // Array de Objetos
        if (is_array($field)) {
          $this->getService('settings/director')
            ->change($field['ds_context'], $field['ds_fieldname'], $field['tx_fieldvalue'], $field['ds_format']);
        } else {
          // Objeto (Array associativo)
          $this->getService('settings/director')
            ->change($params['ds_context'], $params['ds_fieldname'], $params['tx_fieldvalue'], $field['ds_format']);
          break;
        }
      }

      return $this->response->withStatus(204);
    });

    /////////////////
    // CUSTOM FIELDS ENDPOINTS:
    /////////////////
    $this->addEndpoint('GET', "/v1/custom-field/?entityName?", function (Request $request) {
      $entityName = $request->getRoute()->params['entityName'];

      return $this->response
        ->withStatus(200)
        ->withData($this->getService('settings/customfield')->fieldsOfEntity($entityName));
    }, false);

    $this->addEndpoint('POST', "/v1/custom-field", function (Request $request) {
      // Auth user login:
      if (!$this->getService('iam/session')->authenticate()) return $this->response->withStatus(401);

      // Validate user permissions:
      $this->getService('iam/permission')->validatePermissions([
        'STT_SETTINGS' . '_CUSTOMFIELD' => 'C'
      ]);

      $data = $request->getBody();
      return $this->response
        ->withStatus(201)
        ->withData($this->getService('settings/customfield')->createField($data));
    });

    $this->addEndpoint('DELETE', "/v1/custom-field/?entityName?/?fieldName?", function (Request $request) {
      // Auth user login:
      if (!$this->getService('iam/session')->authenticate()) return $this->response->withStatus(401);

      // Validate user permissions:
      $this->getService('iam/permission')->validatePermissions([
        'STT_SETTINGS' . '_CUSTOMFIELD' => 'D'
      ]);

      $entityName = $request->getRoute()->params['entityName'];
      $fieldName = $request->getRoute()->params['fieldName'];

      $deleted = $this->getService('settings/customfield')->deleteField($entityName, $fieldName);

      if (!$deleted) return $this->response->withStatus(404);

      return $this->response
        ->withStatus(204);
    });
  }
}
