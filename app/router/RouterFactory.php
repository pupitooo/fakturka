<?php

namespace App\Router;

use App\Model\Facade\UriFacade;
use Drahak\Restful\Application\Routes\ResourceRoute;
use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Configurator;

class RouterFactory
{

	const LOCALE_PARAM_NAME = 'locale';
	const LOCALE_DEFAULT_LANG = 'cs';
	const LOCALE_PARAM = '[<' . self::LOCALE_PARAM_NAME . '=' . self::LOCALE_DEFAULT_LANG . ' cs|sk|en>/]';

	/** @var UriFacade @inject */
	public $uriFacade;

	/**
	 * @return IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();

		$router[] = $fotoRouter = new RouteList('Foto');
		$router[] = $apiRouter = new RouteList('Api');
		$router[] = $cronRouter = new RouteList('Cron');
		$router[] = $adminRouter = new RouteList('App');
		$router[] = $frontRouter = new RouteList('Front');

		// <editor-fold desc="Foto">

		$fotoRouter[] = new Route('foto/[<size \d+\-\d+>/]<name .+>', [
			'presenter' => "Foto",
			'action' => 'default',
			'size' => NULL,
			'name' => NULL,
		]);

		// </editor-fold>
		// <editor-fold desc="Api">

		$apiRouter[] = new ResourceRoute('api[/<presenter>[/<action>]]', [
			'presenter' => 'Default',
			'action' => 'default'
		], ResourceRoute::GET | ResourceRoute::POST);

		// </editor-fold>
		// <editor-fold desc="Cron">

		$cronRouter[] = new Route('cron[/<presenter>[/<action>]]', [
			'presenter' => 'Default',
			'action' => 'default',
		]);

		// </editor-fold>
		// <editor-fold desc="App">

		$adminRouter[] = new Route(self::LOCALE_PARAM . 'app[/<presenter>[/<action>[/<id>]]]', [
			'presenter' => 'Dashboard',
			'action' => 'default',
			'id' => NULL,
		]);

		// </editor-fold>
		// <editor-fold desc="Front">

		$frontRouter[] = new Route('install', [
			'presenter' => 'Install',
			'action' => 'default',
		]);

		$frontRouter[] = $routeMain = new FilterRoute(self::LOCALE_PARAM . '<presenter>[/<action>[/<id>]]', [
			'presenter' => 'Homepage',
			'action' => 'default',
			'id' => NULL,
		]);

		$routeMain->addFilter('presenter', [$this->uriFacade, 'nameToPresenter'], [$this->uriFacade, 'presenterToName']);
		$routeMain->addFilter('action', [$this->uriFacade, 'nameToAction'], [$this->uriFacade, 'actionToName']);

		// </editor-fold>

		return $router;
	}

}
