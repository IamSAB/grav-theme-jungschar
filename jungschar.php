<?php

namespace Grav\Theme;

use Grav;
use Grav\Common\Theme;
use Composer\Autoload\ClassLoader;
use GuzzleHttp\Client;
use DateTime;
use Sabre\VObject;

class Jungschar extends Theme
{
  public function autoload(): ClassLoader
  {
    return require __DIR__ . '/vendor/autoload.php';
  }

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
    $this->grav['twig']->twig()->addFilter(new \Twig\TwigFilter('groupByYear', [$this, 'groupByYear']));
    $this->grav['twig']->twig()->addFunction(new \Twig\TwigFunction('getEventsInRange', [$this, 'getEventsInRange']));
    $this->grav['twig']->twig()->addFunction(new \Twig\TwigFunction('getEventsAsICal', [$this, 'getEventsAsICal']));
    $this->grav['twig']->twig()->addFunction(new \Twig\TwigFunction('staticMap', [$this, 'staticMap']));
    $this->grav['twig']->twig()->addFunction(new \Twig\TwigFunction('embedMap', [$this, 'embedMap']));
    $this->grav['twig']->twig()->addFunction(new \Twig\TwigFunction('getGoogleMapsApiKey', [$this, 'getGoogleMapsApiKey']));
    $this->grav['twig']->twig()->addFunction(new \Twig\TwigFunction('getEventLocations', [$this, 'getEventLocations']));
    $this->grav['twig']->twig()->addFunction(new \Twig\TwigFunction('getUpcomingEvents', [$this, 'getUpcomingEvents']));
    $this->grav['twig']->twig()->addFunction(new \Twig\TwigFunction('getUpcomingEvent', [$this, 'getUpcomingEvent']));
    $this->grav['twig']->twig()->addFunction(new \Twig\TwigFunction('getLocationAndVenues', [$this, 'getLocationAndVenues']));
    $this->grav['twig']->twig()->addFilter(new \Twig\TwigFilter('startEndTime', [$this, 'startEndTime']));
  }

  public function getUpcomingEvent($event)
  {
    $header = (array) $event->header();
    $now = strtotime('now');

    $title = $event->title();
    $taxonomy = $event->taxonomy();
    $image = array_values($event->media()->images())[0] ?? null;
    $url = $event->url();
    $parent = null;
    $dtstart = $header['dtstart'];
    $dtend = $header['dtend'];
    $location = $header['location'] ?? '';
    $description = $header['description'] ?? '';

    $subevents = $header['events'] ?? null;

    if ($subevents) {
      $total = count($subevents);

      usort($subevents, function ($a, $b) {
        return strtotime($a['dtstart']) > strtotime($b['dtstart']);
      });

      foreach ($subevents as $index => $subevent) {
        if (strtotime($subevent['dtstart']) > $now) {
          $parent = compact('index', 'title', 'total', 'dtstart', 'dtend', 'description');
          $title = $subevent['title'];
          $dtstart = $subevent['dtstart'];
          $dtend = $subevent['dtend'];
          $description = $subevent['description'] ?? '';
          break;
        }
      }
    }

    return compact('title', 'url', 'dtstart', 'dtend', 'parent', 'taxonomy', 'image', 'description', 'location');
  }

  public function getUpcomingEvents($items = '@root.descendants', $field = 'header.dtend')
  {
    $events = $this->grav['page']->collection([
      'items' => $items,
      'filter' => [
        'published' => true,
        'type' => 'event'
      ],
      'dateRange' => [
        'start' => 'now',
        'field' => $field
      ]
    ]);

    $upcoming = [];
    foreach ($events as $event) {
      $upcoming[] = $this->getUpcomingEvent($event);
    }

    usort($upcoming, function ($a, $b) {
      return strtotime($a['dtstart']) > strtotime($b['dtstart']);
    });

    return $upcoming;
  }

  private function geocode(string $address)
  {
    $key = $this->getGoogleMapsApiKey();
    $address = urlencode($address);

    $client = new Client();
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&key=$key";
    $request = $client->request('GET', $url);

    $response = $request->getBody()->getContents();
    $response = json_decode($response);

    $lat = $response->results[0]->geometry->location->lat;
    $lng = $response->results[0]->geometry->location->lng;

    return compact('lat', 'lng');
  }

  public function getLocationAndVenues($event)
  {
    $header = (array) $event->header();

    $markers = [];
    $locstart = $header['locstart'] ?? null;
    $locend = $header['locend'] ?? $locstart;
    $location = $header['location'] ?? null;

    $this->grav['log']->debug('markers', compact('locstart', 'locend', 'location'));

    if (!$locend) {
      $locend = $locstart;
    }

    if ($locstart != $location) {
      if ($locstart == $locend) {
        if ($locstart) {
          $markers[$locstart] = array_merge([
            'title' => 'Treffpunkt',
            'date' => $this->startEnd($header['dtstart'], $header['dtend'], true)
          ], $this->findLatLng($locstart));
        }
        if ($location) {
          $markers[$location] = array_merge([
            'title' => 'Ort',
          ], $this->findLatLng($location));
        }
      } else if ($location == $locend) {
        if ($location) {
          $markers[$location] = array_merge([
            'title' => 'Begrüssung',
            'date' => (new DateTime($header['dtstart']))->format('j. M y G:i')
          ], $this->findLatLng($location));
        }
        if ($locend) {
          $markers[$locend] = array_merge([
            'title' => 'Ort & Verabschiedung',
            'date' => (new DateTime($header['dtend']))->format('j. M y G:i')
          ], $this->findLatLng($locend));
        }
      } else {
        if ($locstart) {
          $markers[$locstart] = array_merge([
            'title' => 'Begrüssung',
            'date' => (new DateTime($header['dtstart']))->format('j. M y G:i')
          ], $this->findLatLng($locstart));
        }
        if ($location) {
          $markers[$location] = array_merge([
            'title' => 'Ort'
          ], $this->findLatLng($location));
        }
        if ($locend) {
          $markers[$locend] = array_merge([
            'title' => 'Verabschiedung',
            'date' => (new DateTime($header['dtend']))->format('j. M y G:i')
          ], $this->findLatLng($locend));
        }
      }
    } else if ($location == $locend) {
      if ($location) {
        $markers[$location] = array_merge([
          'title' => 'Treffpunkt & Ort',
          'date' => $this->startEnd($header['dtstart'], $header['dtend'], true)
        ], $this->findLatLng($locstart));
      }
    } else {
      if ($locstart) {
        $markers[$locstart] = array_merge([
          'title' => 'Ort & Begrüssung',
          'date' => (new DateTime($header['dtend']))->format('j. M y G:i')
        ], $this->findLatLng($location));
      }
      if ($locend) {
        $markers[$locend] = array_merge([
          'title' => 'Verabschiedung',
          'date' => (new DateTime($header['dtend']))->format('j. M y G:i')
        ], $this->findLatLng($locend));
      }
    }

    return $markers;
  }

  private function findLatLng(string $address)
  {
    $cache = $this->grav['cache'];
    $hash = md5($address);

    if (!$latLng = $cache->fetch($hash)) {
      $latLng = $this->geocode($address);
      $cache->save($hash, $latLng);
    }

    return $latLng;
  }

  public function getEventLocations()
  {
    $events = $this->grav['page']->collection([
      'items' => [
        '@page.descendants' => '/chronik',
      ],
      'filter' => [
        'published' => true,
        'type' => 'event'
      ]
    ]);

    $markers = [];

    foreach ($events as $event) {
      $header = (array) $event->header();

      if (isset($header['location'])) {
        $loc = $header['location'];

        if (!isset($markers[$loc])) {
          $markers[$loc] = $this->findLatLng($loc);
          $markers[$loc]['events'] = [];
        }

        $markers[$loc]['events'][] = [
          'title' => $event->title(),
          'url' => $event->url(),
          'category' => $event->taxonomy()['category'][0] ?? '',
          'date' => $this->startEnd($header['dtstart'], $header['dtend'])
        ];
      }
    }

    return $markers;
  }

  public function getGoogleMapsApiKey()
  {
    $config = $this->grav['theme']->config();
    return $config['google_maps_api_key'];
  }

  public function staticMap(array $markers, $size = '400x400')
  {
    $url = 'https://maps.googleapis.com/maps/api/staticmap';

    foreach ($markers as $label => $address) {
      $params[] = "markers=color:gray|label:$label|" . urlencode($address);
    }

    $params[] = "size=$size";
    // $params[] = 'zoom=13';
    $params[] = 'key=' . $this->getGoogleMapsApiKey();
    $query = join('&', $params);

    return "<img src='$url?$query' style='width:100%;'/>";
  }

  public function embedMap(string $location)
  {
    $url = "https://www.google.com/maps/embed/v1/place";
    $key = $this->getGoogleMapsApiKey();
    $q = urlencode($location);

    return "<iframe class='w-full h-full' frameborder='0' style='border:0' src='$url?q=$q&key=$key' allowfullscreen></iframe>";
  }

  public function startEndTime($a, $b, $date = 'd. M y', $time = 'G:i')
  {
    $tz = new \DateTimeZone("Europe/Zurich");
    $dt1 = new DateTime($a, $tz);
    $dt2 = new DateTime($b, $tz);

    if ($dt1->format('d.m.y') == $dt2->format('d.m.y')) {
      return $dt1->format($time);
    } else {
      return $dt1->format("$date $time");
    }
  }

  public function groupByYear($items, $key)
  {
    $arr = [];
    foreach ($items as $item) {
      $header = (array) $item->header();
      $year = (new DateTime($header[$key]))->format('Y');
      $arr[$year][] = $item;
    }
    return $arr;
  }

  public function getEventsAsICal($start = '-6 month', $end = '+1 year')
  {
    $events = $this->grav['page']->collection([
      'items' => [
        '@page.descendants' => '/chronik',
      ],
      'filter' => [
        'published' => true,
        'type' => 'event'
      ],
      'dateRange' => [
        'start' => $start,
        'end' => $end,
        'field' => 'header.dtstart'
      ]
    ]);

    $vcalendar = new VObject\Component\VCalendar();
    $tz = new \DateTimeZone("Europe/Zurich");

    foreach ($events as $event) {
      $header = (array) $event->header();
      $taxonomy = $event->taxonomy();
      $categories = array_merge($taxonomy['category'] ?? [], $taxonomy['group'] ?? [], $taxonomy['tag'] ?? []);
      $label = $taxonomy['category'][0] ?? '';
      $format = 'd. M y G:i';
      $url = $event->url(true);

      if (isset($header['events'])) {
        $count = count($header['events']);
        $duration = $this->startEnd($header['dtstart'], $header['dtend']);

        foreach ($header['events'] as $item) {
          $summary = strtoupper($label == 'Semester' ? ($taxonomy['group'][0] ?? '') : $label) . " - " . $item['title'];

          $dtstart = new DateTime($item['dtstart'], $tz);
          $dtend = new DateTime($item['dtend'], $tz);
          $location = $item['location'] ?? $header['location'] ?? '';

          // resolve start and end location
          $locstart = $item['locstart'] ?? $header['locstart'] ?? '';
          $locend = $item['locend'] ?? $item['locstart'] ?? $header['locend'] ?? $header['locstart'] ?? '';

          // combine description of parent and child
          $description = isset($header['description']) ? "{$header['description']}\n" : '';
          $description .= $item['description'] ?? '';

          // combine start, end and description
          $content = "# Begrüssung\n> {$dtstart->format($format)} / $locstart\n\n";
          $content .= "# Verabschiedung\n> {$dtend->format($format)} / $locend\n\n";
          $content .= "# Anmerkung\n> $description\n\n";

          // state parent relationship
          $content .= "---\nTeil von {$label} '{$event->title()}' / $count Anlässe vom $duration \n\n> ";
          $content .= $url;

          $vcalendar->add('VEVENT', [
            'SUMMARY' => $summary,
            'DTSTART' => $dtstart,
            'DTEND' => $dtend,
            'LOCATION' => $location,
            'DESCRIPTION' => $content,
            'CATEGORIES' => $categories,
            'URL' => $url
          ]);
        }
      } else {
        $summary = strtoupper($label) . " - " . $event->title();
        $dtstart = new DateTime($header['dtstart'], $tz);
        $dtend = new DateTime($header['dtend'], $tz);
        $locstart = $header['locstart'] ?? '';
        $locend = $header['locend'] ?? $header['locstart'] ?? '';
        $location = $header['location'] ?? '';
        $description = $header['description'] ?? '';
        $content = "# Begrüssung\n> {$dtstart->format($format)} / $locstart\n\n";
        $content .= "# Verabschiedung\n> {$dtend->format($format)} / $locend\n\n";
        $content .= "# Anmerkung\n> $description\n\n";
        $content .= "---\n>$url";

        $vcalendar->add('VEVENT', [
          'SUMMARY' => $summary,
          'DTSTART' => $dtstart,
          'DTEND' => $dtend,
          'LOCATION' => $header['location'] ?? '',
          'DESCRIPTION' => $content,
          'CATEGORIES' => $categories,
          'URL' => $url
        ]);
      }
    }

    return $vcalendar->serialize();
  }

  protected function getHslFromString(string $str, $saturation = 100, $lightness = 30)
  {
    $chars = str_split($str);
    $n = array_reduce($chars, function ($acc, $char) {
      return ord($char) + (($acc << 5) - $acc);
    }, 0);
    $hue = $n % 360;
    return "hsl($hue, $saturation%, $lightness%)";
  }

  public function getEventsInRange($a, $b)
  {
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

    $items = [];
    $parent = null;
    $tz = new \DateTimeZone("Europe/Zurich");

    // @see https://fullcalendar.io/docs/event-object
    foreach ($events as $event) {
      $header = (array) $event->header();

      $dtstart = new DateTime($header['dtstart'], $tz);
      $dtend = new DateTime($header['dtend'], $tz);

      if (($rangeStart < $dtstart && $dtstart < $rangeEnd) || ($rangeStart < $dtend && $dtend < $rangeEnd) || ($dtstart < $rangeStart && $rangeEnd < $dtend)) {

        $start = $dtstart->format(DateTime::ISO8601);
        $end = $dtend->format(DateTime::ISO8601);

        $groups = $event->taxonomy()['group'] ?? [];
        $categories = $event->taxonomy()['category'] ?? [];
        $subevents = $header['events'] ?? [];
        $total = count($subevents);

        // start with event item
        $title = $event->title();
        $url = $event->url();
        $color = $this->getHslFromString(implode('', $groups));
        $extendedProps = [
          'duration' => $this->startEnd($start, $end, true),
          'parent' => null
        ];

        if ($total > 0) {
          $duration = $this->startEnd($start, $end);
          foreach ($subevents as $i => $subevent) {
            $item = compact('title', 'url', 'color', 'extendedProps');
            $nr = $i + 1;
            $item['title'] = "$title [{$nr}/$total]: {$subevent['title']}";
            $item['start'] = (new DateTime($subevent['dtstart'], $tz))->format(DateTime::ISO8601);
            $item['end'] = (new DateTime($subevent['dtend'], $tz))->format(DateTime::ISO8601);
            $item['extendedProps']['duration'] = $this->startEnd($subevent['dtstart'], $subevent['dtend'], true);
            $item['extendedProps']['parent'] = "Anlass {$nr}/$total des {$categories[0]} '$title' vom $duration";
            $items[] = $item;
          }
        } else {
          $items[] = compact('title', 'url', 'start', 'end', 'extendedProps');
        }
      }
    }
    return $items;
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
