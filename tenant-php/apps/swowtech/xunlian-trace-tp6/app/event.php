<?php
// 事件定义文件
return [
    'bind' => [],

    'listen' => [
        'AppInit' => ['app\\behavior\\CORS'],
        // 创建订单
        'CreateOrder' => [
            'events\listens\CheckStyle',
            'events\listens\CheckStock',
            'events\listens\CheckCoupon',
        ],
        // 订单完成
        'EndOrder' => [
            'events\listens\editCoupon',
            'events\listens\editSales',
            'events\listens\addPoints',
        ],
    ],

    'subscribe' => [],
];
