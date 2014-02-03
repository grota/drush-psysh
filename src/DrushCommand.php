<?php

namespace DrushPsysh;

use Psy\Command\Command as BaseCommand;
use Psy\Exception\RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * DrushCommand is a PsySH proxy command which accepts a drush command config
 * array and tries to build an appropriate PsySH command for it.
 *
 * @author Justin Hileman
 */
class DrushCommand extends BaseCommand
{
    private $config;
    private $category;

    /**
     * DrushCommand constructor.
     *
     * This accepts the drush command configuration array and does a pretty
     * decent job of building a PsySH command proxy for it. Wheee!
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        parent::__construct();
    }

    public function getCategory()
    {
        if (isset($this->category)) {
            return $this->category;
        }

        $category = $this->config['category'];
        $title    = drush_command_invoke_all('drush_help', "meta:$category:title");

        if (!$title) {
            // If there is no title, then check to see if the
            // command file is stored in a folder with the same
            // name as some other command file (e.g. 'core') that
            // defines a title.
            $category = basename($this->config['path']);
            $title    = drush_command_invoke_all('drush_help', "meta:$category:title");
        }

        return $this->category = empty($title) ? 'Other commands' : $title[0];
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName($this->config['command'])
            ->setAliases($this->buildAliasesFromConfig())
            ->setDefinition($this->buildDefinitionFromConfig())
            ->setDescription($this->config['description'])
            ->setHelp($this->buildHelpFromConfig());
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // $output->page(json_encode($this->config, JSON_PRETTY_PRINT));
        // return;

        global $argv;

        $drushArgs = $argv;

        // Get rid of the repl command, and any args that follow.
        foreach (array('drush-psysh', 'shell', 'repl', 'psysh') as $name) {
            $i = array_search($name, $drushArgs);
            if ($i !== false) {
                $drushArgs = array_slice($drushArgs, 0, $i);
            }
        }

        $drush = implode(' ', array_map(array($this, 'escapeArg'), $drushArgs));
        system("$drush $input", $retval);

        if ($retval !== 0) {
            throw new RuntimeException('Something has gone horribly wrong.');
        }
    }

    /**
     * Extract drush command aliases from config array.
     *
     * @return array
     */
    private function buildAliasesFromConfig()
    {
        if (isset($this->config['aliases']) && !empty($this->config['aliases'])) {
            return $this->config['aliases'];
        } else {
            return array();
        }
    }

    /**
     * Build a command definition from drush command configuration array.
     *
     * Currently, adds all non-hidden arguments and options, and makes a decent
     * effort to guess whether an option accepts a value or not. It isn't always
     * right :P
     *
     * @return array
     */
    private function buildDefinitionFromConfig()
    {
        $def = array();

        if (isset($this->config['arguments']) && !empty($this->config['arguments'])) {

            $requiredArgs = $this->config['required-arguments'];
            if ($requiredArgs === false) {
                $requiredArgs = 0;
            } elseif ($requiredArgs === true) {
                $requiredArgs = count($this->config['arguments']);
            }

            foreach ($this->config['arguments'] as $name => $arg) {
                if (!is_array($arg)) {
                    $arg = array('description' => $arg);
                }

                if (isset($arg['hidden']) && $arg['hidden']) {
                    continue;
                }

                $req = ($requiredArgs-- > 0) ? InputArgument::REQUIRED : InputArgument::OPTIONAL;

                $def[] = new InputArgument($name, $req, $arg['description'], null);
            }
        }

        if (isset($this->config['options']) && !empty($this->config['options'])) {
            foreach ($this->config['options'] as $name => $opt) {
                if (!is_array($opt)) {
                    $opt = array('description' => $opt);
                }

                if (isset($opt['hidden']) && $opt['hidden']) {
                    continue;
                }

                // TODO: figure out if there's a way to detect
                // InputOption::VALUE_NONE (i.e. flags) via the config array.
                if (isset($opt['value']) || $opt['value'] !== 'optional') {
                    $req = InputOption::VALUE_REQUIRED;
                } else {
                    $req = InputOption::VALUE_OPTIONAL;
                }

                $def[] = new InputOption($name, '', $req, $opt['description']);
            }
        }

        return $def;
    }

    /**
     * Build a command help from the drush configuration array.
     *
     * Currently it's a word-wrapped description, plus any examples provided.
     *
     * @return string
     */
    private function buildHelpFromConfig()
    {
        $help = wordwrap($this->config['description']);

        $examples = array();
        foreach ($this->config['examples'] as $ex => $def) {
            // Skip empty examples and things with obvious pipes...
            if ($ex === '' || strpos($ex, '|') !== false) {
                continue;
            }

            $ex = preg_replace('/^drush\s+/', '', $ex);
            $examples[$ex] = $def;
        }

        if (!empty($examples)) {
            $help .= "\n\ne.g.";

            foreach ($examples as $ex => $def) {
                $help .= sprintf("\n<return>// %s</return>\n", wordwrap(OutputFormatter::escape($def), 75, "</return>\n<return>// "));
                $help .= sprintf("<return>>>> %s</return>\n", OutputFormatter::escape($ex));
            }
        }

        return $help;
    }

    /**
     * Escape a single shell argument, unless it's trivial enough not to need it.
     *
     * @param string $arg
     *
     * @return string
     */
    private function escapeArg($arg)
    {
        return preg_match('{^[\w-]+$}', $arg) ? $arg : escapeshellarg($arg);
    }
}
