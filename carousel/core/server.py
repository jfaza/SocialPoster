from __future__ import annotations

import socketserver
import threading
from functools import partial
from http.server import SimpleHTTPRequestHandler
from pathlib import Path
from typing import Any


class QuietHandler(SimpleHTTPRequestHandler):
    def log_message(self, format: str, *args: Any) -> None:
        return


class ReusableTCPServer(socketserver.TCPServer):
    allow_reuse_address = True


def start_server(root: Path) -> tuple[socketserver.TCPServer, int]:
    handler = partial(QuietHandler, directory=str(root))
    httpd = ReusableTCPServer(("127.0.0.1", 0), handler)
    port = int(httpd.server_address[1])

    thread = threading.Thread(target=httpd.serve_forever, daemon=True)
    thread.start()

    return httpd, port
