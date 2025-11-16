<?php

namespace app\widgets;

use Closure;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Menu;

class MainMenu extends Menu
{
    public $linkOptions = [
        'class' => 'lesson-item'
    ];
    public $itemOptions = [
        'tag' => 'div',
    ];
    public $options = [
        'class' => 'practical-lessons',
        'tag' => 'div'
    ];

    public $linkTemplate = '<a href="{url}" {link-options}><span class="lesson-number">{index}.</span><span class="lesson-name">{label}</span></a>';
    public $submenuTemplate = '{items}';

    public $courseTemplate = '<h3 class="course-title">{label}</h3>';
    public $semesterTemplate = '<h4 class="semester-title">{label}</h4>';


    protected function renderItems($items)
    {
        $n = count($items);
        $lines = [];
        foreach ($items as $i => $item) {
            $item['index'] = $i + 1;
            $item['template'] = $this->linkTemplate;
            $item['options'] = ['class' => "lessons-list"];
            $item['linkOptions'] = ['class' => 'lesson-item' . (isset($item['disabled']) ? ' disabled' : '')];

            if (str_starts_with($item['label'], 'Курс')) {
                $item['submenuTemplate'] = '{items}';
                $item['template'] = $this->courseTemplate;
                $item['options'] = ['class' => 'course-block' . (isset($item['disabled']) ? ' disabled' : '')];
            }

            if (str_starts_with($item['label'], 'Семестр')) {
                $item['submenuTemplate'] = '<div class="lessons-list">{items}</div>';
                $item['template'] = $this->semesterTemplate;
                $item['options'] = ['class' => 'semester-block'];
            }


            $options = array_merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));

            $tag = ArrayHelper::remove($options, 'tag', 'li');
            $class = [];
            if ($item['active']) {
                $class[] = $this->activeCssClass;
            }
            if ($i === 0 && $this->firstItemCssClass !== null) {
                $class[] = $this->firstItemCssClass;
            }
            if ($i === $n - 1 && $this->lastItemCssClass !== null) {
                $class[] = $this->lastItemCssClass;
            }
            Html::addCssClass($options, $class);

            $menu = $this->renderItem($item);
            if (!empty($item['items'])) {
                $submenuTemplate = ArrayHelper::getValue($item, 'submenuTemplate', $this->submenuTemplate);
                $menu .= strtr($submenuTemplate, [
                    '{items}' => $this->renderItems($item['items']),
                ]);
            }
            $lines[] = Html::tag($tag, $menu, $options);
        }

        return implode("\n", $lines);
    }

    protected function renderItem($item)
    {
        $linkOptions = ArrayHelper::getValue($item, 'linkOptions', $this->linkOptions);


        $template = ArrayHelper::getValue($item, 'template', $this->linkTemplate);
        return strtr($template, [
            '{url}' => Html::encode(Url::to($item['url'] ?? '#')),
            '{index}' => $item['index'] ?? 1,
            '{label}' => $item['label'],
            '{options}' => Html::renderTagAttributes($item['options'] ?? []),
            '{link-options}' => Html::renderTagAttributes($linkOptions),
        ]);
    }
}