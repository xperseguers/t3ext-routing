.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developer-manual:

Developer manual
================

Demo Routing
------------

This shows how to update your extension to route request automatically and handle requests like::

	http://your-website.tld/routing/extension-key/my-demo/1234
	http://your-website.tld/routing/extension-key/my-demo/1234.json
	http://your-website.tld/routing/extension-key/my-demo/99

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
	  default:
	    '@package':    'MyVendor.ExtensionKey'
	    '@plugin':     'API'
	    '@controller': 'Dummy'
	    '@action':     'demo'
	-
	  name: 'Demo action with a parameter'
	  uriPattern: 'my-demo/{value}'
	  default:
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
	    public function demo($value) {
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
	public function demo($value) {
	    $persons = $this->personRepository->findAll();
	    $this->view->assign('persons', $persons);
	    $this->view->setVariablesToRender(array('persons'));
	}

and you're done! Extbase's dispatcher will see your special view "Demo" to be used for format "Json" and instantiate it
instead of the default view. Your domain objects will be serialized and the JSON header sent automatically.

.. hint:: The class name pattern is ``@vendor\@extension\View\@controller\@action@format``.
