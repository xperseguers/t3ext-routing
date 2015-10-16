.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developer-manual:

Developer manual
================


.. only:: html

	.. contents::
		:local:
		:depth: 1


.. _developer-manual-router:

The router
----------

When the eID router script of this extension is called, it will lookup the first segment of the ``route`` parameter::

	http://localhost/index.php?eID=routing&route=extension-key/custom/segments

That is, ``extension-key``, and check all routes from file
:file:`typo3conf/ext/{extension-key}/Configuration/Routes.yaml` until one can return the correct URI for the specified
arguments.

.. note::

	If no matching route can be found, a 404 status code is sent for the HTTP response.


.. _developer-manual-routes:

Routes
------

A route describes the way from your browser to the controller.

With the ``uriPattern`` you can define how a route is represented in the browser's address
bar. By setting ``defaults`` you can specify package, controller and action that should
apply when a request matches the route. Besides you can set arbitrary default values that
will be available in your controller. They are called ``defaults`` because you can overwrite
them by so called *dynamic route parts*.


.. _developer-manual-uri-patterns:

URI patterns
------------

The URI pattern defines the appearance of the URI. In a simple setup the pattern only
consists of *static route parts* and is equal to the actual URI (without protocol,
host and the ``routing/extension-key/`` prefix).

In order to reduce the amount of routes that have to be created, you are allowed to insert
markers, so called *dynamic route parts*, that will be replaced by the Routing Framework.

But first things first.


.. _developer-manual-static-route-parts:

Static route parts
^^^^^^^^^^^^^^^^^^

Let's create a route that calls the ``listAction`` of the ``ProductController`` when browsing to
``http://localhost/routing/extension-key/my/demo``:

*Example: Simple route with static route parts Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Static demo route'
	  uriPattern: 'my/demo'
	  defaults:
	    '@package':    'MyVendor.Demo'
	    '@plugin':     'MyPlugin'
	    '@controller': 'Product'
	    '@action':     'list'

.. note::

	``name`` is optional, but it's recommended to set a name for all routes to make debugging easier.


.. _developer-manual-dynamic-route-parts:

Dynamic route parts
-------------------

Dynamic route parts are enclosed in curly brackets and define parts of the URI that are
not fixed.

Let's add some dynamics to the previous example:

*Example: Simple route with static and dynamic route parts - Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Dynamic demo route'
	  uriPattern: 'my/demo/{@action}'
	  defaults:
	    '@package':    'MyVendor.Demo'
	    '@plugin':     'MyPlugin'
	    '@controller': 'Product'

Now ``http://localhost/routing/extension-key/my/demo/list`` calls the ``listAction`` just like in the previous
example.

With ``http://localhost/routing/extension-key/my/demo/new`` you'd invoke the ``newAction`` and so on.

.. note::

	It's not allowed to have successive dynamic route parts in the URI pattern because it
	wouldn't be possible to determine the end of the first dynamic route part then.

The ``@`` prefix should reveal that *action* has a special meaning here. Other predefined keys
are ``@package``, ``@plugin``, ``@controller`` and ``@format``. But you can use dynamic route parts to
set any kind of arguments:

*Example: dynamic parameters - Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Dynamic demo route with parameter'
	  uriPattern: 'products/list/{sortOrder}.{@format}'
	  defaults:
	    '@package':    'MyVendor.Demo'
	    '@plugin':     'MyPlugin'
	    '@controller': 'Product'
	    '@action':     'list'

Browsing to ``http://localhost/routing/extension-key/products/list/descending.xml`` will then call the ``listAction`` in
your ``Product`` controller and the request argument ``sortOrder`` has the value of
``descending``.

By default, dynamic route parts match any simple type and convert it to a string that is available through
the corresponding request argument.


.. _developer-manual-object-route-parts:

Object route parts
------------------

If a route part refers to an object, that is *known to the Persistence Manager*, it will be instantied from
its technical identifier (uid) automatically:

*Example: object parameters - Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Single product route'
	  uriPattern: 'products/{product}'
	  defaults:
	    '@package':    'MyVendor.Demo'
	    '@plugin':     'MyPlugin'
	    '@controller': 'Product'
	    '@action':     'show'

If you add this route *above the previously generated dynamic routes*, an URI pointing to the show action of
the ProductController should look like ``http://localhost/routing/extension-key/products/123``.


.. _developer-manual-request-methods:

Request Methods
---------------

Usually the Routing Framework does not care whether it handles a GET or POST request and just looks at the request path.
However in some cases it makes sense to restrict a route to certain HTTP methods. This is especially true for REST APIs
where you often need the same URI to invoke different actions depending on the HTTP method.

This can be achieved with a setting ``httpMethods``, which accepts an array of HTTP verbs:

