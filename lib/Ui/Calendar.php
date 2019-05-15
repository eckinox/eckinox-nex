<?php

namespace Eckinox\Nex\Ui;

use Eckinox\Nex\{
    HtmlNode
};

class Calendar extends HtmlNode {
    protected $date = null;
    protected $prepend_month_name = true;

    public function __construct($date = null) {
        parent::__construct();

        $this->date = $this->date( $date ?: new \DateTime("now") );
        $this->attr(['class' => 'calendar-wrapper', 'unselectable']);
    }

    public function date($set = null) {
        return $set === null ? $this->date : $this->date = $set;
    }

    public function render($callback = null, $full_calendar = true) {
        $day = 0;
        $m   = ltrim($this->date->format('m'), '0');
        $y   = $this->date->format('Y');
        $idx = ( new \DateTime( $y."-$m-1" ) )->format('w');
        $day_count = cal_days_in_month(CAL_GREGORIAN, $m, $y);

        $this->append( $month = HtmlNode::create('div', ['class' => "month-".$m]) );

        $this->{$this->prepend_month_name ? "prepend" : "append"}(
            HtmlNode::create('div', [ 'class' => 'month-wrapper' ])->append(
                HtmlNode::create('div', [ 'class' => 'name' ])->text(array_values($this->lang("nex.date.month"))[$m - 1]),
                HtmlNode::create('div', [ 'class' => 'year' ])->text($y)
            )
        );

        $this_month = date('Y-m') === "$y-" . sprintf("%02d", $m);

        for($w = 0; $w < 6; $w++) {
            $month->append(
                $week = HtmlNode::create('div', [ 'class' => "week week-$w" ])
            );

            for($d = 0; $d <= 6; $d++) {
                $pos = (($w * 6) + $d);

                $has_day = ( $pos >= $idx ) && ( $day < $day_count );
                $node = HtmlNode::create('div', [ 'class' => 'day '.($this_month && ( $day + 1 === (int)date('d') ) ? "is-now " : "" ) . ( $has_day ? "has-day" : "is-empty" ) ])->html( $has_day ? ++$day : "&nbsp;" );

                if ( $callback ) {
                    $callback($node, $week, $day, $m, $y);
                }

                $week->append(
                    $node
                );
            }

            if ( ! $full_calendar && ( $day === $day_count ) ) {
                break;
            }
        }

        return parent::render();
    }
}
