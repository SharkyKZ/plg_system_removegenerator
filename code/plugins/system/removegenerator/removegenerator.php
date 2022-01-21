<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */

defined('_JEXEC') or exit;

use Joomla\CMS\Document\ErrorDocument;
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
	 * Custom generator string.
	 *
	 * @var    string
	 * @since  1.3.0
	 */
	private $generator;

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

		// Make sure generator is a string.
		$this->generator = is_string($this->params->get('customGenerator')) ? $this->params->get('customGenerator') : '';
	}

	/**
	 * Registers callback for removing X-Powered-By header.
	 *
	 * @return  void
	 *
	 * @since   1.3.0
	 */
	public function onAfterInitialise()
	{
		if (!$this->app->isClient('api'))
		{
			return;
		}

		if (!$this->params->get('removeApiHeader', true))
		{
			return;
		}

		header_register_callback(
			static function()
			{
				header_remove('X-Powered-By');
			}
		);
	}

	/**
	 * Sets generator value.
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

		if ($doc instanceof HtmlDocument)
		{
			$doc->setGenerator($this->generator);

			return;
		}

		// Generator is not escaped on feed pages.
		if ($doc instanceof FeedDocument)
		{
			$doc->setGenerator(htmlspecialchars($this->generator, ENT_XML1|ENT_QUOTES, 'UTF-8'));
		}
	}

	/**
	 * Sets generator value on error pages.
	 *
	 * @return  void
	 *
	 * @since   1.4.0
	 */
	public function onBeforeCompileHead()
	{
		if (!$this->appCheck)
		{
			return;
		}

		$doc = $this->app->getDocument();

		if ($doc instanceof ErrorDocument)
		{
			$doc->setGenerator($this->generator);
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
		// Don't remove tags if custom generator is set.
		if ($this->generator !== '')
		{
			return;
		}

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
