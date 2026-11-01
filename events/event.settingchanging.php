<?php

namespace Settings\Events;

use SplitPHP\Event;

/**
 * Class SettingChanging
 *
 * Dispatched by the Settings service immediately before a setting is persisted
 * (insert or update), with the persist itself as the dispatcher's main action.
 *
 * This is a plain NOTIFICATION — the generic settings module enforces nothing.
 * It simply announces the proposed change and lets the application decide what
 * to do with it. Because the EventDispatcher runs listeners BEFORE the main
 * action and skips that action when a listener throws or stops propagation, an
 * application listener may:
 *   - reject the change by throwing (e.g. a BadRequest) → persist is skipped;
 *   - adjust the change by mutating the proposed-change object returned by
 *     info() (e.g. clamping a value) → the persisted value reflects the edit;
 *   - stop propagation to silently skip the persist.
 *
 * The proposed-change object carries:
 *   ->context   string  the setting context (ds_context)
 *   ->fieldname string  the setting field (ds_fieldname)
 *   ->value     mixed   the value about to be written (tx_fieldvalue) — mutable
 *   ->format    string  the value format (ds_format): text|json|file
 *   ->current   ?object the existing STT_SETTINGS record, or null when creating
 *   ->user      ?object the logged user performing the change, or null
 *
 * @package Settings\Events
 */
class SettingChanging extends Event
{
  /**
   * The name of the event.
   */
  public const EVENT_NAME = 'settings.setting.changing';

  /**
   * @var object $change The proposed change (see class doc for shape). Mutable
   *                     by listeners; the persist reads its final state.
   */
  private object $change;

  /**
   * SettingChanging constructor.
   *
   * @param object $change The proposed change the service is about to persist.
   */
  public function __construct(object $change)
  {
    $this->change = $change;
  }

  /**
   * Returns the (mutable) proposed-change object. Listeners read and may edit
   * its properties in place (same instance the service persists). Implements the
   * Event base's abstract info().
   *
   * @return object The proposed change { context, fieldname, value, format, current, user }.
   */
  public function info(): object
  {
    return $this->change;
  }

  /**
   * Returns a string representation of the SettingChanging event.
   *
   * @return string A string representation of the event.
   */
  public function __toString(): string
  {
    return 'Event: ' . self::EVENT_NAME . ' (' . json_encode($this->change) . ')';
  }
}
