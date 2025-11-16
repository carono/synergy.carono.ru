<?php

namespace app\modules\first\case2;

class Dog extends Animal
{
    private $breed;

    // Конструктор производного класса
    public function __construct($name, $breed)
    {
        parent::__construct($name); // Вызов конструктора базового класса
        $this->breed = $breed;
    }

    // Переопределение метода speak
    public function speak()
    {
        return $this->name . " гавкает.<br>";
    }

    // Метод, специфичный для производного класса
    public function showBreed()
    {
        return $this->name . " порода " . $this->breed . ".<br>";
    }
}