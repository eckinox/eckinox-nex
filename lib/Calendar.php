<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0.2
 * @package      Nex
 * @subpackage   lib
 *
 * @update (23/02/2010) [Mikael Laforge] - 1.0.0 - Script creation
 * @update (12/10/2012) [Mikael Laforge] - 1.0.1 - Event link anchor is now added to day number if its the only event this day
 * @update (14/05/2013) [ML] - 1.0.2 - Added support for "target" option in event list
 *
 * 23/02/2010
 * This class was made to help create calendars
 */
class Calendar {

    // Calendar div id
    protected $div_id = null;
    // Current year and month of diplay
    protected $year = null;
    protected $month = null;
    // Links url used by months, weeks, days
    protected $month_links = "";
    protected $week_links = "";
    protected $day_links = "";
    // Param used in url
    protected $day_param = 'day';
    protected $week_param = 'week';
    protected $month_param = 'month';
    protected $year_param = 'year';
    protected $month_navigation = true;
    protected $c_today = false; // Show today's literal day in header
    protected $c_month = true;
    protected $c_day = false; // See day number ( 1,2,3,4,5,6,7 ). Day format, see date() http://ca2.php.net/manual/en/function.date.php
    protected $c_week = false; // See week number ( 1 to 52 )
    protected $c_event = false; // See events under calendar
    protected $day_event = true; // See events on day TDs
    protected $month_activeLinks = false;
    protected $week_activeLinks = false;
    protected $day_activeLinks = false;
    protected $futur = true;
    protected $past = true;
    protected $present = true;
    protected $dateMin = "";
    protected $dateMax = "";
    protected $short_day = true;
    protected $short_week = false;
    protected $short_month = false;
    protected $hour = false;
    protected $events = [];

    /* protected $ajax = false;
      protected $ajaxDivId = "";
      protected $ajaxUrl = "";
      protected $ajaxXtraParam = ""; */

    /**
     * Constructor
     * @param string $div_id id of calendar div
     * @param int $year current year of calendar
     * @param int $month current month of calendar
     */
    public function __construct($div_id = 'calendar', $year = null, $month = null) {
        $this->div_id = $div_id;

        $this->year = ($year == null) ? date('Y') : $year;
        $this->month = ($month == null) ? date('m') : $month;

        $this->set_baseLink();
    }

    public function set_baseLink($uri = null) {
        $uri = !$uri ? url::site(Router::uri()) : $uri;

        list($url, $query) = url::splitOnQuery($uri);

        $boom = arr::explode('&', '=', $query);
        $query = arr::implode('&', '=', $boom, array($this->day_param, $this->month_param, $this->year_param, $this->week_param));

        $uri = $url . ($query ? '?' . $query : '');

        $this->month_links = $this->week_links = $this->week_links = $this->day_links = $uri;
    }

    /**
     * Change month link
     * @param string $link link to use
     */
    public function set_monthLink($link) {
        $this->month_links = $link;
    }

    /**
     * Change week link
     * @param string $link link to use
     */
    public function set_weekLink($a_lien) {
        $this->week_links = $a_lien;
    }

    /**
     * Change day link
     * @param string $link link to use
     */
    public function set_dayLink($link) {
        $this->day_links = $link;
    }

    /**
     * Change Day param used
     * @param string $param name of param
     */
    public function set_dayParam($param) {
        $this->day_param = $param;
    }

    /**
     * Change week param used
     * @param string $param name of param
     */
    public function set_weekParam($param) {
        $this->week_param = $param;
    }

    /**
     * Change month param used
     * @param string $param name of param
     */
    public function set_monthParam($param) {
        $this->month_param = $param;
    }

    /**
     * Change year param used
     * @param string $param name of param
     */
    public function set_yearParam($param) {
        $this->year_param = $param;
    }

    /**
     * Minimum date of calendar
     * @param string $date
     */
    public function set_dateMin($date) {
        $this->dateMin = date::dateToTimestamp($date);
    }

    /**
     * Maximum date of calendar
     * @param string $date
     */
    public function set_dateMax($date) {
        $this->dateMax = date::dateToTimestamp($date);
    }

    /**
     * Display or not month navigation
     * @param bool $active
     */
    public function displayMonthNav($active = true) {
        $this->month_navigation = $active;
    }

    /**
     * Display or not today's literal day in header
     * @param bool $active
     */
    public function displayToday($active = true) {
        $this->c_today = $active;
    }

    /**
     * Display or not month var
     * If not active, month nav should also be desactivated
     * @param bool $active
     */
    public function displayMonth($active = true) {
        $this->c_month = $active;
        if ($active == false) {
            $this->displayMonthNav(false);
        }
    }

    /**
     * Display or not weeks
     * @param bool $active
     */
    public function displayWeek($active = true) {
        $this->c_week = $active;
    }