.. code-block:: yaml

	-
	  uriPattern: 'some/path'
	  defaults:
	    '@package':    'MyVendor.Demo'
	    '@plugin':     'MyPlugin'
	    '@controller': 'Standard'
	    '@action':     'action1'
	  httpMethods: ['GET']
	-
	  uriPattern: 'some/path'
	  defaults:
	    '@package':    'MyVendor.Demo'
	    '@plugin':     'MyPlugin'
	    '@controller': 'Standard'
	    '@action':     'action2'
	  httpMethods: ['POST', 'PUT']

Given the above routes a *GET* request to ``http://localhost/routing/extension-key/some/path`` would invoke
the ``action1Action()`` while *POST* and *PUT* requests to the same URI would call ``action2Action()``.


.. _developer-manual-global-routes:

Global routes
-------------

The Routing Framework lets you register global routes as well, that is, without any ``extension-key`` segment. As the
extension key is then missing, you should manually register corresponding YAML files to be globally available by adding
a line to your :file:`ext_localconf.php`:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['routing']['globalRoutes'][] = 'EXT:' . $_EXTKEY . '/Configuration/MyGlobalRoutes.yaml';


.. _developer-manual-demo-localization:

Localization
------------

Native TYPO3 localization works out-of-the-box if the call to the eID router script of this extension is made in the
context of a given page; that is, if a GET parameter ``id`` exists. E.g.,

::

	http://localhost/routing/extension-key/my-demo/1234?id=12


.. _developer-manual-demo-routing:

Demo Routing
------------

This shows how to update your extension to route request automatically and handle requests like::

	http://localhost/routing/extension-key/my-demo/1234
	http://localhost/routing/extension-key/my-demo/1234.json
	http://localhost/routing/extension-key/my-demo/99

where ``1234`` and ``99`` will be mapped to some method parameter (and converted to domain object if needed) and
``json`` will set the response format to ``json``.


ext_localconf.php
^^^^^^^^^^^^^^^^^

.. code-block:: php

	<?php
	if (!defined('TYPO3_MODE')) {
	    die ('Access denied.');
	}

	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	    'MyVendor.' . $_EXTKEY,
	    'API',
	    array('Dummy' => 'demo'),
	    array('Dummy' => 'demo')
	);


Configuration/Routes.yaml
^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: yaml

	-
	  name: 'Demo action with a parameter in a given format (JSON, ...)'
	  uriPattern: 'my-demo/{value}.{@format}'
	  defaults:
	    '@package':    'MyVendor.ExtensionKey'
	    '@plugin':     'API'
	    '@controller': 'Dummy'
	    '@action':     'demo'
	-
	  name: 'Demo action with a parameter'
	  uriPattern: 'my-demo/{value}'
	  defaults:
	    '@package':    'MyVendor.ExtensionKey'
	    '@plugin':     'API'
	    '@controller': 'Dummy'
	    '@action':     'demo'


Classes/Controller/DummyController.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

	<?php
	namespace MyVendor\ExtensionKey\Controller;

	class DummyController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	    /**
	     * @param int $value
	     * @return string
	     */
	    public function demoAction($value) {
	        $response = array('value' => $value);

	        if ($this->request->getFormat() === 'json') {
	            // Hint: you should use \TYPO3\CMS\Extbase\Mvc\View\JsonView instead
	            header('Content-Type: application/json');
	            $response = json_encode($response);
	        } else {
	            $response = var_export($response, TRUE);
	        }

	        return $response;
	    }

	}


Using JsonView
--------------

Let's assume you have the list of persons to be exported with the action ``demo`` above.

Create a file :file:`Classes/View/Dummy/DemoJson.php`:

.. code-block:: php

	<?php
	namespace MyVendor\ExtensionKey\View\Dummy;

	class DemoJson extends \TYPO3\CMS\Extbase\Mvc\View\JsonView {

	    protected $configuration = array(
	        'persons' => array(
	            '_descendAll' => array(
	                //'_only' => array('property1', 'property2'),
	                '_exclude' => array('pid')
	            )
	        )
	    );

	}

and modify your action ``demo``:

.. code-block:: php

	/**
	 * @param int $value
	 * @return void
	 */
	public function demoAction($value) {
	    $persons = $this->personRepository->findAll();
	    $this->view->assign('persons', $persons);
	    $this->view->setVariablesToRender(array('persons'));
	}

and you're done! Extbase's dispatcher will see your special view "Demo" to be used for format "Json" and instantiate it
instead of the default view. Your domain objects will be serialized and the JSON header sent automatically.

.. hint::
    The class name pattern is ``@vendor\@extension\View\@controller\@action@format``, meaning you will have to specify
    a JSON output by ending your route with ``.json``.
