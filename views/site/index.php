<?php

/** @var yii\web\View $this */

$this->title = 'Практические задачи';
?>

<h1 class="text-center"><?= $this->title ?></h1>

<div class="container mt-4">
    <div class="practical-lessons">
        <!-- Курс 1 -->
        <div class="course-block">
            <h3 class="course-title">Курс 1</h3>

            <!-- Семестр 1 (активный) -->
            <div class="semester-block">
                <h4 class="semester-title">Семестр 1</h4>
                <div class="lessons-list">
                    <a href="#" class="lesson-item disabled">
                        <span class="lesson-name">Практических занятий нет</span>
                    </a>
                </div>
            </div>

            <!-- Семестр 2 (активный) -->
            <div class="semester-block">
                <h4 class="semester-title">Семестр 2</h4>
                <div class="lessons-list">
                    <a href="/course/first/semester2/case-1" class="lesson-item">
                        <span class="lesson-number">1.</span>
                        <span class="lesson-name">Одномерный массив А размерности N</span>
                    </a>
                    <a href="/course/first/semester2/case-2" class="lesson-item">
                        <span class="lesson-number">2.</span>
                        <span class="lesson-name">Демонстрация работы методов базового и производного классов</span>
                    </a>
                    <a href="/course/first/semester2/case-3" class="lesson-item">
                        <span class="lesson-number">3.</span>
                        <span class="lesson-name">База данных «Туризм»</span>
                    </a>
                    <a href="/course/first/semester2/case-4" class="lesson-item">
                        <span class="lesson-number">4.</span>
                        <span class="lesson-name">Анализ имеющихся на рынке ПО информационных систем, приложение на любую тему</span>
                    </a>
                    <a href="/course/first/semester2/case-5" class="lesson-item">
                        <span class="lesson-number">5.</span>
                        <span class="lesson-name">Аналитический обзор проделанной работы</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Курс 2 (неактивный) -->
        <div class="course-block disabled">
            <h3 class="course-title">Курс 2</h3>

            <!-- Семестр 3 (неактивный) -->
            <div class="semester-block">
                <h4 class="semester-title">Семестр 3</h4>
                <div class="lessons-list">
                    <span class="lesson-item disabled">
                        <span class="lesson-name">Занятия не наступили</span>
                    </span>
                </div>
            </div>

            <!-- Семестр 4 (неактивный) -->
            <div class="semester-block">
                <h4 class="semester-title">Семестр 4</h4>
                <div class="lessons-list">
                    <span class="lesson-item disabled">
                        <span class="lesson-name">Занятия не наступили</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="course-block disabled">
            <h3 class="course-title">Курс 3</h3>

            <div class="semester-block">
                <h4 class="semester-title">Семестр 5</h4>
                <div class="lessons-list">
                    <span class="lesson-item disabled">
                        <span class="lesson-name">Занятия не наступили</span>
                    </span>
                </div>
            </div>
            <div class="semester-block">
                <h4 class="semester-title">Семестр 6</h4>
                <div class="lessons-list">
                    <span class="lesson-item disabled">
                        <span class="lesson-name">Занятия не наступили</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="course-block disabled">
            <h3 class="course-title">Курс 4</h3>

            <div class="semester-block">
                <h4 class="semester-title">Семестр 7</h4>
                <div class="lessons-list">
                    <span class="lesson-item disabled">
                        <span class="lesson-name">Занятия не наступили</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
