<?php

declare(strict_types=1);

namespace Carono\LmsParser;

/**
 * Эмуляция SCORM-сессии: «открыли занятие, поставили видео, досмотрели N минут, вышли».
 * Серверу отправляются те же AJAX-вызовы, которые делает учебный плеер из браузера.
 */
final class Scorm
{
    public function __construct(
        private readonly Client $client,
        private readonly Logger $logger,
    ) {
    }

    /**
     * Открыть занятие (item) внутри пакета. Возвращает initial cmi-данные и ссылку на iframe-контент.
     *
     * @return array{cmi: array<string, mixed>, itemLink: ?string, raw: array<string, mixed>}
     */
    public function chooseItem(string $itemId, string $learningPackageId, string $referer): array
    {
        $url = "/learning/ajax/navigation_request/choice/0/{$itemId}/{$learningPackageId}";
        $resp = $this->client->ajaxGet($url, $referer);
        return [
            'cmi' => $resp['cmi'] ?? [],
            'itemLink' => $resp['itemLink'] ?? null,
            'raw' => $resp,
        ];
    }

    /**
     * Сохранить cmi-данные.
     * В оригинальном api.js плеер вызывает /learning/ajax/save_data/commit/0 из-за опечатки
     * `learnignPackageId` — сервер опирается на сессию, а не на URL. Воспроизводим то же поведение.
     */
    public function saveData(string $origin, string $learningPackageId, array $cmi, string $referer): array
    {
        $url = "/learning/ajax/save_data/{$origin}/0";
        return $this->client->ajaxPost($url, [
            'data' => json_encode($cmi, JSON_UNESCAPED_UNICODE),
        ], $referer);
    }

    /**
     * suspendall / exitall / continue — управляющие действия плеера.
     */
    public function controlsRequest(string $type, string $learningPackageId, array $cmi, string $referer): array
    {
        // courseAction = 0 (как при стандартном завершении без 'finish' / 'notfinish').
        $url = "/learning/ajax/navigation_request/{$type}/0/{$learningPackageId}";
        return $this->client->ajaxPost($url, [
            'data' => json_encode($cmi, JSON_UNESCAPED_UNICODE),
        ], $referer);
    }

    /**
     * Закрытие LMS-страницы (как нажатие «Подтвердить изучение материала»).
     */
    public function closeLms(string $learningPackageId, string $referer): array
    {
        $url = "/learning/ajax/close_lms/{$learningPackageId}";
        return $this->client->ajaxPost($url, [], $referer);
    }

    /**
     * Эмулируем просмотр видео: формируем cmi в формате, который собирает initializeDataModel в data_model.js
     * (SCORM 2004 snake_case + adl). Возвращаем структуру, которую потом отправим в save_data и controlsRequest.
     */
    public function buildCompletedCmi(array $initialCmi, int $minutes, ?string $learnerId = null, ?string $learnerName = null): array
    {
        $hours = intdiv($minutes, 60);
        $rem = $minutes - $hours * 60;
        $sessionTime = sprintf('PT%dH%dM0S', $hours, $rem);

        return [
            '_version' => '1.0',
            '_children' => 'student_id,student_name,lesson_location,credit,lesson_status,entry,score,total_time,exit,session_time',
            'comments_from_learner' => ['_children' => 'comment,location,timestamp', '_count' => 0, 'values' => []],
            'comments_from_lms' => ['_children' => 'comment,location,timestamp', '_count' => 0, 'values' => []],
            'objectives' => ['_children' => 'id,score,success_status,completion_status,progress_measure,description', '_count' => 0, 'values' => []],
            'interactions' => ['_children' => 'id,type,objectives,timestamp,correct_responses,weighting,learner_response,result,latency,description', '_count' => 0, 'values' => []],

            'completion_status' => 'completed',
            'completion_threshold' => $initialCmi['completionThreshold'] ?? null,
            'credit' => $initialCmi['credit'] ?? 'credit',
            'entry' => 'ab-initio',
            'exit' => 'normal',
            'launch_data' => $initialCmi['launchData'] ?? null,
            'learner_id' => $learnerId ?? ($initialCmi['learnerId'] ?? null),
            'learner_name' => $learnerName ?? ($initialCmi['learnerName'] ?? null),
            'learner_preference' => [
                '_children' => 'audio_level,language,delivery_speed,audio_captioning',
                'audio_level' => 1,
                'language' => null,
                'delivery_speed' => 1,
                'audio_captioning' => 0,
            ],
            'location' => $initialCmi['lessonLocation'] ?? null,
            'max_time_allowed' => $initialCmi['maxTimeAllowed'] ?? null,
            'mode' => 'normal',
            'progress_measure' => 1,
            'scaled_passing_score' => $initialCmi['scaled_passing_score'] ?? null,
            'score' => [
                '_children' => 'scaled,min,max,raw',
                'scaled' => 1,
                'min' => null,
                'max' => null,
                'raw' => null,
            ],
            'session_time' => $sessionTime,
            'success_status' => 'passed',
            'suspend_data' => $initialCmi['suspendData'] ?? null,
            'time_limit_action' => $initialCmi['timeLimitAction'] ?? 'continue,no message',
            'total_time' => $sessionTime,

            'completion_reported' => 1,
            'success_reported' => 1,
            'progress_reported' => 1,
            'score_scaled_reported' => 1,
        ];
    }

