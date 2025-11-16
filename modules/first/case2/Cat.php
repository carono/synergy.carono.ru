<?php

namespace app\modules\first\case2;

class Cat extends Animal
{
    // Конструктор производного класса
    public function __construct($name, $food)
    {
        parent::__construct($name, $food); // Вызов конструктора базового класса
    }

    // Переопределение метода speak
    public function speak()
    {
        return 'мяу';
    }
}