.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _introduction:

Introduction
============


.. _what-it-does:

What does it do?
----------------

This extension lets you route requests like:

- ``http://localhost/routing/extension-key/my-demo/1234``
- ``http://localhost/routing/extension-key/my-demo/1234.json``
- ``http://localhost/routing/extension-key/my-demo/99``

to any controller/action based on a YAML-based routing configuration. In this example,
where ``1234`` and ``99`` will be mapped to some method parameter (and converted to domain object if needed) and
``json`` will set the response format to ``json``.

The router is using the first segment of the ``route`` parameter as extension key to determine how to handle the
remaining of the requested route. A file :file:`Configuration/Routes.yaml` (or :file:`Configuration/Routes.yml`) in the
corresponding extension directory is then read to process the request and dispatch it accordingly.


.. _usage:

Usage
-----

The routing is handled by the "routing" eID script of this extension. The route ``extension-key/custom/segments`` should
be passed as ``route`` parameter when you create your URI. E.g., ::

	http://localhost/index.php?eID=routing&route=extension-key/custom/segments

In order to make the URI visually more appealing, we suggest that you use a rewrite rule for your web server. Following
subsections shows how to change your Apache or Nginx configuration so that requests starting with ``routing/``
(arbitrary segment you may change to fit your needs) be handled by this extension. URI above would then become::

	http://localhost/routing/extension-key/custom/segments


.. _usage-apache:

Apache
^^^^^^

Add following line to your virtual host configuration block or to a :file:`.htaccess` at root:

.. code-block:: apacheconf

	RewriteRule ^routing/(.*)$ /index.php?eID=routing&route=$1 [QSA,L]


.. _usage-nginx:

Nginx
^^^^^

.. code-block:: nginx

	rewrite ^/routing/(.*)$ /index.php?eID=routing&route=$1 last;