    /**
     * Полный сценарий «прошёл занятие» с реальным ожиданием:
     *  1) navigation_request/choice — открыть item
     *  2) heartbeat-цикл: каждые 30с — GET /student/up + save_data/commit (сервер считает реальное время)
     *  3) navigation_request/exitall — выйти, помечая курс продвинутым
     *  4) close_lms — закрыть пакет
     *
     * @return array{ok: bool, itemLink: ?string, response: array<string, mixed>}
     */
    public function completeLesson(
        string $itemId,
        string $learningPackageId,
        string $referer,
        int $watchedMinutes,
    ): array {
        $this->logger->debug("SCORM: choice item=$itemId package=$learningPackageId");
        $opened = $this->chooseItem($itemId, $learningPackageId, $referer);

        $rawCmi = $opened['cmi'] ?? [];
        $progressBefore = (string)($opened['raw']['progress'] ?? '?');
        $statusBefore = (string)($rawCmi['completionStatus'] ?? '?');
        $totalBefore = (string)($rawCmi['totalTime'] ?? '?');
        $this->logger->debug("SCORM: до — progress=$progressBefore status=$statusBefore total=$totalBefore");

        $cmi = $this->buildCompletedCmi(
            $rawCmi,
            $watchedMinutes,
            $rawCmi['learnerId'] ?? null,
            $rawCmi['learnerName'] ?? null,
        );

        // Сервер считает реальный delta-time между choice и exitall — нужно ждать настоящие N минут.
        $durationSec = $watchedMinutes * 60;
        $start = time();
        $this->logger->debug("SCORM: heartbeat {$watchedMinutes} мин, пинг каждые 30с");

        while (time() - $start < $durationSec) {
            sleep(30);
            $elapsed = time() - $start;
            $this->logger->debug("SCORM: keep-alive {$elapsed}s / {$durationSec}s");

            try {
                $this->client->get('/student/up', ['headers' => ['Referer' => $referer]]);
            } catch (\Throwable) {}

            $h = intdiv($elapsed, 3600);
            $rest = $elapsed - $h * 3600;
            $m = intdiv($rest, 60);
            $s = $rest - $m * 60;
            $cmiCommit = $cmi;
            $cmiCommit['session_time'] = sprintf('PT%dH%dM%dS', $h, $m, $s);
            $cmiCommit['total_time'] = $cmiCommit['session_time'];

            try {
                $this->saveData('commit', $learningPackageId, $cmiCommit, $referer);
            } catch (\Throwable $e) {
                $this->logger->debug("SCORM: save_data warn: ".$e->getMessage());
            }
        }

        $this->logger->debug("SCORM: navigation_request/exitall");
        $exited = $this->controlsRequest('exitall', $learningPackageId, $cmi, $referer);
        $this->logger->debug("SCORM: exitall → ".substr(json_encode($exited, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 800));

        $this->logger->debug("SCORM: close_lms");
        $this->closeLms($learningPackageId, $referer);

        return [
            'ok' => true,
            'itemLink' => $opened['itemLink'] ?? null,
            'response' => ['exited' => $exited],
        ];
    }
}
