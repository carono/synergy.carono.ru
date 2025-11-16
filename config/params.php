<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'menu' => [
        [
            'label' => 'Курс 1',
            'items' => [
                [
                    'label' => 'Семестр 1',
                    'items' => [
                        ['label' => 'Практических занятий нет', 'disabled' => true]
                    ]
                ],
                [
                    'label' => 'Семестр 2',
                    'items' => [
                        ['label' => 'Одномерный массив А размерности N', 'url' => '/course/first/semester2/case-1'],
                        ['label' => 'Демонстрация работы методов базового и производного классов', 'url' => '/course/first/semester2/case-2'],
                        ['label' => 'База данных «Туризм»', 'url' => '/course/first/semester2/case-3'],
                        ['label' => 'Анализ имеющихся на рынке ПО информационных систем, приложение на любую тему', 'url' => '/course/first/semester2/case-4'],
                        ['label' => 'Аналитический обзор проделанной работы', 'url' => '/course/first/semester2/case-5'],
                    ]
                ]
            ]
        ],
        [
            'label' => 'Курс 2',
            'disabled' => true,
            'items' => [
                [
                    'label' => 'Семестр 3',
                    'items' => [
                        ['label' => 'Занятия не наступили']
                    ]
                ],
                [
                    'label' => 'Семестр 4',
                    'items' => [
                        ['label' => 'Занятия не наступили']
                    ]
                ],
            ]
        ],
        [
            'label' => 'Курс 3',
            'disabled' => true,
            'items' => [
                [
                    'label' => 'Семестр 5',
                    'items' => [
                        ['label' => 'Занятия не наступили']
                    ]
                ],
                [
                    'label' => 'Семестр 6',
                    'items' => [
                        ['label' => 'Занятия не наступили']
                    ]
                ],
            ]
        ],
        [
            'label' => 'Курс 4',
            'disabled' => true,
            'items' => [
                [
                    'label' => 'Семестр 7',
                    'items' => [
                        ['label' => 'Занятия не наступили']
                    ]
                ]
            ]
        ]
    ]
];
