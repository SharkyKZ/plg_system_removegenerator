<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */

defined('_JEXEC') or exit;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Version;

/**
 * Plugin installer script.
 */
final class PlgSystemRemoveGeneratorInstallerScript
{
	/**
	 * Minimum supported Joomla! version.
	 *
	 * @var    string
	 * @since  1.2.0
	 */
	private $joomlaMinimum = '3.8';

	/**
	 * Next unsupported Joomla! version.
	 *
	 * @var    string
	 * @since  1.2.0
	 */
	private $joomlaUnsupported = '6.0';

	/**
	 * Minimum supported PHP version.
	 *
	 * @var    string
	 * @since  1.2.0
	 */
	private $phpMinimum = '5.4';

	/**
	 * Next unsupported PHP version.
	 *
	 * @var    string
	 * @since  1.2.0
	 */
	private $phpUnsupported = '8.5';

	/**
	 * Function called before extension installation/update/removal procedure commences.
	 *
	 * @param   string                                 $type    The type of change (install, update, discover_install or uninstall).
	 * @param   Joomla\CMS\Installer\InstallerAdapter  $parent  The class calling this method.
	 *
	 * @return  bool  Returns true if installation can proceed.
	 *
	 * @since   1.2.0
	 */
	public function preflight($type, $parent)
	{
		if ($type === 'uninstall')
		{
			return true;
		}

		if (version_compare(JVERSION, $this->joomlaMinimum, '<'))
		{
			return false;
		}

		if (version_compare(JVERSION, $this->joomlaUnsupported, '>=')  && !(new Version)->isInDevelopmentState())
		{
			return false;
		}

		if (version_compare(PHP_VERSION, $this->phpMinimum, '<'))
		{
			Log::add(Text::sprintf('PLG_SYSTEM_REMOVEGENERATOR_INSTALL_PHP_MINIMUM', $this->phpMinimum), Log::WARNING, 'jerror');

			return false;
		}

		if (version_compare(PHP_VERSION, $this->phpUnsupported, '>='))
		{
			Log::add(Text::sprintf('PLG_SYSTEM_REMOVEGENERATOR_INSTALL_PHP_UNSUPPORTED', $this->phpUnsupported), Log::WARNING, 'jerror');

			return false;
		}

		return true;
	}
}
