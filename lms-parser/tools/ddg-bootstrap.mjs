/**
 * DDoS-Guard bootstrap для lms-parser.
 *
 * lms.synergy.ru закрыт JS-челленджем DDoS-Guard: обычный HTTP-клиент (Guzzle, curl)
 * получает 403 ещё до формы логина. Этот скрипт открывает сайт настоящим браузером,
 * даёт челленджу отработать, логинится и печатает в stdout JSON с cookies —
 * в том формате, который читает GuzzleHttp\Cookie\FileCookieJar.
 *
 * Запускается из bin/cookies, самостоятельно вызывать не нужно.
 *
 * Использование:
 *   LMS_LOGIN=... LMS_PASSWORD=... node tools/ddg-bootstrap.mjs [--playwright=DIR] [--headless]
 *
 * Креды принимаются только через окружение: аргументы командной строки видны в `ps`
 * любому пользователю машины.
 *
 * Важно: headless-браузер DDoS-Guard распознаёт и не пропускает, поэтому по умолчанию
 * окно запускается видимым (в WSL — через WSLg, нужен DISPLAY).
 */

import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const BASE = 'https://lms.synergy.ru';
const USER_AGENT =
  'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

const args = {};
for (const raw of process.argv.slice(2)) {
  const m = /^--([^=]+)(?:=(.*))?$/.exec(raw);
  if (m) args[m[1]] = m[2] === undefined ? true : m[2];
}

const login = process.env.LMS_LOGIN;
const password = process.env.LMS_PASSWORD;
if (!login || !password) {
  console.error('Нужны LMS_LOGIN и LMS_PASSWORD в окружении');
  process.exit(2);
}

/** Playwright ставится отдельно от парсера — ищем его по подсказке или в стандартных местах. */
function resolvePlaywright() {
  const candidates = [];
  if (args.playwright) candidates.push(args.playwright);
  if (process.env.LMS_PLAYWRIGHT_DIR) candidates.push(process.env.LMS_PLAYWRIGHT_DIR);
  candidates.push(process.cwd());
  if (process.env.HOME) candidates.push(path.join(process.env.HOME, 'playwright-tests'));

  for (const dir of candidates) {
    const entry = path.join(dir, 'node_modules', 'playwright', 'index.mjs');
    if (fs.existsSync(entry)) return entry;
  }
  try {
    return createRequire(import.meta.url).resolve('playwright');
  } catch {
    return null;
  }
}

const entry = resolvePlaywright();
if (entry === null) {
  console.error(
    'Не найден playwright. Установите его (npm i playwright && npx playwright install chromium)\n' +
    'и укажите каталог установки в LMS_PLAYWRIGHT_DIR или через --playwright=DIR.'
  );
  process.exit(3);
}

const { chromium } = await import(entry);

const browser = await chromium.launch({ headless: Boolean(args.headless) });
try {
  const context = await browser.newContext({ locale: 'ru-RU', userAgent: USER_AGENT });
  const page = await context.newPage();

  await page.goto(BASE + '/', { waitUntil: 'domcontentloaded', timeout: 90000 });

  // Челлендж перезагружает страницу сам; ждём, пока в title перестанет быть DDoS-Guard.
  let passed = false;
  for (let i = 0; i < 30; i++) {
    if (!/ddos.?guard/i.test(await page.title())) {
      passed = true;
      break;
    }
    await page.waitForTimeout(3000);
  }
  if (!passed) {
    console.error('DDoS-Guard не пропустил за 90 с. Попробуйте запустить без --headless (нужен DISPLAY).');
    process.exit(4);
  }

  // Логинимся тем же XHR, что и форма на сайте, — cookies осядут в контексте браузера.
  const raw = await page.evaluate(async ([user, pass]) => {
    const response = await fetch('/user/login', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ popupUsername: user, popupPassword: pass, currentUrl: '' }),
    });
    return await response.text();
  }, [login, password]);

  let json;
  try {
    json = JSON.parse(raw);
  } catch {
    console.error('Ответ /user/login — не JSON: ' + raw.slice(0, 200));
    process.exit(5);
  }
  if (json.alertMessage || !json.redirect) {
    console.error('Не удалось войти: ' + (json.alertMessage || raw.slice(0, 200)));
    process.exit(5);
  }

  // Подтверждаем сессию: /student/up должен отдать страницу обучения, а не челлендж.
  await page.goto(BASE + '/student/up', { waitUntil: 'domcontentloaded', timeout: 90000 });
  const html = await page.content();
  if (!html.includes('/user/logout')) {
    console.error('Логин прошёл, но /student/up не выглядит авторизованной страницей.');
    process.exit(6);
  }

  const cookies = (await context.cookies())
    .filter((c) => c.domain.endsWith('synergy.ru'))
    .map((c) => ({
      Name: c.name,
      Value: c.value,
      Domain: c.domain,
      Path: c.path,
      'Max-Age': null,
      Expires: c.expires && c.expires > 0 ? Math.floor(c.expires) : null,
      Secure: Boolean(c.secure),
      Discard: false,
      HttpOnly: Boolean(c.httpOnly),
    }));

  process.stdout.write(JSON.stringify(cookies));
} finally {
  await browser.close();
}
