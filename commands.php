<?php

namespace Settings\Commands;

use SplitPHP\Cli;
use SplitPHP\Utils;

class Commands extends Cli
{
  public function init(): void
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

    // Help command
    $this->addCommand('help', function () {
      /** @var \Utils\Services\CliHelper $helper */
      $helper = $this->getService('utils/clihelper');
      Utils::printLn($helper->ansi(strtoupper("Welcome to the Settings Help Center!"), 'color: magenta; font-weight: bold'));

      // 1) Define metadata for each command
      $commands = [
        ':from:context'   => [
          'usage' => 'settings:from:context [--context=<context>]',
          'desc'  => 'Show an object containing all settings from a context.',
          'flags' => [
            '--context=<context>'          => 'Context from which you want to see all settings',
          ],
        ],
        ':change' => [
          'usage' => 'settings:change',
          'desc'  => 'Interactively add or change a setting.',
        ],
        ':remove' => [
          'usage' => 'settings:remove [--context=<context>]',
          'desc'  => 'Delete a setting by its context and field name.',
          'flags' => [
            '--context=<context>'          => 'Context from which to remove the setting',
          ],
        ],
        ':help'             => [
          'usage' => 'settings:help',
          'desc'  => 'Show this help screen.',
        ],
      ];

      // 2) Summary table
      Utils::printLn($helper->ansi("\nAvailable commands:\n", 'color: cyan; text-decoration: underline'));

      $rows = [
        [
          'cmd'  => 'settings:from:context',
          'desc' => 'Show an object containing all settings from a context.',
          'opts' => '--context',
        ],
        [
          'cmd'  => 'settings:change',
          'desc' => 'Interactively add or change a setting',
          'opts' => '(no flags)',
        ],
        [
          'cmd'  => 'settings:remove',
          'desc' => 'Delete a setting by its context and field name',
          'opts' => '--context',
        ],
        [
          'cmd'  => 'settings:help',
          'desc' => 'Show this help screen',
          'opts' => '(no flags)',
        ],
      ];

      $helper->table($rows, [
        'cmd'  => 'Command',
        'desc' => 'Description',
        'opts' => 'Options',
      ]);

      // 3) Detailed usage lists
      foreach ($commands as $cmd => $meta) {
        Utils::printLn($helper->ansi("\n{$cmd}", 'color: yellow; font-weight: bold'));
        Utils::printLn("  Usage:   {$meta['usage']}");
        Utils::printLn("  Purpose: {$meta['desc']}");

        if (!empty($meta['flags'])) {
          Utils::printLn("  Options:");
          $flagLines = [];
          foreach ($meta['flags'] as $flag => $explain) {
            $flagLines[] = "{$flag}  — {$explain}";
          }
          $helper->listItems($flagLines, false, '    •');
        }
      }

      Utils::printLn(''); // trailing newline
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
