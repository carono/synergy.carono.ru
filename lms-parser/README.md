# LMS Synergy parser

Нативный парсер `https://lms.synergy.ru/` на Guzzle: логинится, проходит по дисциплинам текущего семестра, скачивает доступные видеоуроки и по SCORM-протоколу отправляет «просмотрено», чтобы разблокировать следующие уроки.

## Установка

```bash
cd lms-parser
composer install
cp .env.example .env
# отредактировать .env: LMS_LOGIN / LMS_PASSWORD
```

## Запуск

```bash
./bin/parse                       # текущий семестр, скачать всё доступное и отметить просмотренным
./bin/parse --semester=4          # конкретный семестр
./bin/parse --discover-only       # только показать список семестров и дисциплин
./bin/parse --no-watch            # только скачивание, без эмуляции просмотра
./bin/parse --minutes=45          # сколько минут «просмотра» отправлять в SCORM
```

## Структура файлов

```
downloads/
  semester_04/
    programmirovanie_na_yazyke_python/
      z5.1_zanyatie_5.1.mp4
      z5.2_zanyatie_5.2.mp4
      ...
    bazy_dannyh/
      ...
state.json    # прогресс по урокам (idempotent: повторный запуск пропустит уже сделанные)
cookies.json  # сохранённая сессия Guzzle
```

## Логика

1. Логинимся через POST `/user/login` (форма `popupUsername` / `popupPassword`).
2. Скачиваем `/student/up` и через DomCrawler находим `<tbody class="semester sN expanded">` — это текущий семестр.
3. Для каждой дисциплины открываем `/lntools/versiongroupassign/contents/student/{id}` (LMS редиректит на `/student/updiscipline/...`).
4. На странице дисциплины собираем уроки из `li[data-index]`. Заблокированные уроки (`a.resourse_blocked`) пропускаем; если блокировка из-за теста — пропускаем дисциплину целиком.
5. Для каждого доступного урока:
   - открываем `/lntools/mcresource/view/{resourceId}/?...` (редирект на `/learning/view/{packageId}`),
   - читаем `php = {...}` инлайновый объект → `learningPackageId`, `firstItemId` из `tocItem`,
   - дёргаем `GET /learning/ajax/navigation_request/choice/0/{itemId}/{packageId}` → получаем `itemLink` (HTML с `<video>`),
   - вытаскиваем прямой MP4 URL (`<source src="...">`) и качаем (с поддержкой докачки через Range),
   - отправляем SCORM-cmi с `completionStatus=completed`, `totalTime=PT0H30M0S` через `save_data` + `navigation_request/exitall` + `close_lms`.

## Замечания

- Время «просмотра» эмулируется на стороне сервера через те же AJAX, что делает плеер LMS. Если LMS поднимет валидацию (например, серверный таймер или watermarking) — нужно будет докрутить heartbeat-вызовы.
- `cookies.json` сохраняется между запусками — повторный запуск не делает лишний логин.
- `state.json` — idempotent: повторный прогон пропустит уже скачанные уроки.
