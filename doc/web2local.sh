#!/bin/sh

rsync -vCutPr --delete -e ssh touv:~/sites/ait/ ~/devel/ait/doc/

