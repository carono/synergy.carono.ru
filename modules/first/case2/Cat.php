<?php

namespace app\modules\first\case2;

class Cat extends Animal
{
    // Переопределение метода speak
    public function speak()
    {
        return 'мяу';
    }
}