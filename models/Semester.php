<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models;

/**
 * This is the model class for table "semester".
 */
class Semester extends base\Semester
{
    public function beforeValidate()
    {
        $this->start_at = $this->start_at ? date('Y-m-d', strtotime($this->start_at)) : null;
        $this->end_at = $this->end_at ? date('Y-m-d', strtotime($this->end_at)) : null;
        return parent::beforeValidate();
    }
}
