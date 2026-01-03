#!/bin/bash
# Redirect to the actual install script on GitHub
# This allows: curl -fsSL https://getneddy.com/install.sh | bash
exec curl -fsSL https://raw.githubusercontent.com/paul-tastic/ned/master/agent/install.sh | bash -s -- "$@"
