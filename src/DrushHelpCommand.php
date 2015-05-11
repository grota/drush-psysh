<?php
/**
 * @file
 * Help command.
 *
 * Original author: Justin Hileman
 */

namespace DrushPsysh;

use Psy\Command\Command as BaseCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Help command.
 *
 * Lists available commands, and gives command-specific help when asked nicely.
 */
class DrushHelpCommand extends BaseCommand {
  const NON_DRUSH_CATEGORY = 'PsySH commands';

  private $command;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('help')
      ->setAliases(array('?'))
      ->setDefinition(array(
        new InputArgument('command_name', InputArgument::OPTIONAL, 'The command name', NULL),
      ))
      ->setDescription('Show a list of commands. Type `help [foo]` for information about [foo].')
      ->setHelp('My. How meta.');
  }

  /**
   * Helper for setting a subcommand to retrieve help for.
   */
  public function setCommand($command) {
    $this->command = $command;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if ($this->command !== NULL) {
      // Help for an individual command.
      $output->page($this->command->asText());
      $this->command = NULL;
    }
    elseif ($name = $input->getArgument('command_name')) {
      // Help for an individual command.
      $output->page($this->getApplication()->get($name)->asText());
    }
    else {
      $categories = array();

      // List available commands.
      $commands = $this->getApplication()->all();

      // Find the alignment width.
      $width = 0;
      foreach ($commands as $command) {
        $width = strlen($command->getName()) > $width ? strlen($command->getName()) : $width;
      }
      $width += 2;

      foreach ($commands as $name => $command) {
        if ($name !== $command->getName()) {
          continue;
        }

        if ($command->getAliases()) {
          $aliases = sprintf('  <comment>Aliases:</comment> %s', implode(', ', $command->getAliases()));
        }
        else {
          $aliases = '';
        }

        if ($command instanceof DrushCommand) {
          $category = $command->getCategory();
        }
        else {
          $category = self::NON_DRUSH_CATEGORY;
        }

        if (!isset($categories[$category])) {
          $categories[$category] = array();
        }

        $categories[$category][] = sprintf("    <info>%-${width}s</info> %s%s", $name, $command->getDescription(), $aliases);
      }

      $messages = array();
      foreach ($categories as $name => $category) {
        $messages[] = '';
        $messages[] = sprintf('<comment>%s</comment>', OutputFormatter::escape($name));
        foreach ($category as $message) {
          $messages[] = $message;
        }
      }

      $output->page($messages);
    }
  }

}
