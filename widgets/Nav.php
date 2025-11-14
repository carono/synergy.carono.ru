<?php

namespace app\widgets;

use carono\yii2rbac\RoleManager;
use yii\bootstrap5\Html;

class Nav extends \yii\bootstrap5\Nav
{
    public function renderItems(): string
    {
        $items = [];
        foreach ($this->items as $item) {
            if ((isset($item['visible']) && !$item['visible']) || isset($item['url']) && !RoleManager::checkAccessByUrl($item['url'])) {
                continue;
            }
            $items[] = $this->renderItem($item);
        }

        return Html::tag('ul', implode("\n", $items), $this->options);
    }
}