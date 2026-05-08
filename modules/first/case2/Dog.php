<?php

namespace app\modules\first\case2;

class Dog extends Animal
{
    private $breed;

    // Конструктор производного класса
    public function __construct($name, $food, $breed)
    {
        parent::__construct($name, $food); // Вызов конструктора базового класса
        $this->breed = $breed;
    }

    // Переопределение метода speak
    public function speak()
    {
        return 'гав';
    }

    // Метод, специфичный для производного класса
    public function getBreed()
    {
        return $this->breed;
    }
}