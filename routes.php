<?php

namespace Settings\Routes;

use SplitPHP\Request;
use SplitPHP\WebService;
use SplitPHP\Exceptions\Unauthorized;

class Settings extends WebService
{
  public function init()
  {
    $this->setAntiXsrfValidation(false);
    /////////////////
    // GENERAL SETTINGS ENDPOINTS:
    /////////////////
    $this->addEndpoint('GET', "/v1/from-context/?context?", function (Request $request) {
      $context = $request->getRoute()->params['context'];

      $object = $this->getService('settings/settings')->contextObject($context);

      if (empty($object)) return $this->response->withStatus(404);

      return $this->response
        ->withStatus(200)
        ->withData($object, false);
    });

    $this->addEndpoint('GET', "/v1/setting/?context?/?fieldname?", function (Request $request) {
      $context = $request->getRoute()->params['context'];
      $fieldname = $request->getRoute()->params['fieldname'];

      $record = $this->getService('settings/settings')->get($context, $fieldname);

      if (empty($record)) return $this->response->withStatus(404);

      return $this->response
        ->withStatus(200)
        ->withData($record, false);
    });

    $this->addEndpoint('PUT', "/v1/setting", function (Request $request) {
      $this->auth([
        'STT_SETTINGS' => 'U'
      ]);

      $body = $request->getBody();

      foreach ($body as $field) {
        // Array de Objetos
        if (is_array($field)) {
          $this->getService('settings/settings')
            ->change($field['ds_context'], $field['ds_fieldname'], $field['tx_fieldvalue'], $field['ds_format']);
        } else {
          // Objeto (Array associativo)
          $this->getService('settings/settings')
            ->change($body['ds_context'], $body['ds_fieldname'], $body['tx_fieldvalue'], $body['ds_format']);
          break;
        }
      }

      return $this->response->withStatus(204);
    }, true);

    /////////////////
    // CUSTOM FIELDS ENDPOINTS:
    /////////////////
    $this->addEndpoint('GET', "/v1/custom-field/?entityName?", function (Request $request) {
      $entityName = $request->getRoute()->params['entityName'];

      return $this->response
        ->withStatus(200)
        ->withData($this->getService('settings/customfield')->fieldsOfEntity($entityName));
    });

    $this->addEndpoint('POST', "/v1/custom-field", function (Request $request) {
      $this->auth([
        'STT_SETTINGS_CUSTOMFIELD' => 'C'
      ]);

      $data = $request->getBody();
      return $this->response
        ->withStatus(201)
        ->withData($this->getService('settings/customfield')->createField($data));
    }, true);

    $this->addEndpoint('DELETE', "/v1/custom-field/?entityName?/?fieldName?", function (Request $request) {
      $this->auth([
        'STT_SETTINGS_CUSTOMFIELD' => 'D'
      ]);

      $entityName = $request->getRoute()->params['entityName'];
      $fieldName = $request->getRoute()->params['fieldName'];

      $deleted = $this->getService('settings/customfield')->deleteField($entityName, $fieldName);

      if (!$deleted) return $this->response->withStatus(404);

      return $this->response
        ->withStatus(204);
    }, true);
  }

  private function auth(array $permissions)
  {
    if (!$this->getService('modcontrol/control')->moduleExists('iam')) return;

    // Auth user login:
    if (!$this->getService('iam/session')->authenticate())
      throw new Unauthorized("Não autorizado.");

    // Validate user permissions:
    $this->getService('iam/permission')
      ->validatePermissions($permissions);
  }
}
