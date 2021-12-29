<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */

defined('_JEXEC') or exit;

use Joomla\CMS\Document\FeedDocument;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * System plugin for removing generator tag.
 *
 * @since  1.0.0
 */
class PlgSystemRemoveGenerator extends CMSPlugin
{
	/**
	 * Application instance.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplicationInterface
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Flag whether plugin should continue running.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $appCheck;

	/**
	 * Class constructor.
	 *
	 * @param   \Joomla\Event\DispatcherInterface  $subject  The object to observe.
	 * @param   array                              $config   An optional associative array of configuration settings.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function __construct(&$subject, array $config = array())
	{
		parent::__construct($subject, $config);

		$this->appCheck = $this->app->isClient('site') || $this->app->isClient('administrator');
	}

	/**
	 * Removes generator tag from HTML pages.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onBeforeRender()
	{
		if (!$this->appCheck)
		{
			return;
		}

		$doc = $this->app->getDocument();

		if (!($doc instanceof HtmlDocument))
		{
			return;
		}

		/**
		 * updated: @RussW - 29-dec-2021
		 * added the option to add a custom generator tag, instead of simply removing it
		 */
		if (!$this->params->get('removeGenerator', true))
		{
			$doc->setGenerator(''.$this->params->get('customGenerator').'');
		}
		else
		{
			$doc->setGenerator('');
		}
	}

	/**
	 * Removes generator tag from feed pages.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterRender()
	{
		if (!$this->appCheck)
		{
			return;
		}

		$doc = $this->app->getDocument();

		if (!($doc instanceof FeedDocument))
		{
			return;
		}

		$patterns = array(
			'/<generator.*?<\/generator>\n/',
			'/<!-- generator=".*?" -->\n/',
		);
		$body = preg_replace($patterns, '', $this->app->getBody());

		$this->app->setBody($body);
	}

	/**
	 * Removes X-Content-Encoded-By header.
	 *
	 * @return  void
	 *
	 * @since   1.1.0
	 */
	public function onAfterCompress()
	{
		if (!$this->appCheck)
		{
			return;
		}

		if (!$this->params->get('removeHeader', true))
		{
			return;
		}

		$headers = $this->app->getHeaders();

		$this->app->clearHeaders();

		foreach ($headers as $header)
		{
			if (strtolower($header['name']) === 'x-content-encoded-by')
			{
				continue;
			}

			$this->app->setHeader($header['name'], $header['value']);
		}
	}
}
