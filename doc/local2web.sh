#!/bin/sh
#TODO : remplacer rsync par git
rsync -vCutPr --delete ~/devel/ait.git/doc/ touv:~/sites/ait/
