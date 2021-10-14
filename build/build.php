<?php

define('PATH_ROOT', str_replace('\\', '/', dirname(__DIR__)));

final class PluginBuildScript
{
	private const VERSION = '1.2.0';

	private const PLUGIN_ELEMENT = 'removegenerator';
	private const PLUGIN_TYPE = 'system';

	private const JOOMLA_MINIMUM = '3.8';
	private const JOOMLA_UNSUPPORTED = '5.0';
	private const PHP_MINIMUM = '5.3.10';
	private const PHP_UNSUPPORTED = '8.1';

	private const UPDATE_JOOMLA_REGEX = '(4|3\.([89]|10))';
	private const UPDATE_NAME = 'System - Remove Generator';
	private const UPDATE_DESCRIPTION = 'Plugin for removing generator tag.';

	private string $pluginDirectory;
	private string $pluginName;
	private string $repositoryUrl;
	private string $zipFile;

	public function __construct()
	{
		$this->pluginDirectory = PATH_ROOT . '/code/plugins/' . self::PLUGIN_TYPE . '/' . self::PLUGIN_ELEMENT;
		$this->pluginName = 'plg_' . self::PLUGIN_TYPE . '_' . self::PLUGIN_ELEMENT;
		$this->repositoryUrl = 'https://github.com/SharkyKZ/' . $this->pluginName;
		$this->zipFile = __DIR__ . '/zips/' . $this->pluginName . '-' . self::VERSION . '.zip';
	}

	public function build(): void
	{
		$this->buildZip();
		$this->updateManifestXml();
		$this->updateUpdateXml();
		$this->updateChangelogXml();
	}

	private function buildZip(): void
	{
		if(!is_dir(__DIR__ . '/zips'))
		{
			mkdir(__DIR__ . '/zips', 0755);
		}

		$zip = new ZipArchive;
		$zip->open($this->zipFile, ZipArchive::CREATE);
		$iterator = new RecursiveDirectoryIterator($this->pluginDirectory);
		$iterator2 = new RecursiveIteratorIterator($iterator);

		foreach ($iterator2 as $file)
		{
			if ($file->isFile())
			{
				$zip->addFile(
					$file->getPathName(),
					str_replace(['\\', $this->pluginDirectory . '/'], ['/', ''], $file->getPathName())
				);
			}
		}

		$zip->close();
	}

	private function updateManifestXml(): void
	{
		$manifestFile = $this->pluginDirectory . '/' . self::PLUGIN_ELEMENT . '.xml';
		$manifest = file_get_contents($manifestFile);
		$manifest = preg_replace('#<version>(.*)</version>#', '<version>' . self::VERSION . '</version>', $manifest);
		file_put_contents($manifestFile, $manifest);
	}

	private function updateUpdateXml(): void
	{
		$manifestFile = PATH_ROOT . '/updates/updates.xml';
		$xml = simplexml_load_file($manifestFile);

		foreach ($xml->children() as $update)
		{
			if ((string) $update->version === self::VERSION)
			{
				return;
			}
		}

		//  Static values.
		$update = $xml->addChild('update');
		$update->addChild('name', self::UPDATE_NAME);
		$update->addChild('description', self::UPDATE_DESCRIPTION);
		$update->addChild('element', self::PLUGIN_ELEMENT);
		$update->addChild('type', 'plugin');
		$update->addChild('folder', self::PLUGIN_TYPE);
		$update->addChild('client', 'site');
		$update->addChild('maintainer', 'SharkyKZ');
		$update->addChild('maintainerurl', $this->repositoryUrl);

		// Dynamic values.
		$update->addChild('version', self::VERSION);
		$node = $update->addChild('downloads');
		$node = $node->addChild('downloadurl', $this->repositoryUrl . '/releases/download/' . self::VERSION . '/' . basename($this->zipFile));
		$node->addAttribute('type', 'full');
		$node->addAttribute('format', 'zip');

		foreach (array_intersect(['sha512', 'sha384', 'sha256'], hash_algos()) as $algo)
		{
			$update->addChild($algo, hash_file($algo, $this->zipFile));
		}

		$node = $update->addChild('infourl', $this->repositoryUrl . '/releases/tag/' . self::VERSION);
		$node->addAttribute('title', self::UPDATE_NAME);
		$update->addChild('changelogurl', 'https://raw.githubusercontent.com/SharkyKZ/' . $this->pluginName . '/master/updates/changelog.xml');

		// System requirements.
		$node = $update->addChild('targetplatform');
		$node->addAttribute('name', 'joomla');
		$node->addAttribute('version', self::UPDATE_JOOMLA_REGEX);
		$update->addChild('php_minimum', self::PHP_MINIMUM);

		file_put_contents($manifestFile, $this->formatXml($xml->asXml()));
	}

	private function updateChangelogXml(): void
	{
		$manifestFile = PATH_ROOT . '/updates/changelog.xml';
		$xml = simplexml_load_file($manifestFile);

		foreach ($xml->children() as $update)
		{
			if ((string) $update->version === self::VERSION)
			{
				return;
			}
		}

		$changelog = $xml->addChild('changelog');
		$changelog->addChild('element', self::PLUGIN_ELEMENT);
		$changelog->addChild('type', 'plugin');
		$changelog->addChild('version', self::VERSION);

		file_put_contents($manifestFile, $this->formatXml($xml->asXml()));
	}

	private function formatXml(string $xml): string
	{
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXml($xml);
		$output = $dom->saveXML();

		return str_replace('  ', "\t", $output);
	}
}

(new PluginBuildScript)->build();