    /**
     * Display or not literal days
     * @param bool $active
     */
    public function displayDay($format = 'N') {
        $this->c_day = $format;
    }

    /**
     * Display or not calendar events
     * @param bool $active
     */
    public function displayEvent($active = true) {
        $this->c_event = $active;
    }

    /**
     * Active or not links on month
     * @param bool $active
     */
    public function activeMonthLink($active = true) {
        $this->month_activeLinks = $active;
    }

    /**
     * Active or not links on week
     * @param bool $active
     */
    public function activeWeekLink($active = true) {
        $this->week_activeLinks = $active;
    }

    /**
     * Active or not links on days
     * @param bool $active
     */
    public function activeDayLink($active = true) {
        $this->day_activeLinks = $active;
    }

    /**
     * Active or not futur links
     * @param bool $active
     */
    public function activeFutur($active = true) {
        $this->futur = $active;
    }

    /**
     * Active or not present link
     * @param bool $active
     */
    public function activePresent($active = true) {
        $this->present = $active;
    }

    /**
     * Active or not past link
     * @param bool $active
     */
    public function activePast($active = true) {
        $this->past = $active;
    }

    /**
     * Active or not day having event
     * @param bool $active
     */
    public function activeDayEvent($active = true) {
        $this->day_event = $active;
    }

    /**
     * Add event
     * @param string $date
     * @param string $event url | style | title | class | id
     * Events are kept in class array like this
     * $events['AAAA-MM-JJ HH:MM:SS']
     */
    public function addEvent($date, $event = []) {
        $date = date::timestampToDate($date, 'Y-m-d');
        if (!isset($this->events[$date])) {
            $this->events[$date] = [];
        }

        array_push($this->events[$date], $event);
    }

