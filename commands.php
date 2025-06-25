<?php

namespace Settings\Commands;

use SplitPHP\Cli;
use SplitPHP\Utils;

class Commands extends Cli
{
  public function init()
  {
    $this->addCommand('from:context', function ($args) {
      Utils::printLn("Welcome to the Settings Management!");
      if (!isset($args[0]) && !isset($args['--context'])) {
        Utils::printLn(" >> Please enter the context for the settings (e.g., 'general', 'user', etc.): ");
      }
      $context = $this->setContext($args);
      $ctxObject = $this->getService('settings/settings')->contextObject($context);
      Utils::printLn();
      Utils::printLn("  >> Settings for context '{$context}':");

      if (empty($ctxObject)) {
        Utils::printLn("  >> No settings found for the context '{$context}'.");
        return;
      }

      foreach ($ctxObject as $key => $value) {
        Utils::printLn("    -> {$key}: {$value}");
      }
    });

    $this->addCommand('change', function () {
      Utils::printLn("Welcome to the Settings Change Command!");
      Utils::printLn("This command will help you change or add a setting.");
      Utils::printLn();
      Utils::printLn(" >> Please follow the prompts to define your setting informations.");
      Utils::printLn();
      Utils::printLn("  >> Add/Change Setting:");
      Utils::printLn("------------------------------------------------------");

      $setting = $this->getService('utils/clihelper')->inputForm([
        'context' => [
          'label'    => 'Context',
          'required' => true,
          'length'   => 60,
        ],
        'format' => [
          'label'    => 'Type (text, json, etc.)',
          'required' => false,
          'length'   => 20,
          'default'  => 'text',
        ],
        'fieldname' => [
          'label'    => 'Name',
          'required' => true,
          'length'   => 60,
        ],
        'value' => [
          'label'    => 'Value',
          'required' => true,
          'length'   => 65535,
        ],
      ]);
      Utils::printLn();

      $record = $this->getService('settings/settings')->change(...(array)$setting);

      Utils::printLn("  >> Setting added successfully!");
      foreach ($record as $key => $value) {
        Utils::printLn("    -> {$key}: {$value}");
      }
    });

    $this->addCommand('remove', function ($args) {
      Utils::printLn("Welcome to the Settings Removal Command!");
      Utils::printLn();

      if (!isset($args[0]) && !isset($args['--context'])) {
        Utils::printLn(" >> Enter context:");
      }
      $context = $this->setContext($args);
      Utils::printLn(" >> Enter setting field name:");
      $fieldname = $this->getService('utils/misc')->persistentCliInput(
        function ($input) {
          return !empty($input) && strlen($input) <= 60;
        },
        "Field name must be a non-empty string with a maximum length of 60 characters."
      );

      $rows = $this->getService('settings/settings')->remove($context, $fieldname);
      Utils::printLn($rows ? "  >> Setting removed successfully!" : "  >> No setting '{$fieldname}' found to remove in context '{$context}'.");
    });
  }

  private function setContext($args)
  {
    return $args['--context'] ?? ($args[0] ?? $this->getService('utils/misc')->persistentCliInput(
      function ($input) {
        return !empty($input) && strlen($input) <= 60;
      },
      "Context must be a non-empty string with a maximum length of 60 characters.",
    ));
  }
}
