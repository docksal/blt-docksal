<?php

namespace Docksal\BltDockal\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Exceptions\BltException;
use Robo\Contract\VerbosityThresholdInterface;

/**
 * Defines commands related to Docksal.
 */
class DocksalCommands extends BltTasks {

  /**
   * Initializes default Docksal configs for this project.
   *
   * @command recipes:docksal:project:init
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function docksalProjectInit() {
    // Copy .docksal folder from tempalte into the current project root.
    $result = $this->taskCopyDir([$this->getConfigValue('repo.root') . '/vendor/docksal/blt-docksal/config/.docksal' => $this->getConfigValue('repo.root') . '/.docksal'])
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
      ->run();

    if (!$result->wasSuccessful()) {
      throw new BltException("Could not initialize Docksal configuration.");
    }

    // Copy BLT local config template (aka example.local.blt.yml).
    $result = $this->taskFilesystemStack()
      ->copy($this->getConfigValue('repo.root') . '/vendor/docksal/blt-docksal/config/blt/example.local.blt.yml', $this->getConfigValue('repo.root') . '/blt/example.local.blt.yml', true)
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
      ->run();

    if (!$result->wasSuccessful()) {
      throw new BltException("Could not copy example.local.blt.yml template to blt folder.");
    }

    $result = $this->taskFilesystemStack()
      ->copy($this->getConfigValue('blt.config-files.example-local'), $this->getConfigValue('blt.config-files.local'), true)
      ->stopOnFail()
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
      ->run();

    if (!$result->wasSuccessful()) {
      $filepath = $this->getInspector()->getFs()->makePathRelative($this->getConfigValue('blt.config-files.local'), $this->getConfigValue('repo.root'));
      throw new BltException("Unable to create $filepath.");
    }

    $this->say("<info>Docksal configs were successfully initialized.</info>");

    $execute_init = $this->confirm('Would you like to run fin init to provision your Docksal stack and do site setup?', true);
    if ($execute_init) {
      $this->taskExec('fin init')->run();
    }
  }


  /**
   * Initializes BLT and Drupal configs to work with Docksal.
   *
   * Note: This command is separate from recipes:docksal:project:init because it's intended to be run inside Docksal.
   *
   * @command recipes:docksal:config:init
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function docksalConfigInit() {
    // Re-init local settings.
    try {
      $result = $this->taskFilesystemStack()
        // TODO: Add multisite local settings support as in blt:init:settings.
        ->remove($this->getConfigValue('drupal.local_settings_file'))
        ->remove($this->getConfigValue('docroot')  . '/sites/default/local.drush.yml')
        ->stopOnFail()
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
        ->run();

      if (!$result->wasSuccessful()) {
        throw new BltException("Could not remove old local settings. Please check your permissions.");
      }

      // Re-init settings after we removed old settings.
      $this->invokeCommand('blt:init:settings');
    }
    catch (BltException $e) {
      throw new BltException("Could not init local BLT or settings files.");
    }

    // Try to fix Behat local configs to use containerized Chrome if available.
    if (file_exists($this->getConfigValue('repo.root') . '/tests/behat/example.local.yml')) {
      $result = $this->taskReplaceInFile($this->getConfigValue('repo.root') . '/tests/behat/example.local.yml')
        ->from('api_url: "http://localhost:9222"')
        ->to('api_url: "http://chrome:9222"')
        ->run();

      if ($result->getData()['replaced'] > 0) {
        $this->say('Successfully replaced Behat Chrome debug URL, trying to regenerate behat local settings.');
        $result = $this->taskFilesystemStack()
          ->remove( $this->getConfigValue('repo.root') . '/tests/behat/local.yml')
          ->stopOnFail()
          ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
          ->run();

        if (!$result->wasSuccessful()) {
          throw new BltException("Could not remove Behat local settings. Please check your permissions.");
        }

        // Re-init settings after we removed old settings.
        $this->invokeCommand('tests:behat:init:config');
      }
    }
  }

}
