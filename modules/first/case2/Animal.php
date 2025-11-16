<?php

namespace app\modules\first\case2;

class Animal
{
    protected $name;

    // Конструктор базового класса
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function speak()
    {
        return $this->name . " издает звук.<br>";
    }

    public function eat($food)
    {
        echo $this->name . " ест " . $food . ".<br>";
    }
}