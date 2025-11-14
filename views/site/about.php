<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'О студенте';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <!-- Основной блок персональной страницы (только main) -->
    <main class="profile-main" role="main" aria-label="Профиль разработчика"
          style="max-width:900px;margin:32px auto;padding:28px;border-radius:12px;background:#ffffff;box-shadow:0 6px 18px rgba(0,0,0,0.06);font-family:Inter, Roboto, Arial, sans-serif;color:#0f172a;">
        <header style="display:flex;align-items:center;gap:18px;margin-bottom:18px;">
            <div>
                <img style="border-radius: 10px" src="/img/foto_sq.jpg" width="100px">
            </div>
            <div>
                <h1 style="margin:0;font-size:22px;line-height:1.15;">Касьянов Александр</h1>
                <p style="margin:6px 0 0;color:#475569;font-size:14px;">группа: <strong>ОБПз-42409МОиибд</strong></p>
            </div>
        </header>

        <section aria-labelledby="about-heading" style="margin-bottom:18px;">
            <h2 id="about-heading" style="font-size:16px;margin:0 0 8px;color:#0f172a;">Обо мне</h2>
            <p style="margin:0;color:#334155;line-height:1.6;font-size:15px;">
                Я — опытный PHP-программист с длительным профессиональным опытом, мне 38 лет, и начавший карьеру в 2008 году как системный администратор. В настоящее время основная специализация —
                backend-разработка на PHP с фокусом на фреймворке <strong>Yii2</strong>.
                Веду публичный репозиторий на GitHub и делал вклады в ядро Yii2.
            </p>
        </section>

        <section aria-labelledby="skills-heading" style="display:grid;grid-template-columns:1fr 280px;gap:18px;margin-bottom:18px;align-items:start;">
            <div>
                <h3 id="skills-heading" style="font-size:15px;margin:0 0 8px;color:#0f172a;">Навыки и технологии</h3>
                <ul style="margin:0;padding:0 0 0 18px;color:#334155;line-height:1.7;font-size:15px;">
                    <li>PHP (основной язык)</li>
                    <li>Yii2 (основной фреймворк, контрибуции в ядро)</li>
                    <li>Работа с MySQL / PostgreSQL, оптимизация запросов</li>
                    <li>DevOps-навыки (опыт системного администрирования)</li>
                    <li>Git, CI/CD, тестирование (unit/integration)</li>
                </ul>

                <h4 style="margin:16px 0 6px;font-size:14px;color:#0f172a;">Ключевые роли</h4>
                <ol style="margin:0;padding-left:18px;color:#334155;font-size:15px;line-height:1.7;">
                    <li>Системный администратор — начало карьеры, инфраструктура и обслуживание</li>
                    <li>Backend-разработчик на PHP — разработка сервисов и API</li>
                    <li>Контрибьютор — участие в open-source (Yii2)</li>
                </ol>
            </div>

            <aside style="padding:14px;border-radius:10px;background:#fbfdff;border:1px solid #eef2ff;">
                <p style="margin:0 0 8px;font-size:14px;color:#0f172a;font-weight:600;">Ссылки</p>
                <p style="margin:6px 0 6px;font-size:14px;"><a href="https://github.com/carono" target="_blank" rel="noopener noreferrer">Гипхаб: github.com/carono</a></p>
                <p style="margin:6px 0 6px;font-size:14px;"><a href="https://carono.ru" target="_blank" rel="noopener noreferrer">Контакты: carono.ru</a></p>
            </aside>
        </section>

        <section aria-labelledby="work-heading" style="margin-bottom:18px;">
            <h3 id="work-heading" style="font-size:15px;margin:0 0 8px;color:#0f172a;">Опыт и репозитории</h3>
            <p style="margin:0 0 10px;color:#334155;font-size:15px;line-height:1.6;">Работаю в IT с 2008 года: начиная с администрирования серверов и инфраструктуры, перешёл в разработку
                PHP-приложений. Ведущий стек — Yii2, PHP, SQL и сопутствующие инструменты CI/CD и тестирования.</p>
        </section>
    </main>

</div>
