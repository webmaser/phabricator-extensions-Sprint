<?php

final class SprintStatsTest extends SprintTestCase {


  private $short_interval = 28800; // 8 hours
  private $day_interval = 86400; // 1 days
  private $week_interval = 604800; // 1 week
  private $multi_month_interval = 7257600; // 3 months
  private $year_interval = 29030400;  // 1 year

  public function testSetTimezone() {
    $stats = new SprintStats();
    $viewer = $this->generateNewTestUser();
    $timezone = $stats->setTimezone($viewer);
    $expected_timezone = new DateTimeZone($viewer->getTimezoneIdentifier());
    $this->AssertEquals($expected_timezone, $timezone);
  }

  /**
   * @return DateIterator
   */
  public function providerDateArray() {

    $date = new DateTime();
    $now = $date->format('U');
    $short = $this->short_date($now);
    $medium = $this->medium_date($now);
    $long = $this->long_date($now);
    return new DateIterator(array($short), array($medium), array($long));
  }


  /**
   * @param string $now
   */
  public function short_date($now) {
    $short = array($now + $this->short_interval, $now + $this->short_interval * 2, $now + $this->short_interval * 3, $now + $this->short_interval * 4, $now + $this->short_interval * 5);
    return $short;
  }

  /**
   * @param string $now
   */
  public function medium_date($now) {
    $medium = array($now + $this->day_interval, $now + $this->day_interval * 2, $now + $this->day_interval * 3, $now + $this->day_interval * 4, $now + $this->day_interval * 5);
    return $medium;
  }

  /**
   * @param string $now
   */
  public function long_date($now) {
    $long = array($now + $this->week_interval, $now + $this->week_interval * 2, $now + $this->week_interval * 4, $this->multi_month_interval, $this->year_interval);
    return $long;
  }

  /**
   * @param array $end
   * @dataProvider providerDateArray
   */
  public function testbuildTimeSeries($end) {
    $stats = new SprintStats();
    $viewer = $this->generateNewTestUser();
    $timezone = $stats->setTimezone($viewer);
    $date = new DateTime();
    $now = $date->format('U');
    $timeseries = $stats->buildDateArray($now, $end, $timezone);
    $this->assertInstanceOf('BurndownDataDate', array_pop($timeseries));
  }

  public function testbuildBefore() {
    $stats = new SprintStats();
    $viewer = $this->generateNewTestUser();
    $timezone = $stats->setTimezone($viewer);
    $date = new DateTime();
    $now = $date->format('U');
    $before = $stats->buildBefore($now, $timezone);
    $this->assertInstanceOf('BurndownDataDate', $before);
  }

  public function testgetBurndownDate() {
    $stats = new SprintStats();
    $date = 'Thu Jan 1';
    $date = $stats->getBurndownDate($date);
    $this->assertInstanceOf('BurndownDataDate', $date);
  }

  public function testsumSprintStats() {
    $stats = new SprintStats();
    $viewer = $this->generateNewTestUser();
    $timezone = $stats->setTimezone($viewer);
    $date = new DateTime();
    $now = $date->format('U');
    $end = $now + $this->week_interval * 2;
    $timeseries = $stats->buildDateArray($now, $end, $timezone);
    $dates = $stats->sumSprintStats($timeseries);
    $this->assertInstanceOf('BurndownDataDate', array_pop($dates));
  }

  public function testsumTotalTasks() {
    $stats = new SprintStats();
    $viewer = $this->generateNewTestUser();
    $timezone = $stats->setTimezone($viewer);
    $date = new DateTime();
    $now = $date->format('U');
    $end = $now + $this->week_interval * 2;
    $timeseries = $stats->buildDateArray($now, $end, $timezone);
    $dates = $stats->sumTotalTasks($timeseries);
    $this->assertInstanceOf('BurndownDataDate', array_pop($dates));
  }

  public function testsumTotalPoints() {
    $stats = new SprintStats();
    $viewer = $this->generateNewTestUser();
    $timezone = $stats->setTimezone($viewer);
    $date = new DateTime();
    $now = $date->format('U');
    $end = $now + $this->week_interval * 2;
    $timeseries = $stats->buildDateArray($now, $end, $timezone);
     $dates = $stats->sumTotalPoints($timeseries);
    $this->assertInstanceOf('BurndownDataDate', array_pop($dates));
  }

  public function testcalcPointsRemaining() {
    $stats = new SprintStats();
    $viewer = $this->generateNewTestUser();
    $timezone = $stats->setTimezone($viewer);
    $date = new DateTime();
    $now = $date->format('U');
    $end = $now + $this->week_interval * 2;
    $timeseries = $stats->buildDateArray($now, $end, $timezone);
    $dates = $stats->calcPointsRemaining($timeseries);
    $this->assertInstanceOf('BurndownDataDate', array_pop($dates));
  }

  public function testcalcTasksRemaining() {
    $stats = new SprintStats();
    $viewer = $this->generateNewTestUser();
    $timezone = $stats->setTimezone($viewer);
    $date = new DateTime();
    $now = $date->format('U');
    $end = $now + $this->week_interval * 2;
    $timeseries = $stats->buildDateArray($now, $end, $timezone);
    $dates = $stats->calcTasksRemaining($timeseries);
    $this->assertInstanceOf('BurndownDataDate', array_pop($dates));
  }

  public function testcomputeIdealPoints() {
    $stats = new SprintStats();
    $viewer = $this->generateNewTestUser();
    $timezone = $stats->setTimezone($viewer);
    $date = new DateTime();
    $now = $date->format('U');
    $end = $now + $this->week_interval * 2;
    $timeseries = $stats->buildDateArray($now, $end, $timezone);
    $dates = $stats->computeIdealPoints($timeseries);
    $this->assertInstanceOf('BurndownDataDate', array_pop($dates));
  }

  public function testbuildDataSet() {
    $stats = new SprintStats();
    $viewer = $this->generateNewTestUser();
    $timezone = $stats->setTimezone($viewer);
    $date = new DateTime();
    $now = $date->format('U');
    $end = $now + $this->week_interval * 2;
    $timeseries = $stats->buildDateArray($now, $end, $timezone);
    $series = array('Start Points', 'Remaining Points', 'Ideal Points', 'Points Closed Today');
    $data = $stats->buildDataSet($timeseries);
    $this->assertEquals($series, $data[0]);
  }
}
