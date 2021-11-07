<?php

namespace Grav\Theme;

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
    $this->grav['twig']->twig()->addFilter(new \Twig\TwigFilter('startEndTime', [$this, 'startEndTime']));
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

  private function findLatLnt(string $address)
  {
    $cache = $this->grav['cache'];
    $hash = md5($address);
    $latLng = $cache->fetch($hash);

    if (!$latLng) {
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

    $locations = [];

    foreach ($events as $event) {
      $header = (array) $event->header();
      
      if (isset($header['location'])) {
        $loc = $header['location'];

        if (!isset($locations[$loc])) {
          $locations[$loc] = $this->findLatLnt($loc);
          $locations[$loc]['events'] = [];
        }

        $locations[$loc]['events'][] = [
          'title' => $event->title(),
          'url' => $event->url(),
          'category' => $event->taxonomy()['category'][0] ?? '',
          'date' => $this->startEnd($header['dtstart'], $header['dtend'])
        ];
      }
    }

    return $locations;
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

    $arr = [];
    $tz = new \DateTimeZone("Europe/Zurich");

    // @see https://fullcalendar.io/docs/event-object
    foreach ($events as $event) {
      $header = (array) $event->header();
      $start = new DateTime($header['dtstart'], $tz);
      $end = new DateTime($header['dtend'], $tz);
      
      // TODO: colorcode according to category and group
      if (($rangeStart < $start && $start < $rangeEnd) || ($rangeStart < $end && $end < $rangeEnd) || ($start < $rangeStart && $rangeEnd < $end)) {
        if (isset($header['events'])) {
          foreach ($header['events'] as $subevent) {
            $arr[] = [
              'title' => $subevent['title'],
              'start' => (new DateTime($subevent['dtstart'], $tz))->format(DateTime::ISO8601),
              'end' => (new DateTime($subevent['dtend'], $tz))->format(DateTime::ISO8601),
              'url' => $event->url()
            ];
          }
        } else {
          $arr[] = [
            'title' => $event->title(),
            'start' => $start->format(DateTime::ISO8601),
            'end' => $end->format(DateTime::ISO8601),
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
