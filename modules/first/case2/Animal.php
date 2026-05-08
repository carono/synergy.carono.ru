<?php

namespace app\modules\first\case2;

abstract class Animal
{
    protected $name;
    protected $food;

    // Конструктор базового класса
    public function __construct($name, $food)
    {
        $this->name = $name;
        $this->food = $food;
    }

    abstract public function speak();

    public function getName()
    {
        return $this->name;
    }

    public function getFood()
    {
        return $this->food;
    }
}