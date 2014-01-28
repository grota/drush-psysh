<?php
/**
 * Implements hook_drush_help
 */
function drush_psysh_drush_help($section) {
  switch ($section) {
  case 'meta:drush_psysh:title':
    return dt('Drupal REPL');
      break;
    default:
      break;
  }
}

/**
 * Implementation of hook_drush_command().
 */
function drush_psysh_drush_command() {
  $items['drush-psysh'] = array(
    'callback' => 'drush_psysh_callback',
    'description' => 'Drupal REPL via psysh',
    'aliases' => array('psysh', 'repl'),
    'examples' => array(
      'drush psysh' => 'Invokes the Drupal REPL',
    ),
  );
  return $items;
}

function drush_psysh_callback() {
  require_once __DIR__ . '/vendor/autoload.php';
  $shell = new Psy\Shell();
  $shell->run();
}
