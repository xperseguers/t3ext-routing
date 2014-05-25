=======================
Request Routing Service
=======================

Service to route HTTP/REST requests to your own controller/actions.

**Good to know:** The routing is inspired by the way Flow's router works (`read more <http://docs.typo3.org/flow/TYPO3FlowDocumentation/2.1/TheDefinitiveGuide/PartIII/Routing.html>`_).


Install
=======

#. Clone this repository into `typo3conf/ext/routing`::

       $ cd /path/to/typo3conf/ext/
       $ git clone https://github.com/xperseguers/t3ext-routing.git routing

#. Go to Extension Manager and activate extension ``routing``

#. Add a rewrite rule to your ``.htaccess``::

       RewriteRule ^routing/(.*)$ /index.php?eID=routing&route=$1 [QSA,L]

   This will have the effect of using this extension for handling requests starting with ``routing/``.

Usage
=====

The router is using the first segment of the ``route`` parameter as extension key to determine how to handle the
remaining of the requested route. A file ``Configuration/Routes.yaml`` in the corresponding extension directory is then
read to process the request and dispatch it accordingly.


Demo Routing
============

This shows how to update your extension to route request automatically and handle requests like::

    http://your-website.tld/routing/extension-key/my-demo/1234
    http://your-website.tld/routing/extension-key/my-demo/1234.json
    http://your-website.tld/routing/extension-key/my-demo/99

where ``1234`` and ``99`` will be mapped to some method parameter (and converted to domain object if needed) and
``json`` will sets the response format to ``json``.


ext_localconf.php
-----------------

::

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
-------------------------

::

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
--------------------------------------

::

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
                header('Content-Type: application/json');
                $response = json_encode($response);
            } else {
                $response = var_export($response, TRUE);
            }

            return $response;
        }

    }

