<?php
/**
 * @file
 * Drush wrapper around the psysh REPL.
 */

/**
 * Implements hook_drush_help().
 */
function drush_psysh_drush_help($section) {
  switch ($section) {
    case 'meta:drush_psysh:title':
      return dt('Drupal REPL');

    default:
      break;
  }
}

/**
 * Implements hook_drush_command().
 */
function drush_psysh_drush_command() {
  $items['drush-psysh-install-deps'] = array(
    'callback'    => 'drush_psysh_install_deps',
    'description' => 'Install drush-psysh dependencies via composer',
    'bootstrap'   => DRUSH_BOOTSTRAP_DRUSH,
  );
  $items['drush-psysh'] = array(
    'callback'    => 'drush_psysh_callback',
    'description' => 'Drupal REPL via psysh',
    'aliases'     => array('shell', 'repl', 'psysh'),
    'examples'    => array(
      'drush shell' => 'Invokes the Drupal REPL',
    ),
  );
  return $items;
}

/**
 * Repl starting drush callback.
 */
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

/**
 * Drush-psysh dependency installation callback.
 */
function drush_psysh_install_deps() {
  $autoload = __DIR__ . '/vendor/autoload.php';
  if (is_file($autoload) && is_readable($autoload)) {
    return drush_log(dt('Dependencies already installed'), 'success');
  }
  $cmd = 'composer install';
  $rc = drush_shell_cd_and_exec(__DIR__, $cmd);
  if (!$rc) {
    return drush_set_error(dt('Error executing "!cmd" in "!dir"', array('!cmd' => $cmd, '!dir' => __DIR__)));
  }
  drush_log(dt('Dependencies installed'), 'success');
}
