Playing socket server in PHP

This package contains twon main class, server dan handler class. The server class
are used to "serve" incoming connection only, then all they do just delegate this
connection to handler class. Handler class instantiated for each request / connection.

This package was originally from Python socketserver.py.