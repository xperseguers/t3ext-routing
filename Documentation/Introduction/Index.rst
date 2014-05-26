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

- ``http://your-website.tld/routing/extension-key/my-demo/1234``
- ``http://your-website.tld/routing/extension-key/my-demo/1234.json``
- ``http://your-website.tld/routing/extension-key/my-demo/99``

to any controller/action based on a YAML-based routing configuration. In this example,
where ``1234`` and ``99`` will be mapped to some method parameter (and converted to domain object if needed) and
``json`` will set the response format to ``json``.

The router is using the first segment of the ``route`` parameter as extension key to determine how to handle the
remaining of the requested route. A file :file:`Configuration/Routes.yaml` in the corresponding extension directory is then
read to process the request and dispatch it accordingly.
