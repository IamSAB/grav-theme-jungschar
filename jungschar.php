<?php

namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Common\Utils;
use DateTime;
use Sabre\VObject;

class Jungschar extends Theme
{
  public static function getSubscribedEvents()
  {
    return [
      'onTwigInitialized' => ['onTwigInitialized', 0],
    ];
  }

  public function onTwigInitialized()
  {
    $this->grav['twig']->twig()->addFilter(new \Twig\TwigFilter('startEnd', [$this, 'startEnd']));

    $this->grav['twig']->twig()->addFilter(new \Twig\TwigFilter('sortByExifCreated', [$this, 'sortByExifCreated']));
    $this->grav['twig']->twig()->addFilter(new \Twig\TwigFilter('dataSize', [$this, 'dataSize']));
    $this->grav['twig']->twig()->addFunction(new \Twig\TwigFunction('getEventsInRange', [$this, 'getEventsInRange']));
  }

  public function getEventsAsICal()
  {
    $arr = [];

    $events = $this->grav['page']->collection([
      'items' => [
        '@page.descendants' => '/chronik',
      ],
      'filter' => [
        'published' => true,
        'type' => 'event'
      ],
      'dateRange' => [
        'start' => '-6 month',
        'end' => '+1 year',
        'field' => 'header.dtstart'
      ]
    ]);

    // TODO: use DESCRIPTION or COMMENT? include locstart and locend

    foreach ($events as $event) {
      $header = (array) $event->header();
      $categories = Utils::arrayFlatten($event->taxonomy());

      if (isset($header['events'])) {
        foreach ($header['events'] as $item) {
          $arr[] = [
            'VEVENT' => [
              'SUMMARY' => $item['title'],
              'DTSTART' => new DateTime($item['dtstart']),
              'DTEND' => new DateTime($item['dtend']),
              'LOCATION' => $item['location'] ?? $header['location'] ?? '',
              'DESCRIPTION' => $item['description'] ?? $header['description'] ?? '',
              'CATEGORIES' => $categories,
            ]
          ];
        }
      } else {
        $arr[] = [
          'VEVENT' => [
            'SUMMARY' => $event->title(),
            'DTSTART' => new DateTime($header['dtstart']),
            'DTEND' => new DateTime($header['dtend']),
            'LOCATION' => $header['location'] ?? '',
            'DESCRIPTION' => $header['description'] ?? '',
            'CATEGORIES' => $categories,
          ]
        ];
      }
    }

    $vcalendar = new VObject\Component\VCalendar($arr);

    return $vcalendar->serialize();
  }

  public function getEventsInRange($a, $b)
  {
    // TODO: rather weird results, think due to caching of whole site
    
    $rangeStart = new DateTime($a);
    $rangeEnd = new DateTime($b);

    $events = $this->grav['page']->collection([
      'items' => [
        '@page.descendants' => '/chronik',
      ],
      'filter' => [
        'published' => true,
        'type' => 'event'
      ]
    ]);

    $arr = [];
    // @see https://fullcalendar.io/docs/event-object
    foreach ($events as $event) {
      $header = (array) $event->header();
      $start = new DateTime($header['dtstart']);
      $end = new DateTime($header['dtend']);

      if (($rangeStart < $start && $start < $rangeEnd) || ($rangeStart < $end && $end < $rangeEnd) || ($start < $rangeStart && $rangeEnd < $end)) {

        if (isset($header['events'])) {
          foreach ($header['events'] as $subevent) {
            $arr[] = [
              'title' => $subevent['title'],
              'start' => (new DateTime($subevent['dtstart']))->format(DateTime::ISO8601),
              'end' => (new DateTime($subevent['dtend']))->format(DateTime::ISO8601),
              'url' => $event->url()
            ];
          }
        } else {
          $arr[] = [
            'title' => $event->title(),
            'start' => (new DateTime($header['dtstart']))->format(DateTime::ISO8601),
            'end' => (new DateTime($header['dtend']))->format(DateTime::ISO8601),
            'url' => $event->url()
          ];
        }
      }
    }
    return $arr;
  }

  public function startEnd($a, $b, $showTime = false)
  {
    $d1 = new DateTime($a);
    $d2 = new DateTime($b);
    $now = new DateTime();

    $singleDay = $d1->format('d.m.y') == $d2->format('d.m.y');
    $singleMonth = $d1->format('n') == $d2->format('n');
    $singleYear = $d1->format('y') == $d2->format('y');
    $thisYear = $d1->format('y') == $now->format('y');

    $divider = " bis ";
    $f1 = $f2 = "";

    if ($showTime) {
      if ($singleDay) {
        $f1 = "j. M";
        if (!$thisYear) $f1 .= " y";
        $f1 .= " G:i";
        $f2 = "G:i";
      } elseif (!$singleYear || !$thisYear) {
        $f1 = $f2 = "j. M y G:i";
      } else {
        $f1 = $f2 = "j. M G:i";
      }
    } else {
      if ($singleDay) {
        $divider = "";
        $f1 = "j. M";
        if (!$thisYear) $f1 .= " y";
      } elseif ($singleMonth) {
        $f1 = "j.";
        $f2 = "j. M";
        if (!$thisYear) $f2 .= " y";
      } elseif ($singleYear) {
        $f1 = $f2 = "j. M";
        if (!$thisYear) $f2 .= " y";
      } else {
        $f1 = $f2 = "j. M y";
      }
    }

    return $d1->format($f1) . $divider . $d2->format($f2);
  }

  public function dataSize($img)
  {
    if (!extension_loaded('exif')) {
      throw new \RuntimeException('You need to EXIF PHP Extension to use this function');
    }

    $exif = @exif_read_data($img->path());
    if ($exif && array_key_exists('Orientation', $exif)) {
      $flag = $exif['Orientation'];
      if (in_array($flag, [5, 6, 7, 8])) {
        return $img->height . 'x' . $img->width;
      }
    }
    return $img->width . 'x' . $img->height;
  }

  public function sortByExifCreated($images)
  {
    if (!extension_loaded('exif')) {
      throw new \RuntimeException('You need to EXIF PHP Extension to use this function');
    }

    $known = [];
    $unkown = [];

    foreach ($images as $name => $img) {
      $exif = @exif_read_data($img->path());
      if ($exif === false || !(isset($exif['DateTimeOriginal']) || isset($exif['DateTime']))) {
        $unkown[$name] = $name;
      } else {
        $date = new Datetime($exif['DateTimeOriginal'] ?? $exif['DateTime']);
        $known[$name] = $date->getTimestamp();
        $img->write('Arial', $date->format('d.m.y'));
      }
    }

    asort($known);
    asort($unkown);

    $sorted_images = [];

    foreach ($known as $name => $v) {
      $sorted_images[$name] = $images[$name];
    }

    foreach ($unkown as $name => $v) {
      $sorted_images[$name] = $images[$name];
    }

    return $sorted_images;
  }
}
