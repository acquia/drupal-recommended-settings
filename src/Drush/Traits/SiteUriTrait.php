<?php

declare(strict_types=1);

namespace Acquia\Drupal\RecommendedSettings\Drush\Traits;

use Symfony\Component\Filesystem\Path;

/**
 * Trait to deletermine Site Uri.
 */
trait SiteUriTrait {

  /**
   * Determine an appropriate site subdir name to use for the provided uri.
   *
   * This code copied from SiteInstallCommands.php file.
   *
   * @param string $root
   *   The path to drupal docroot.
   * @param string $uri
   *   The site uri.
   *
   * @return array|false|mixed|string|string[]
   *   Returns the site uri.
   */
  private function getSitesSubdirFromUri(string $root, string $uri): mixed {
    $dir = strtolower($uri);
    // Always accept simple uris (e.g. 'dev', 'stage', etc.)
    if (preg_match('#^[a-z0-9_-]*$#', $dir)) {
      return $dir;
    }
    // Strip off the protocol from the provided uri -- however,
    // now we will require that the sites subdir already exist.
    $dir = preg_replace('#[^/]*//#', '', $dir);
    if ($dir && file_exists(Path::join($root . "/sites", $dir))) {
      return $dir;
    }

    // Find the dir from sites.php file.
    $sites_file = $root . '/sites/sites.php';
    if (file_exists($sites_file)) {
      $sites = [];
      include $sites_file;
      $uri = $this->prepareSiteUri($uri);
      if (!empty($sites) && array_key_exists($uri, $sites)) {
        return $sites[$uri];
      }
    }
    // Fall back to default directory if it exists.
    if (file_exists(Path::join($root, 'sites', 'default'))) {
      return 'default';
    }

    return FALSE;
  }

  /**
   * Preparing site uri if --uri="https://www.example.com"
   *
   * @param string $uri
   *   Site uri in a url format.
   *
   * @return string
   *   Site uri with format port.domain.path or normal uri.
   */
  protected function prepareSiteUri(string $uri): string {
    // Checking whether the passed --uri from the command is valid url.
    if (filter_var($uri, FILTER_VALIDATE_URL) !== FALSE) {
      // Parsing url to retrieve url object.
      $parseUrl = parse_url($uri);
      // Initialize site uri.
      $siteUri = "";
      if (isset($parseUrl['port'])) {
        $siteUri = $parseUrl['port'] . ".";
      }
      if (isset($parseUrl['host'])) {
        $siteUri = $siteUri . $parseUrl['host'];
      }
      if (isset($parseUrl['path'])) {
        // Url consists of path then replace slash with dot.
        $siteUri = $siteUri . str_replace("/", ".", rtrim($parseUrl['path'], "/"));
      }
      // Return site uri as port.domain.path pattern.
      return trim($siteUri);
    }

    return $uri;
  }

}
