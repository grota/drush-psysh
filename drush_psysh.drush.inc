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
    'aliases' => array('shell', 'repl', 'psysh'),
    'examples' => array(
      'drush shell' => 'Invokes the Drupal REPL',
    ),
  );
  return $items;
}

function drush_psysh_callback() {
  require_once __DIR__ . '/vendor/autoload.php';

  $commands = array(new DrushPsysh\DrushHelpCommand());

  foreach (drush_get_commands() as $name => $config) {
    // Ignore some commands that don't make sense inside PsySH.
    if (in_array($name, array('help', 'drush-psysh', 'php-eval'))) {
      continue;
    }

    // Don't add hidden commands or aliases.
    if ($config['hidden'] || $name !== $config['command']) {
      continue;
    }

    $commands[] = new DrushPsysh\DrushCommand($config);
  }

  $shell = new Psy\Shell();
  $shell->addCommands($commands);
  $shell->run();
}