    /**
     * Render calendar with year and month
     * @param bool $render_now
     */
    public function render($render_now = false) {
        // Init
        $htmlEvent = "";
        $onclick = "";
        $year = $this->year;
        $month = $this->month;

        // If we dont display futur and past, no links on weeks
        if (!$this->futur && !$this->past) {
            $this->week_activeLinks = false;
        }

        $date = mktime(0, 0, 0, $month, 1, $year);
        $current_month = intval($month);

        // Limits
        if ($this->dateMin) {
            $min_month = date('m', $this->dateMin);
            $min_week = sprintf("%s-%s", date('Y', $this->dateMin), date('W', $this->dateMin));
        }
        if ($this->dateMax) {
            $max_month = date('m', $this->dateMax);
            $max_week = sprintf("%s-%s", date('Y', $this->dateMax), date('W', $this->dateMax));
        }

        // First day of month
        $decal = date("w", $date) + 1; // N or w + 1
        // Number of day in the month
        $max = date('t', $date);

        // Precedent month
        if (intval($month) === 1) {
            $prec_m = 12;
            $prec_y = $year - 1;
            $next_y = $year;
            $next_m = $month + 1;
        } elseif ($month == 12) {
            $next_m = 1;
            $next_y = $year + 1;
            $prec_y = $year;
            $prec_m = $month - 1;
        } else {
            $next_y = $year;
            $prec_y = $year;
            $next_m = $month + 1;
            $prec_m = $month - 1;
        }

        $html = "<div id=\"" . $this->div_id . "\" class='calendar'>\n";

        $html .= "<div class='header'>";

        // Number of columns
        $nbCols = $this->c_week ? 8 : 7;

        // Today's date
        if ($this->c_today) {
            $html .= "<div class='today title'><span>" . date::literalDate(time(), false, true) . "</span></div>";
        }

        // Month navigation
        $month_nav = '';
        if ($this->c_month) {
            $month_nav .= "<div class='month-nav'>";

            $month_links = url::addParam($this->month_links, array($this->month_param => $month, $this->year_param => $year, $this->day_param => '0'));
            //$month_links = $this->month_links."&mois=".$month."&annee=".$year."&jour=0";

            $month_linksPrec = url::addParam($this->month_links, array($this->month_param => $prec_m, $this->year_param => $prec_y, $this->day_param => '0'));
            $month_linksNext = url::addParam($this->month_links, array($this->month_param => $next_m, $this->year_param => $next_y, $this->day_param => '0'));

            //$month_linksPrec = isset($this->formatLienMois) ? sprintf($this->formatLienMois,$prec_y,$prec_m) : $this->month_links."&mois=".$prec_m."&annee=".$prec_y."&jour=0";
            //$month_linksNext = isset($this->formatLienMois) ? sprintf($this->formatLienMois,$next_y,$next_m) : $this->month_links."&mois=".$next_m."&annee=".$next_y."&jour=0";
            $onclickPrec = $onclickNext = "";

            $colspan = $this->month_navigation ? ($nbCols - 2) : $nbCols;


            // Link for precedent month
            if (!$this->month_navigation) {
                //$month_nav .= "";
            } elseif (($this->dateMin && $date <= $this->dateMin)  // date inferieure à limite inf
                    || (!$this->past && date::timestampToDate($date, 'Y-m-d') <= gmdate("Y-m-d"))) {
                $month_nav .= "<span class=\"month prev limit\">&nbsp;</span>";
            } else {
                $month_nav .= "<span class=\"month prev\"><a " . (strlen($month_linksPrec) > 0 ? "href=\"" . $month_linksPrec . "\"" : "") . $onclickPrec . "><strong>" . Nex::lang('date.prevMonth') . "</strong></a></span>";
            }

            // Link for current month
            $month_nav .= " <span class=\"current month\">" .
                    ($this->month_activeLinks ? "<a href=\"" . $month_links . "\">" : "") .
                    sprintf("%s %04d", date::literalMonth($current_month, $this->short_month), $year) .
                    ($this->month_activeLinks ? "</a>" : "") .
                    "</span> ";

            // Link for next month
            if (!$this->month_navigation) {
                $month_nav .= "";
            } elseif (($this->dateMax && ( $date + $max * 86400 ) >= $this->dateMax)  // date inferieure à limite inf
                    || (!$this->futur && date::timestampToDate($date + $max * 86400, 'Y-m-d') >= gmdate("Y-m-d"))) {
                $month_nav .= "<span class=\"month next limit\">&nbsp;</span>";
            } else {
                $month_nav .= "<span class=\"month next\"><a " . (strlen($month_linksNext) > 0 ? "href=\"" . $month_linksNext . "\"" : "") . $onclickNext . "><strong>" . Nex::lang('date.nextMonth') . "</strong></a></span>";
            }

            $month_nav .= "</div>";
        }
        $html .= $month_nav;

        $html .= "</div>"; // End header

        $html .= "<table>\n";
        $html .= "<thead>\n";

        // Days
        if ($this->c_day) {
            $html .= "<tr>\n";
            if ($this->c_week) {
                $html .= "<th>&nbsp;</th>";
            }

            $days = array(2, 3, 4, 5, 6, 7, 1);
            $x = 0;
            foreach ($days as $j) {
                $timestamp = mktime(1, 1, 1, 1, $j, 2000);
                $html .= "<th class=\"day " . ($x % 2 ? 'even' : 'odd') . "\"><span class='weekday'>" . date::dayOfWeek($timestamp, $this->short_day) . "</span></th>";
                $x++;
            }

            $html .= "</tr>\n";
        }

        // Weeks
        if ($this->c_week) {
            $week = gmdate("Y-W", mktime(12, 0, 0, $month, 1, $year));
            $noWeek = gmdate("W", mktime(12, 0, 0, $month, 1, $year));
            $curWeek = gmdate("Y-W");
            $yearWeek = ($month == 12 && $week == 1) ? $year + 1 : $year;
            $week_link = url::addParam($this->week_links, array($this->year_param => $year, $this->week_param => $noWeek));
            //$week_link = isset($this->formatLienSemaines) ? sprintf($this->formatLienSemaines,$year,$week) : $this->week_links."&annee=".$annee."&semaine=".$noWeek;

            if ($this->week_activeLinks == false || (isset($this->dateMin, $min_week) && $week < $min_week) || (isset($this->dateMax, $max_week) && $week > $max_week) || (!$this->futur && $week > $curWeek) || (!$this->past && $week < $curWeek)
            ) {
                $html .= "<tr><th class='week'>" . $noWeek . "</th>";
            } else {
                $html .= "<tr><th class='week'><a href=\"" . $week_link . "\">" . $noWeek . "</a></th>";
            }
        }

        $html .= "</thead>\n";
        $html .= "<tbody>\n";

        // Month day
        $x = 0;
        $tr_open = false;
        for ($i = 1; $i < 43; $i++) {
            if (($i - 1) % (7) == 0) {
                $x = 0;
                $html .= "<tr>\n";
                $tr_open = true;
            }

            $day = $i - $decal + 1;
            $dayStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
            $day_timestamp = date::dateToTimestamp($dayStr);

            $day_link = url::addParam($this->day_links, array($this->year_param => $year, $this->month_param => $month, $this->day_param => $day));
            //$day_link = isset($this->formatLienJours) ? sprintf($this->formatLienJours,$year,$month,$day) : $this->day_links.sprintf("&annee=%04d&mois=%02d&jour=%02d",$year,$month,$day);
            // Display day
            if ($day > 0 && $day <= $max) {
                $has_event = false;
                // Day with events
                if ($this->c_event == true) {
                    // Event div
                    if (isset($this->events[$dayStr]) && is_array($this->events[$dayStr])) {
                        $htmlEvent .= "<div class=\"event\" style=\"display:none;\">";
                        foreach ($this->events[$dayStr] as $event) {
                            if (empty($event['title']))
                                continue;

                            $htmlEvent .= $event['title'];
                        }
                        $htmlEvent .= "</div>\n";
                        $has_event = true;
                    }
                }

                // Check past, futur , present
                if ($dayStr < date("Y-m-d") && $this->past == true)
                    $class = 'past';
                elseif ($dayStr > date("Y-m-d") && $this->futur == true)
                    $class = 'futur';
                elseif ($dayStr == date("Y-m-d") && $this->present == true)
                    $class = 'today';
                else
                    $class = '';

                $class .= ($x % 2 ? ' even' : ' odd');

                // Build html
                // INACTIVE
                if (($this->dateMin && $dayStr < date::timestampToDate($this->dateMin)) || ($this->dateMax && $dayStr > date::timestampToDate($this->dateMax))) {
                    $html .= "<td class=\"inactive $class\"><div class='wrapper'><span class='day'>" . sprintf("%02d", $day) . "</span></div></td>\n";
                }
                // EVENT
                elseif (isset($this->events[$dayStr]) && is_array($this->events[$dayStr])) {
                    $events = $this->events[$dayStr];
                    $html .= "<td class=\"active has-event has-event-" . count($events) . " $class\"><div class='wrapper'>";

                    $html .= '<ul class="events">';
                    foreach ($events as $event) {
                        $html .= "<li " . (isset($event['id']) ? 'id="' . $event['id'] . '" ' : '') . "class=\"event " . (isset($event['class']) ? $event['class'] : '') . "\" style=\"" . (isset($event['style']) ? $event['style'] : '') . "\">" .
                                "<a" . (!empty($event['url']) ? " href=\"" . $event['url'] . "\"" : '') . " style=\"" . (isset($event['astyle']) ? $event['astyle'] : '') . "\"" . (isset($event['target']) ? 'target="' . $event['target'] . '"' : '') . ">" .
                                (!empty($event['title']) ? '<span class="title">' . $event['title'] . '</span>' : '') .
                                "</a>" .
                                (isset($event['jsonld']) ? $event['jsonld'] : '') .
                                "</li>";
                    }
                    $html .= '</ul>';

                    $html .= "<span class='day'>";

                    if ($this->day_activeLinks == true)
                        $html .= "<a href=\"" . $day_link . "\">";
                    elseif (count($events) === 1 && !empty($events[0]['url']))
                        $html .= "<a href=\"" . $events[0]['url'] . "\">";

                    $html .= sprintf("%02d", $day);

                    if ($this->day_activeLinks == true || (count($events) === 1 && !empty($events[0]['url'])))
                        $html .= "</a>";

                    $html .= "</span>";
                    $html .= "</div></td>";
                }
                // NORMAL DAY
                else {
                    $html .= "<td class=\"active $class\"><div class='wrapper'>" .
                            "<span class='day'>" .
                            (($this->day_activeLinks == true) ? "<a href=\"" . $day_link . "\">" : '') .
                            sprintf("%02d", $day) .
                            (($this->day_activeLinks == true) ? "</a>" : '') .
                            "</span>" .
                            "</div></td>";
                }
            }
            // Just to make sure table isnt fucked up some day
            // This should never happen
            else
                $html .= "<td>&nbsp;</td>";


            // End of TR
            if ($i % (7) == 0) {
                $html .= "</tr>\n";
                $tr_open = false;

                if ($i >= ( $max + $decal - 1))
                    break;

                // Next line week number
                if ($this->c_week && $i < 42) {
                    $week = gmdate("Y-W", mktime(12, 0, 0, $month, $day + 1, $year));
                    $noWeek = gmdate("W", mktime(12, 0, 0, $month, $day + 1, $year));

                    if ($this->week_activeLinks == false || (isset($this->dateMin, $min_week) && $week < $min_week) || (isset($this->dateMax, $max_week) && $week > $max_week) || (!$this->futur && $week > $curWeek) || (!$this->past && $week < $curWeek)
                    )
                        $html .= "<tr><th>" . $noWeek . "</th>";
                    else
                        $html .= "<tr><th><a href=\"" . $week_link . "\">" . $noWeek . "</a></th>";
                }
            }

            $x++;
        } // End for

        if ($tr_open == true) {
            $html .= "</tr>\n";
        }

        $html .= "</tbody>\n";
        $html .= "</table>\n";

        // Footer
        $html .= '<div class="footer">' . $month_nav . "</div>"; // End footer
        // affichage des événements
        if ($this->c_event) {
            $html .= "<div id=\"" . $this->div_id . "_events\">";
            $html .= $htmlEvent;
            $html .= "</div>";
        }
        $html .= "</div>";

        if ($render_now == true) {
            echo $html;
            return true;
        }

        return $html;
    }

}
