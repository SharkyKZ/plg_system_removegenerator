<?php

define('PATH_ROOT', str_replace('\\', '/', dirname(__DIR__)));

final class PluginBuildScript
{
	private const PLUGIN_ELEMENT = 'removegenerator';
	private const PLUGIN_TYPE = 'system';

	private const JOOMLA_MINIMUM = '3.8';
	private const JOOMLA_UNSUPPORTED = '5.0';
	private const PHP_MINIMUM = '5.4';
	private const PHP_UNSUPPORTED = '8.3';

	private const UPDATE_JOOMLA_REGEX = '(4\.|3\.([89]|10))';
	private const UPDATE_NAME = 'System - Remove Generator';
	private const UPDATE_DESCRIPTION = 'Plugin for removing generator tag.';
	private const REPOSITORY_NAME = 'plg_system_removegenerator';

	private string $pluginDirectory;
	private string $mediaDirectory;
	private string $pluginName;
	private string $repositoryUrl;
	private string $zipFile;
	private string $version;

	public function __construct()
	{
		$this->pluginName = 'plg_' . self::PLUGIN_TYPE . '_' . self::PLUGIN_ELEMENT;

		$this->pluginDirectory = PATH_ROOT . '/code/plugins/' . self::PLUGIN_TYPE . '/' . self::PLUGIN_ELEMENT;
		$this->mediaDirectory = PATH_ROOT . '/code/media/' . $this->pluginName;

		$xml = simplexml_load_file($this->pluginDirectory . '/' . self::PLUGIN_ELEMENT . '.xml');
		$this->version = (string) $xml->version;

		$this->repositoryUrl = 'https://github.com/SharkyKZ/' . self::REPOSITORY_NAME;
		$this->zipFile = __DIR__ . '/zips/' . $this->pluginName . '-' . $this->version . '.zip';
	}

	public function build(): void
	{
		$this->buildZip();
		$this->updateUpdateXml();
		$this->updateChangelogXml();
	}

	private function buildZip(): void
	{
		if (!is_dir(__DIR__ . '/zips'))
		{
			mkdir(__DIR__ . '/zips', 0755);
		}

		$zip = new ZipArchive;
		$zip->open($this->zipFile, ZipArchive::OVERWRITE|ZipArchive::CREATE);

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

		if (is_dir($this->mediaDirectory))
		{
			$iterator = new RecursiveDirectoryIterator($this->mediaDirectory);
			$iterator2 = new RecursiveIteratorIterator($iterator);

			foreach ($iterator2 as $file)
			{
				if ($file->isFile())
				{
					$zip->addFile(
						$file->getPathName(),
						str_replace(['\\', $this->mediaDirectory . '/'], ['/', 'media/'], $file->getPathName())
					);
				}
			}
		}

		$zip->addFile(PATH_ROOT . '/LICENSE', 'LICENSE');
		$zip->close();
	}

	private function updateUpdateXml(): void
	{
		$manifestFile = PATH_ROOT . '/updates/updates.xml';
		$xml = simplexml_load_file($manifestFile);
		$children = $xml->xpath('update');

		foreach ($children as $key => $update)
		{
			if (
				(string) $update->version === $this->version
				|| ((string) $update->targetplatform->attributes()['version'] === self::UPDATE_JOOMLA_REGEX && (string) $update->php_minimum === self::PHP_MINIMUM)
			)
			{
				unset($update[0]);
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
		$update->addChild('version', $this->version);
		$node = $update->addChild('downloads');
		$node = $node->addChild('downloadurl', $this->repositoryUrl . '/releases/download/' . $this->version . '/' . basename($this->zipFile));
		$node->addAttribute('type', 'full');
		$node->addAttribute('format', 'zip');

		foreach (array_intersect(['sha512', 'sha384', 'sha256'], hash_algos()) as $algo)
		{
			$update->addChild($algo, hash_file($algo, $this->zipFile));
		}

		$node = $update->addChild('infourl', $this->repositoryUrl . '/releases/tag/' . $this->version);
		$node->addAttribute('title', self::UPDATE_NAME);
		$update->addChild('changelogurl', 'https://raw.githubusercontent.com/SharkyKZ/' . self::REPOSITORY_NAME . '/master/updates/changelog.xml');

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
			if ((string) $update->version === $this->version)
			{
				return;
			}
		}

		$changelog = $xml->addChild('changelog');
		$changelog->addChild('element', self::PLUGIN_ELEMENT);
		$changelog->addChild('type', 'plugin');
		$changelog->addChild('version', $this->version);

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

