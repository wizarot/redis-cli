<?php
/**
 * 自定义颜色配置
 * (由于项目简单,这里就不引入yml之类的了,直接用php数组配置下)
 */

// fg 字体颜色, bg 背景颜色
// 可用颜色: default, black, red, green, yellow, blue, magenta, cyan , white
$style = [
    'comment' => [
        'fg' => 'default',
        'bg' => 'default',
    ],
    'success' => [
        'fg' => 'black',
        'bg' => 'green',
    ],
    'error'   => [
        'fg' => 'white',
        'bg' => 'red',
    ],
    'warning' => [
        'fg' => 'white',
        'bg' => 'red',
    ],
    'note'    => [
        'fg' => 'yellow',
        'bg' => 'default',
    ],
    'caution' => [
        'fg' => 'white',
        'bg' => 'red',
    ],
];

// 可以复制数组到customStyle.php文件中,自定义即可
@include __DIR__.'/customStyle.php';