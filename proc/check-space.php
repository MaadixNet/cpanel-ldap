<?php
/**
 * Created by Maddish
 *
 * function file called by service-available.php
 * Check required fields of groups
 *
 */
session_start();
require_once __DIR__.'/../classes/class.DiskStatus.php';
try {
  $diskStatus = new DiskStatus('/');
  $freeSpace = $diskStatus->freeSpace();
  $totalSpace = $diskStatus->totalSpace();
  $barWidth = ($diskStatus->usedSpace()/100) * 300;//300 is the width of the bar in pxx
} catch (Exception $e) {
  echo 'Error ('.$e->getMessage().')';
  exit();
}
