{{ html()->button( (isset($icon) ? html()->i()->class($icon) . ' ' : '') . ($text ?? 'Submit'), ($type ?? 'submit'))->class($class ?? '') }}