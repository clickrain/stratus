<?php

namespace clickrain\stratus\events;

use craft\events\CancelableEvent;

class SyncEvent extends CancelableEvent
{
    public string $type;
}
