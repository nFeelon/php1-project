<?php
function formatSubscribersCount($count)
{
    $count = (int) $count;

    if ($count >= 1000000) {
        return round($count / 1000000, 1) . ' млн подписчиков';
    } elseif ($count >= 1000) {
        return round($count / 1000, 1) . ' тыс. подписчиков';
    } elseif ($count > 0) {
        return $count . ' ' . getNumEnding($count, ['подписчик', 'подписчика', 'подписчиков']);
    } else {
        return '0 подписчиков';
    }
}

function formatViewsCount($count)
{
    $count = (int) $count;

    if ($count >= 1000000) {
        return round($count / 1000000, 1) . ' млн';
    } elseif ($count >= 1000) {
        return round($count / 1000, 1) . ' тыс.';
    } else {
        return $count;
    }
}

function formatDate($date)
{
    $timestamp = strtotime($date);
    return date('d.m.Y', $timestamp);
}

function formatDuration($seconds)
{
    $seconds = (int) $seconds;

    if ($seconds < 3600) {
        return sprintf('%02d:%02d', floor($seconds / 60), $seconds % 60);
    } else {
        return sprintf('%02d:%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60);
    }
}

function getNumEnding($number, $endings)
{
    $number = abs((int) $number);
    $mod10 = $number % 10;
    $mod100 = $number % 100;

    if ($mod100 >= 11 && $mod100 <= 19) {
        return $endings[2];
    }

    if ($mod10 === 1) {
        return $endings[0];
    }

    if ($mod10 >= 2 && $mod10 <= 4) {
        return $endings[1];
    }

    return $endings[2];
}
